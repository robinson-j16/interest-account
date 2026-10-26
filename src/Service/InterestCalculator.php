<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Service;

final class InterestCalculator implements InterestCalculatorInterface
{
    /**
     * @param int $balancePennies
     * @param float $annualRatePercent
     * @param \DateTimeImmutable $lastRunDate
     * @param \DateTimeImmutable $currentDate
     * @return int
     */
    public function calculateInterest(
        int $balancePennies,
        float $annualRatePercent,
        \DateTimeImmutable $lastRunDate,
        \DateTimeImmutable $currentDate
    ): int
    {
        // We calculate interest in integer micro-pennies to avoid floating point money math.
        // 1 penny = 1_000_000 micro-pennies.
        if ($currentDate <= $lastRunDate) {
            $days = 0;
        } else {
            $days = $currentDate->diff($lastRunDate)->days;
        }

        // Convert annual rate percent (e.g. 1.02) to integer basis points (e.g. 102).
        $rateBps = (int) round($annualRatePercent * 100);

        // Formula (in pennies):
        // interestPennies = balance * rateBps * days / 365
        // We multiply by 1_000_000 to work in micro-pennies and keep everything as integers.
        // Algebraically, this is equivalent to:
        // interestMicroPennies = balance * rateBps * days * 100 / 365

        $numerator = $balancePennies
            * $rateBps
            * $days
            * 100;

        return intdiv($numerator, 365);
    }
}
