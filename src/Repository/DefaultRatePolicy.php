<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Repository;

final class DefaultRatePolicy implements RatePolicyInterface
{
    /**
     * @param float $thresholdPennies
     * @param float $lowRate
     * @param float $highRate
     * @param float $fallbackRate
     */
    public function __construct(
        private float $thresholdPennies,
        private float $lowRate,
        private float $highRate,
        private float $fallbackRate
    ) {}

    /**
     * @param int $income
     * @return float
     */
    public function getRate(int $income): float
    {
        return $income < $this->thresholdPennies ? $this->lowRate : $this->highRate;
    }

    /**
     * @return float
     */
	public function getFallbackRate(): float
	{
		return $this->fallbackRate;
	}
}
