<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Repository;

interface RatePolicyInterface
{
    /**
     * @param int $income
     * @return float
     */
    public function getRate(int $income): float;

    /**
     * @return float
     */
    public function getFallbackRate(): float;
}
