<?php

namespace Robinson\InterestAccount\Tests;

use Robinson\InterestAccount\Application\InterestAccountConfig;
use Robinson\InterestAccount\Repository\DefaultRatePolicy;

final class PolicyFactory
{
    public static function default(): DefaultRatePolicy
    {
        $config = new InterestAccountConfig();
        return new DefaultRatePolicy(
            $config->thresholdPennies,
            $config->lowRate,
            $config->highRate,
            $config->fallbackRate,
        );
    }
}