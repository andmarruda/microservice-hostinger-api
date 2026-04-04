<?php

namespace App\Modules\AuthModule\Tests\Unit;

use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Ports\Repositories\AuthRepositoryInterface;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;
use App\Modules\AuthModule\UseCases\LoginUser\LoginUser;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LoginUserTest extends TestCase
{
    private MockInterface $auth;
    private MockInterface $auditLogger;
    private LoginUser $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = Mockery::mock(AuthRepositoryInterface::class);
        $this->auditLogger = Mockery::mock(AuditLoggerInterface::class);

        $this->useCase = new LoginUser($this->auth, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_success_with_valid_credentials(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'user@example.com';

        $this->auth->shouldReceive('findByCredentials')->with('user@example.com', 'password')->once()->andReturn($user);
        $this->auditLogger->shouldReceive('logLoginSucceeded')->once();

        $result = $this->useCase->execute('user@example.com', 'password');

        $this->assertTrue($result->success);
        $this->assertSame($user, $result->user);
        $this->assertNull($result->token);
    }

    public function test_returns_invalid_credentials_when_user_not_found(): void
    {
        $this->auth->shouldReceive('findByCredentials')->andReturn(null);
        $this->auditLogger->shouldReceive('logLoginFailed')->once();

        $result = $this->useCase->execute('bad@example.com', 'wrongpassword');

        $this->assertFalse($result->success);
        $this->assertEquals('invalid_credentials', $result->error);
    }

    public function test_logs_failed_login_on_invalid_credentials(): void
    {
        $this->auth->shouldReceive('findByCredentials')->andReturn(null);
        $this->auditLogger->shouldReceive('logLoginFailed')
            ->withArgs(function ($email) {
                return $email === 'bad@example.com';
            })
            ->once();

        $result = $this->useCase->execute('bad@example.com', 'wrongpassword');

        $this->assertFalse($result->success);
    }

    public function test_logs_successful_login(): void
    {
        $user = new User();
        $user->id = 1;

        $this->auth->shouldReceive('findByCredentials')->andReturn($user);
        $this->auditLogger->shouldReceive('logLoginSucceeded')
            ->withArgs(function ($loggedUser, $method) {
                return $method === 'session';
            })
            ->once();

        $result = $this->useCase->execute('user@example.com', 'password', false);

        $this->assertTrue($result->success);
    }
}
