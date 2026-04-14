<?php

namespace App\Modules\DriftModule\Tests\Feature;

use App\Modules\DriftModule\Jobs\RunDriftScan;
use App\Modules\DriftModule\Models\DriftReport;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\VpsModule\Factories\VpsAccessGrantFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RunDriftScanTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function client(array $liveVps): HostingerProxyClientInterface
    {
        $mock = Mockery::mock(HostingerProxyClientInterface::class);
        $mock->shouldReceive('getVpsList')->andReturn($liveVps);
        return $mock;
    }

    public function test_creates_orphan_grant_report_when_vps_no_longer_exists(): void
    {
        VpsAccessGrantFactory::new()->forVps('vps-gone')->create();

        (new RunDriftScan())->handle($this->client([['id' => 'vps-alive']]));

        $this->assertDatabaseHas('drift_reports', [
            'drift_type' => 'orphan_grant',
            'vps_id'     => 'vps-gone',
            'status'     => 'open',
        ]);
    }

    public function test_does_not_create_report_when_vps_still_exists(): void
    {
        VpsAccessGrantFactory::new()->forVps('vps-alive')->create();

        (new RunDriftScan())->handle($this->client([['id' => 'vps-alive']]));

        $this->assertDatabaseCount('drift_reports', 0);
    }

    public function test_does_not_duplicate_open_reports(): void
    {
        VpsAccessGrantFactory::new()->forVps('vps-gone')->create();

        (new RunDriftScan())->handle($this->client([['id' => 'vps-alive']]));
        (new RunDriftScan())->handle($this->client([['id' => 'vps-alive']]));

        // First scan creates: orphan_grant (vps-gone not in live list)
        //                   + undiscovered_vps (vps-alive in live list but has no grant)
        // Second scan must not duplicate either report — both already open.
        $this->assertDatabaseCount('drift_reports', 2);
        $this->assertDatabaseHas('drift_reports', ['drift_type' => 'orphan_grant',      'vps_id' => 'vps-gone',  'status' => 'open']);
        $this->assertDatabaseHas('drift_reports', ['drift_type' => 'undiscovered_vps',  'vps_id' => 'vps-alive', 'status' => 'open']);
    }

    public function test_does_nothing_when_hostinger_call_fails(): void
    {
        VpsAccessGrantFactory::new()->forVps('vps-1')->create();

        $client = Mockery::mock(HostingerProxyClientInterface::class);
        $client->shouldReceive('getVpsList')->andThrow(new \RuntimeException('timeout'));

        (new RunDriftScan())->handle($client);

        $this->assertDatabaseCount('drift_reports', 0);
    }
}
