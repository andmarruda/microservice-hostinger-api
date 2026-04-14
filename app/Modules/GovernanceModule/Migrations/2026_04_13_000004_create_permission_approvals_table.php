<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission');
            $table->string('vps_id')->nullable();
            $table->string('status')->default('pending'); // pending, approved, denied
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('target_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_approvals');
    }
};
