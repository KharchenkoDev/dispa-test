<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testIndexReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.card');
    }

    public function testIndexWithPageParam(): void
    {
        $client = static::createClient();
        $client->request('GET', '/?page=1');

        self::assertResponseIsSuccessful();
    }

    public function testIndexWithInvalidPageFallsToFirst(): void
    {
        $client = static::createClient();
        $client->request('GET', '/?page=0');

        self::assertResponseIsSuccessful();
    }
}
