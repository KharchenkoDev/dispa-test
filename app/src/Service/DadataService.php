<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DadataService
{
    private const FIND_BY_ID_URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'DADATA_TOKEN')]
        private readonly string $apiToken,
    ) {}

    public function findByInn(string $inn): array
    {
        $response = $this->httpClient->request('POST', self::FIND_BY_ID_URL, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => "Token {$this->apiToken}",
            ],
            'json' => ['query' => $inn],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode === 401) {
            throw new \RuntimeException('Ошибка авторизации DaData: проверьте DADATA_TOKEN.', 401);
        }

        if ($statusCode !== 200) {
            throw new \RuntimeException("DaData вернул неожиданный статус: {$statusCode}.", 502);
        }

        $data = $response->toArray();

        return $data['suggestions'][0] ?? [];
    }
}
