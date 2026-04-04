<?php

namespace App\Modules\SecurityResourceModule\UseCases\AddFirewallRule;

class AddFirewallRuleResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?string $correlationId,
        public readonly ?string $validationMessage,
    ) {}

    public static function success(string $correlationId): self
    {
        return new self(true, null, $correlationId, null);
    }

    public static function forbidden(): self
    {
        return new self(false, 'forbidden', null, null);
    }

    public static function invalidRule(string $message): self
    {
        return new self(false, 'invalid_rule', null, $message);
    }

    public static function hostingerError(string $correlationId): self
    {
        return new self(false, 'hostinger_error', $correlationId, null);
    }
}
