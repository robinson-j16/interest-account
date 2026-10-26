<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Tests;

use Robinson\InterestAccount\Service\InterestCalculator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InterestCalculatorTest extends TestCase
{
    /**
     * @dataProvider interestScenarios
     */
    public function testCalculatesInterestAccurately(
        int $balance,
        float $rate,
        int $daysElapsed,
        int $expectedMicroPennies
    ): void {
        $calculator = new InterestCalculator();
        $start = new DateTimeImmutable('2024-01-01 00:00:00');
        $end = $start->modify("+{$daysElapsed} days");

        $result = $calculator->calculateInterest($balance, $rate, $start, $end);

        $this->assertSame($expectedMicroPennies, $result);
    }

    public static function interestScenarios(): array
    {
        return [
            '3 days standard' => [
                100000, // £1,000 in pennies
                1.02,   // 1.02%
                3,      // 3 days
                8_383_561, // Expected micro-pennies for 3 days at 1.02%
            ],
            '1 day standard' => [
                100000, // £1,000 in pennies
                1.02,   // 1.02%
                1,      // 1 day
                2_794_520, // Expected micro-pennies for 1 day at 1.02%
            ],
            'Zero balance' => [
                0,
                1.02,
                3,
                0,
            ],
            'Zero days' => [
                100000,
                1.02,
                0,
                0,
            ],
            'High balance one year' => [
                1_000_000, // £10,000.00 in pennies
                5.0,       // 5%
                365,       // 1 year
                50_000_000_000, // £500 interest -> 50,000 pennies -> 50,000 * 1,000,000 micro-pennies
            ],
        ];
    }
}
