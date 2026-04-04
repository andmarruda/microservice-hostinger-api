<?php

namespace App\Modules\SecurityResourceModule\UseCases\AddSshKey;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use Illuminate\Support\Str;

class AddSshKey
{
    private const VALID_KEY_PREFIXES = ['ssh-rsa ', 'ssh-ed25519 ', 'ecdsa-sha2-nistp256 '];

    public function __construct(
        private SecurityPermissionInterface $permissions,
        private HostingerSecurityApiClientInterface $hostinger,
        private InfraAuditLoggerInterface $auditLogger,
    ) {}

    public function execute(int $userId, string $vpsId, string $keyName, string $publicKey, ?string $actorEmail = null, ?string $ipAddress = null, ?string $userAgent = null): AddSshKeyResult
    {
        if (!$this->permissions->canManageSshKeys($userId, $vpsId)) {
            return AddSshKeyResult::forbidden();
        }

        if (!$this->isValidPublicKey($publicKey)) {
            return AddSshKeyResult::invalidKey('Public key must start with ssh-rsa, ssh-ed25519, or ecdsa-sha2-nistp256.');
        }

        $correlationId = (string) Str::uuid();

        $apiResult = $this->hostinger->addSshKey($vpsId, $keyName, $publicKey, $correlationId);

        $this->auditLogger->logAction(
            action: 'ssh_key_add',
            actorId: $userId,
            actorEmail: $actorEmail,
            vpsId: $vpsId,
            resourceType: 'ssh_key',
            resourceId: null,
            correlationId: $correlationId,
            outcome: $apiResult->success ? 'success' : 'failure',
            metadata: $apiResult->errorMessage ? ['error' => $apiResult->errorMessage] : ['key_name' => $keyName],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        if (!$apiResult->success) {
            return AddSshKeyResult::hostingerError($correlationId);
        }

        return AddSshKeyResult::success($correlationId);
    }

    private function isValidPublicKey(string $publicKey): bool
    {
        foreach (self::VALID_KEY_PREFIXES as $prefix) {
            if (str_starts_with($publicKey, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
