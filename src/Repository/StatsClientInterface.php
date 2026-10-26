<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Repository;

interface StatsClientInterface
{
    /**
     * Get the annual interest rate for a given user.
     *
     * Implementations MUST always return a rate, even when the remote
     * service fails or the user is unknown. In failure/unknown cases,
     * a sensible fallback rate MUST be returned instead of throwing.
     *
     * @param string $userId Identifier of the user whose rate is requested.
     *
     * @return float The interest rate.
     */
    public function getInterestRateForUser(string $userId): float;
}
