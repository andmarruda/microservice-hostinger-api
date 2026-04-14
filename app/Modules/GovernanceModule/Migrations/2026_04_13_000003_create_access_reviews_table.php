<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'period_start']);
        });

        Schema::create('access_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('access_reviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('vps_id');
            $table->string('decision')->nullable(); // approved, revoked
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['review_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_review_items');
        Schema::dropIfExists('access_reviews');
    }
};
