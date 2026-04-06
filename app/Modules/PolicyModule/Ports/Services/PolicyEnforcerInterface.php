<?php

namespace App\Modules\PolicyModule\Ports\Services;

interface PolicyEnforcerInterface
{
    public function evaluate(string $action, int $userId, ?string $vpsId = null): PolicyDecision;
}
