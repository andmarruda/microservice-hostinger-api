<?php

namespace App\Modules\PolicyModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\PolicyModule\Factories\EnforcementPolicyFactory;
use App\Modules\PolicyModule\Infrastructure\Services\DatabasePolicyEnforcer;
use App\Modules\PolicyModule\PolicyActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabasePolicyEnforcerTest extends TestCase
{
    use RefreshDatabase;

    private DatabasePolicyEnforcer $enforcer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enforcer = new DatabasePolicyEnforcer();
    }

    public function test_allows_when_no_policies_exist(): void
    {
        $user = User::factory()->create();

        $decision = $this->enforcer->evaluate(PolicyActions::VPS_START, $user->id, 'vps-1');

        $this->assertTrue($decision->allowed);
    }

    public function test_denies_user_not_found(): void
    {
        $decision = $this->enforcer->evaluate(PolicyActions::VPS_START, 9999, 'vps-1');

        $this->assertFalse($decision->allowed);
        $this->assertSame('User not found.', $decision->reason);
    }

    public function test_root_role_is_exempt_from_global_deny(): void
    {
        $root = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'root', 'guard_name' => 'web']);
        $root->assignRole($role);

        EnforcementPolicyFactory::new()
            ->forAction(PolicyActions::VPS_START)
            ->create(['created_by' => $root->id]);

        $decision = $this->enforcer->evaluate(PolicyActions::VPS_START, $root->id, 'vps-1');

        $this->assertTrue($decision->allowed);
    }

    public function test_global_deny_blocks_regular_user(): void
    {
        $creator = User::factory()->create();
        $user    = User::factory()->create();

        EnforcementPolicyFactory::new()
            ->forAction(PolicyActions::VPS_STOP)
            ->create(['scope_type' => 'global', 'created_by' => $creator->id]);

        $decision = $this->enforcer->evaluate(PolicyActions::VPS_STOP, $user->id, 'vps-1');

        $this->assertFalse($decision->allowed);
    }

    public function test_user_scoped_deny_blocks_only_that_user(): void
    {
        $creator = User::factory()->create();
        $userA   = User::factory()->create();
        $userB   = User::factory()->create();

        EnforcementPolicyFactory::new()
            ->forAction(PolicyActions::VPS_REBOOT)
            ->forUser($userA->id)
            ->create(['created_by' => $creator->id]);

        $this->assertFalse($this->enforcer->evaluate(PolicyActions::VPS_REBOOT, $userA->id, 'vps-1')->allowed);
        $this->assertTrue($this->enforcer->evaluate(PolicyActions::VPS_REBOOT, $userB->id, 'vps-1')->allowed);
    }

    public function test_vps_scoped_deny_blocks_only_that_vps(): void
    {
        $creator = User::factory()->create();
        $user    = User::factory()->create();

        EnforcementPolicyFactory::new()
            ->forAction(PolicyActions::SSH_KEY_ADD)
            ->forVps('vps-locked')
            ->create(['created_by' => $creator->id]);

        $this->assertFalse($this->enforcer->evaluate(PolicyActions::SSH_KEY_ADD, $user->id, 'vps-locked')->allowed);
        $this->assertTrue($this->enforcer->evaluate(PolicyActions::SSH_KEY_ADD, $user->id, 'vps-other')->allowed);
    }

    public function test_role_scoped_deny_blocks_users_with_that_role(): void
    {
        $creator = User::factory()->create();
        $user    = User::factory()->create();
        $role    = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        $user->assignRole($role);

        EnforcementPolicyFactory::new()
            ->forAction(PolicyActions::SNAPSHOT_CREATE)
            ->forRole('operator')
            ->create(['created_by' => $creator->id]);

        $this->assertFalse($this->enforcer->evaluate(PolicyActions::SNAPSHOT_CREATE, $user->id, 'vps-1')->allowed);
    }

    public function test_expired_policy_does_not_block(): void
    {
        $creator = User::factory()->create();
        $user    = User::factory()->create();

        EnforcementPolicyFactory::new()
            ->forAction(PolicyActions::VPS_START)
            ->create([
                'scope_type'  => 'global',
                'created_by'  => $creator->id,
                'active_from' => now()->subDays(10),
                'active_until' => now()->subDay(),
            ]);

        $this->assertTrue($this->enforcer->evaluate(PolicyActions::VPS_START, $user->id, 'vps-1')->allowed);
    }

    public function test_future_policy_does_not_block_yet(): void
    {
        $creator = User::factory()->create();
        $user    = User::factory()->create();

        EnforcementPolicyFactory::new()
            ->forAction(PolicyActions::VPS_START)
            ->create([
                'scope_type' => 'global',
                'created_by' => $creator->id,
                'active_from' => now()->addDay(),
            ]);

        $this->assertTrue($this->enforcer->evaluate(PolicyActions::VPS_START, $user->id, 'vps-1')->allowed);
    }

    public function test_active_policy_within_window_blocks(): void
    {
        $creator = User::factory()->create();
        $user    = User::factory()->create();

        EnforcementPolicyFactory::new()
            ->forAction(PolicyActions::VPS_STOP)
            ->create([
                'scope_type'   => 'global',
                'created_by'   => $creator->id,
                'active_from'  => now()->subHour(),
                'active_until' => now()->addHour(),
            ]);

        $this->assertFalse($this->enforcer->evaluate(PolicyActions::VPS_STOP, $user->id, 'vps-1')->allowed);
    }
}
