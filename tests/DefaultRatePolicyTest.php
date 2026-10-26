<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Tests;

use Robinson\InterestAccount\Repository\DefaultRatePolicy;
use PHPUnit\Framework\TestCase;

final class DefaultRatePolicyTest extends TestCase
{
    public function testReturnsLowRateWhenIncomeBelowThreshold(): void
    {
        $policy = new DefaultRatePolicy(5_000_00, 0.93, 1.02, 0.5);
        $this->assertSame(0.93, $policy->getRate(4_999_99));
    }

    public function testReturnsHighRateWhenIncomeAtThreshold(): void
    {
        $policy = new DefaultRatePolicy(5_000_00, 0.93, 1.02, 0.5);
        $this->assertSame(1.02, $policy->getRate(5_000_00));
    }

    public function testReturnsHighRateWhenIncomeAboveThreshold(): void
    {
        $policy = new DefaultRatePolicy(5_000_00, 0.93, 1.02, 0.5);
        $this->assertSame(1.02, $policy->getRate(10_000_00));
    }

    public function testReturnsConfiguredFallbackRate(): void
    {
        $policy = new DefaultRatePolicy(5_000_00, 0.93, 1.02, 0.75);
        $this->assertSame(0.75, $policy->getFallbackRate());
    }
}
