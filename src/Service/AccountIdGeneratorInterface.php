<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Service;

interface AccountIdGeneratorInterface
{
    /**
     * @return string
     */
    public function generate(): string;
}
