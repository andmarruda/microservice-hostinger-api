<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drift_reports', function (Blueprint $table) {
            $table->id();
            $table->string('drift_type');
            $table->string('severity')->default('medium');
            $table->string('vps_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('details');
            $table->string('status')->default('open');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'detected_at']);
            $table->index('drift_type');
            $table->index('vps_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drift_reports');
    }
};
