<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DadataService
{
    private const FIND_BY_ID_URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party';
    private const TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'DADATA_TOKEN')]
        private readonly string $apiToken,
    ) {
    }

    public function findByInn(string $inn): array
    {
        try {
            $response = $this->httpClient->request('POST', self::FIND_BY_ID_URL, [
                'timeout' => self::TIMEOUT_SECONDS,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => "Token {$this->apiToken}",
                ],
                'json' => ['query' => $inn],
            ]);

            $statusCode = $response->getStatusCode();

            if (401 === $statusCode) {
                throw new \RuntimeException('Ошибка авторизации DaData: проверьте DADATA_TOKEN.', 401);
            }

            if (200 !== $statusCode) {
                throw new \RuntimeException("DaData вернул неожиданный статус: {$statusCode}.", 502);
            }

            return $response->toArray()['suggestions'][0] ?? [];
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Сеть DaData недоступна или истёк таймаут.', 504, $e);
        } catch (DecodingExceptionInterface $e) {
            throw new \RuntimeException('Некорректный ответ от DaData.', 502, $e);
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }
}
