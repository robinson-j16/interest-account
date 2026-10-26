<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Application;

use Robinson\InterestAccount\Infrastructure\InMemoryAccountRepository;
use Robinson\InterestAccount\Infrastructure\InMemoryTransactionRepository;
use Robinson\InterestAccount\Infrastructure\RamseyUuidGenerator;
use Robinson\InterestAccount\Infrastructure\StatsApiClient;
use Robinson\InterestAccount\Repository\DefaultRatePolicy;
use Robinson\InterestAccount\Service\AccountService;
use Robinson\InterestAccount\Service\InterestCalculator;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

final class AccountServiceFactory
{
    /**
     * @param ClientInterface|null $httpClient
     * @param DefaultRatePolicy|null $ratePolicy
     * @param InterestAccountConfig|null $config
     * @return AccountService
     */
    public static function create(
        ?ClientInterface $httpClient = null,
        ?DefaultRatePolicy $ratePolicy = null,
        ?InterestAccountConfig $config = null,
    ): AccountService {
        $config ??= new InterestAccountConfig();

        $accountRepo = new InMemoryAccountRepository();
        $transactionRepo = new InMemoryTransactionRepository();

        if (is_null($httpClient)) {
            $httpClient = new Client([
                'base_uri' => $config->statsBaseUri,
            ]);
        }

        if (is_null($ratePolicy)) {
            $ratePolicy = new DefaultRatePolicy(
                $config->thresholdPennies,
                $config->lowRate,
                $config->highRate,
                $config->fallbackRate,
            );
        }

        $statsClient = new StatsApiClient($httpClient, $ratePolicy);
        $interestCalculator = new InterestCalculator();
        $idGenerator = new RamseyUuidGenerator();

        return new AccountService(
            $accountRepo,
            $transactionRepo,
            $statsClient,
            $interestCalculator,
            $idGenerator,
        );
    }
}
