<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Infrastructure;

use Robinson\InterestAccount\Repository\RatePolicyInterface;
use Robinson\InterestAccount\Repository\StatsClientInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;


class StatsApiClient implements StatsClientInterface
{
    /**
     * @param ClientInterface $httpClient
     * @param RatePolicyInterface $ratePolicy
     */
    public function __construct(
        private ClientInterface $httpClient,
        private RatePolicyInterface $ratePolicy
    ) {}


    /**
     * @param string $userId
     * @return float
     */
    public function getInterestRateForUser(string $userId): float
    {
        try {
            $response = $this->httpClient->request('GET', "/users/{$userId}");
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            // Validate id.
            if (isset($data['id']) && $data['id'] !== $userId) {
                // TODO: In a real app, log this is failing.
                return $this->ratePolicy->getFallbackRate();
            }

            // Validate income.
            if (!array_key_exists('income', $data) || is_null($data['income'])) {
                // TODO: In a real app, log this is failing.
                return $this->ratePolicy->getFallbackRate();
            }

            // Validate income is an integer
            if (!is_int($data['income'])) {
                // TODO: In a real app, log this is failing.
                return $this->ratePolicy->getFallbackRate();
            }

            return $this->ratePolicy->getRate($data['income']);
        } catch (\Throwable $e) {
            // TODO: In a real app, log this exception so we know this is failing.
            // $this->logger->error("Failed to fetch user stats: " . $e->getMessage());
            return $this->ratePolicy->getFallbackRate();
        }
    }
}
