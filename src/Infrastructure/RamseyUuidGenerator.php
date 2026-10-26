<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Infrastructure;

use Robinson\InterestAccount\Service\AccountIdGeneratorInterface;
use Ramsey\Uuid\Uuid;

final class RamseyUuidGenerator implements AccountIdGeneratorInterface
{
    /**
     * @return string
     */
    public function generate(): string
    {
        return Uuid::uuid4()->toString();
    }
}
