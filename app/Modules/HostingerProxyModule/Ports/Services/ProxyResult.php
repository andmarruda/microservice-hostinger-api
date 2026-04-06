<?php

namespace App\Modules\HostingerProxyModule\Ports\Services;

class ProxyResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?array $data,
        public readonly ?string $error,
    ) {}

    public static function success(array $data): self
    {
        return new self(true, $data, null);
    }

    public static function forbidden(): self
    {
        return new self(false, null, 'forbidden');
    }

    public static function hostingerError(): self
    {
        return new self(false, null, 'hostinger_error');
    }
}
