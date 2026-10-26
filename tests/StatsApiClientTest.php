<?php

namespace Robinson\InterestAccount\Tests;

use Robinson\InterestAccount\Infrastructure\StatsApiClient;
use Robinson\InterestAccount\Repository\DefaultRatePolicy;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;

// Assume this is the service class that makes the API call
class StatsApiClientTest extends TestCase
{
    private DefaultRatePolicy $policy;
    public function setUp(): void
    {
        parent::setUp();
        $this->policy = PolicyFactory::default();
    }
    /**
     * @dataProvider incomeProvider
     */
    public function testGetUserIncome_SuccessfulResponse(?int $income, float $expectedRate): void
    {
        $mock = new MockHandler([
            // 1. Define the 200 OK response with the Statistics payload
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => '88224979-406e-4e32-9458-55836e4e1f95',
                'income' => $income,
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $httpStatsApiClient = new StatsApiClient($httpClient, $this->policy);

        $userId = '88224979-406e-4e32-9458-55836e4e1f95';

        $result = $httpStatsApiClient->getInterestRateForUser($userId);

        $this->assertSame($expectedRate, $result);
    }

    public static function incomeProvider(): array
    {
        return [
            'high income' => [500000, 1.02],
            'low income' => [499999, 0.93],
            'null income' => [null, 0.5],
        ];
    }

    /**
     * @dataProvider non200StatusProvider
     */
    public function testGetUserIncome_Non200ResponseThrows(int $statusCode, float $expectedRate): void
    {
        $mock = new MockHandler([
            new Response($statusCode, ['Content-Type' => 'application/json']),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $httpStatsApiClient = new StatsApiClient($httpClient, $this->policy);

        $userId = '88224979-406e-4e32-9458-55836e4e1f95';
        $result = $httpStatsApiClient->getInterestRateForUser($userId);

        $this->assertSame(0.5, $result);
    }

    public static function non200StatusProvider(): array
    {
        return [
            '500 internal server error' => [500, 0.5],
            '404 not found' => [404, 0.5],
        ];
    }
}