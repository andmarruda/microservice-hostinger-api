<?php

namespace App\Modules\HostingerProxyModule\Tests\Unit;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\UseCases\GetBillingCatalog\GetBillingCatalog;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetBillingCatalogTest extends TestCase
{
    private MockInterface $client;
    private GetBillingCatalog $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(HostingerProxyClientInterface::class);
        $this->useCase = new GetBillingCatalog($this->client);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_catalog_when_user_has_permission(): void
    {
        $catalog = [['id' => 'plan-1', 'name' => 'Starter']];

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('Billing.getCatalog')->andReturn(true);

        $this->client->shouldReceive('getBillingCatalog')->once()->andReturn($catalog);

        $result = $this->useCase->execute($user);

        $this->assertTrue($result->success);
        $this->assertEquals($catalog, $result->data);
    }

    public function test_returns_forbidden_when_user_lacks_permission(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('Billing.getCatalog')->andReturn(false);

        $this->client->shouldNotReceive('getBillingCatalog');

        $result = $this->useCase->execute($user);

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
    }

    public function test_returns_hostinger_error_when_client_throws(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('Billing.getCatalog')->andReturn(true);

        $this->client->shouldReceive('getBillingCatalog')->once()->andThrow(new \RuntimeException('API error'));

        $result = $this->useCase->execute($user);

        $this->assertFalse($result->success);
        $this->assertEquals('hostinger_error', $result->error);
    }

    public function test_billing_is_not_cached(): void
    {
        // Billing must always hit the client (never cached), so calling twice should call client twice
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('Billing.getCatalog')->andReturn(true);

        $this->client->shouldReceive('getBillingCatalog')->twice()->andReturn([['id' => 'plan-1']]);

        $first = $this->useCase->execute($user);
        $second = $this->useCase->execute($user);

        $this->assertTrue($first->success);
        $this->assertTrue($second->success);
    }
}
