<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Service;

use DateTimeImmutable;

interface InterestCalculatorInterface
{
    /**
     * @param int $balancePennies
     * @param float $annualRatePercent
     * @param DateTimeImmutable $lastRunDate
     * @param DateTimeImmutable $currentDate
     * @return int
     */
    public function calculateInterest(
        int $balancePennies,
        float $annualRatePercent,
        DateTimeImmutable $lastRunDate,
        DateTimeImmutable $currentDate
    ): int;
}
