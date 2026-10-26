<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Application;

final class InterestAccountConfig
{
    /**
     * @param string $statsBaseUri
     * @param float $thresholdPennies
     * @param float $lowRate
     * @param float $highRate
     * @param float $fallbackRate
     */
    public function __construct(
        public readonly string $statsBaseUri = 'https://stats.example.test/',
        public readonly float $thresholdPennies = 5_000_00,
        public readonly float $lowRate = 0.93,
        public readonly float $highRate = 1.02,
        public readonly float $fallbackRate = 0.5,
    ) {}
}
