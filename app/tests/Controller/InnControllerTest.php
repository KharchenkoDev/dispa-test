<?php

namespace App\Tests\Controller;

use App\Service\DadataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InnControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->em->getConnection()->executeStatement('DELETE FROM inn_lookups');
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

    public function testFreshCacheSkipsDadata(): void
    {
        // Мок ожидает ровно один вызов — второй запрос должен вернуть кеш из БД
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

    public function testStaleCacheRefreshesDadata(): void
    {
        // Создаём запись с устаревшим updated_at (2 часа назад)
        $staleTime = new \DateTimeImmutable('-2 hours');
        $this->em->getConnection()->executeStatement(
            'INSERT INTO inn_lookups (inn, name, is_active, okved, raw_response, created_at, updated_at)
             VALUES (:inn, :name, 1, :okved, :raw, :created, :updated)',
            [
                'inn' => '7707083893',
                'name' => 'Старое название',
                'okved' => '64.19',
                'raw' => '{}',
                'created' => $staleTime->format('Y-m-d H:i:s'),
                'updated' => $staleTime->format('Y-m-d H:i:s'),
            ]
        );

        // DaData должна быть вызвана, так как кеш устарел
        $mock = $this->createMock(DadataService::class);
        $mock->expects(self::once())->method('findByInn')->willReturn($this->sberbankFixture());
        static::getContainer()->set(DadataService::class, $mock);

        $this->client->request('GET', '/api/inn/7707083893');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('ПАО Сбербанк', $data['name']);
    }

    public function testNetworkErrorReturns504(): void
    {
        $mock = $this->createStub(DadataService::class);
        $mock->method('findByInn')->willThrowException(
            new \RuntimeException('Сеть DaData недоступна или истёк таймаут.', 504)
        );
        static::getContainer()->set(DadataService::class, $mock);

        $this->client->request('GET', '/api/inn/7707083893');

        self::assertResponseStatusCodeSame(504);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
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

    public function testPartialDadataResponseHandledGracefully(): void
    {
        $mock = $this->createMock(DadataService::class);
        $mock->expects(self::once())->method('findByInn')->willReturn([
            'value' => 'ООО Тест',
            'data' => [
                'name' => ['short_with_opf' => 'ООО Тест'],
                'state' => [], // нет поля status
                // нет okved
            ],
        ]);
        static::getContainer()->set(DadataService::class, $mock);

        $this->client->request('GET', '/api/inn/1234567890');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('ООО Тест', $data['name']);
        self::assertFalse($data['is_active']);
        self::assertSame('', $data['okved']);
    }

    private function sberbankFixture(): array
    {
        return [
            'value' => 'ПАО СБЕРБАНК',
            'data' => [
                'name' => [
                    'short_with_opf' => 'ПАО Сбербанк',
                    'full_with_opf' => 'ПУБЛИЧНОЕ АКЦИОНЕРНОЕ ОБЩЕСТВО «СБЕРБАНК РОССИЯ»',
                ],
                'state' => ['status' => 'ACTIVE'],
                'okved' => '64.19',
            ],
        ];
    }
}
