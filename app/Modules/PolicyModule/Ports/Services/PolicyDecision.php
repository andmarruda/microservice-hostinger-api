<?php

namespace App\Modules\PolicyModule\Ports\Services;

class PolicyDecision
{
    private function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason,
    ) {}

    public static function allow(): self
    {
        return new self(true, null);
    }

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }
}
