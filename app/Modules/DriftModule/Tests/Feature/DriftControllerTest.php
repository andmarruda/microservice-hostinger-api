<?php

namespace App\Modules\DriftModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\DriftModule\Factories\DriftReportFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriftControllerTest extends TestCase
{
    use RefreshDatabase;

    private function rootUser(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'root', 'guard_name' => 'web']);
        $user->assignRole($role);
        return $user;
    }

    // ── GET /api/v1/drift ─────────────────────────────────────────────────────

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/drift');
        $response->assertStatus(401);
    }

    public function test_index_returns_403_for_non_root(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->getJson('/api/v1/drift');
        $response->assertStatus(403);
    }

    public function test_index_returns_all_reports_for_root(): void
    {
        $root = $this->rootUser();
        DriftReportFactory::new()->open()->count(2)->create();
        DriftReportFactory::new()->resolved()->count(1)->create();

        Sanctum::actingAs($root);
        $response = $this->getJson('/api/v1/drift');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_index_filters_by_status(): void
    {
        $root = $this->rootUser();
        DriftReportFactory::new()->open()->count(2)->create();
        DriftReportFactory::new()->resolved()->count(1)->create();

        Sanctum::actingAs($root);
        $response = $this->getJson('/api/v1/drift?status=open');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_filters_by_severity(): void
    {
        $root = $this->rootUser();
        DriftReportFactory::new()->highSeverity()->count(1)->create();
        DriftReportFactory::new()->open()->count(2)->create(['severity' => 'low']);

        Sanctum::actingAs($root);
        $response = $this->getJson('/api/v1/drift?severity=high');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    // ── POST /api/v1/drift/{id}/resolve ──────────────────────────────────────

    public function test_resolve_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/drift/1/resolve');
        $response->assertStatus(401);
    }

    public function test_resolve_returns_403_for_non_root(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $report = DriftReportFactory::new()->open()->create();

        $response = $this->postJson("/api/v1/drift/{$report->id}/resolve");
        $response->assertStatus(403);
    }

    public function test_resolve_marks_report_resolved(): void
    {
        $root   = $this->rootUser();
        $report = DriftReportFactory::new()->open()->create();

        Sanctum::actingAs($root);
        $response = $this->postJson("/api/v1/drift/{$report->id}/resolve");

        $response->assertStatus(200);
        $response->assertJson(['data' => ['resolved' => true]]);
        $this->assertDatabaseHas('drift_reports', ['id' => $report->id, 'status' => 'resolved']);
    }

    public function test_resolve_returns_404_for_missing_report(): void
    {
        $root = $this->rootUser();
        Sanctum::actingAs($root);

        $response = $this->postJson('/api/v1/drift/9999/resolve');
        $response->assertStatus(404);
    }

    public function test_resolve_returns_409_when_already_closed(): void
    {
        $root   = $this->rootUser();
        $report = DriftReportFactory::new()->resolved()->create();

        Sanctum::actingAs($root);
        $response = $this->postJson("/api/v1/drift/{$report->id}/resolve");
        $response->assertStatus(409);
    }

    // ── POST /api/v1/drift/{id}/dismiss ──────────────────────────────────────

    public function test_dismiss_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/drift/1/dismiss');
        $response->assertStatus(401);
    }

    public function test_dismiss_returns_403_for_non_root(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $report = DriftReportFactory::new()->open()->create();

        $response = $this->postJson("/api/v1/drift/{$report->id}/dismiss");
        $response->assertStatus(403);
    }

    public function test_dismiss_marks_report_dismissed(): void
    {
        $root   = $this->rootUser();
        $report = DriftReportFactory::new()->open()->create();

        Sanctum::actingAs($root);
        $response = $this->postJson("/api/v1/drift/{$report->id}/dismiss");

        $response->assertStatus(200);
        $response->assertJson(['data' => ['dismissed' => true]]);
        $this->assertDatabaseHas('drift_reports', ['id' => $report->id, 'status' => 'dismissed']);
    }

    public function test_dismiss_returns_404_for_missing_report(): void
    {
        $root = $this->rootUser();
        Sanctum::actingAs($root);

        $response = $this->postJson('/api/v1/drift/9999/dismiss');
        $response->assertStatus(404);
    }

    public function test_dismiss_returns_409_when_already_closed(): void
    {
        $root   = $this->rootUser();
        $report = DriftReportFactory::new()->dismissed()->create();

        Sanctum::actingAs($root);
        $response = $this->postJson("/api/v1/drift/{$report->id}/dismiss");
        $response->assertStatus(409);
    }
}
