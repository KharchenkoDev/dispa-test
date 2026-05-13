<?php

namespace App\Tests\Controller;

use App\Service\DadataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InnControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection()
            ->executeStatement('DELETE FROM inn_lookups');
    }

    public function testInvalidInnTooShort(): void
    {
        $this->client->request('GET', '/api/inn/123');

        self::assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testInvalidInnNonNumeric(): void
    {
        $this->client->request('GET', '/api/inn/770708abc3');

        self::assertResponseStatusCodeSame(400);
    }

    public function testInnNotFoundInDadata(): void
    {
        $mock = $this->createStub(DadataService::class);
        $mock->method('findByInn')->willReturn([]);
        static::getContainer()->set(DadataService::class, $mock);

        $this->client->request('GET', '/api/inn/0000000000');

        self::assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testSuccessfulLookupCallsDadataAndSavesToDb(): void
    {
        $mock = $this->createMock(DadataService::class);
        $mock->expects(self::once())->method('findByInn')->willReturn($this->sberbankFixture());
        static::getContainer()->set(DadataService::class, $mock);

        $this->client->request('GET', '/api/inn/7707083893');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('7707083893', $data['inn']);
        self::assertSame('ПАО Сбербанк', $data['name']);
        self::assertTrue($data['is_active']);
        self::assertSame('64.19', $data['okved']);
    }

    public function testCachedInnSkipsDadata(): void
    {
        // Мок ожидает ровно один вызов — второй запрос должен отдать данные из БД
        $mock = $this->createMock(DadataService::class);
        $mock->expects(self::once())->method('findByInn')->willReturn($this->sberbankFixture());
        static::getContainer()->set(DadataService::class, $mock);

        $this->client->request('GET', '/api/inn/7707083893');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/inn/7707083893');
        self::assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('7707083893', $data['inn']);
    }

    public function testDadataAuthErrorReturns401(): void
    {
        $mock = $this->createStub(DadataService::class);
        $mock->method('findByInn')->willThrowException(
            new \RuntimeException('Ошибка авторизации DaData: проверьте DADATA_TOKEN.', 401)
        );
        static::getContainer()->set(DadataService::class, $mock);

        $this->client->request('GET', '/api/inn/7707083893');

        self::assertResponseStatusCodeSame(401);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    private function sberbankFixture(): array
    {
        return [
            'value' => 'ПАО СБЕРБАНК',
            'data'  => [
                'name'       => [
                    'short_with_opf' => 'ПАО Сбербанк',
                    'full_with_opf'  => 'ПУБЛИЧНОЕ АКЦИОНЕРНОЕ ОБЩЕСТВО «СБЕРБАНК РОССИЯ»',
                ],
                'state'      => ['status' => 'ACTIVE'],
                'okved'      => '64.19',
                'okved_name' => 'Деятельность по предоставлению прочих видов кредита',
            ],
        ];
    }
}
