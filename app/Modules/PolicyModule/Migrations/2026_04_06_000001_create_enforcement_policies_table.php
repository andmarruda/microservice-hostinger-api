<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enforcement_policies', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('scope_type')->default('global'); // global, vps, role, user
            $table->string('scope_id')->nullable();           // vpsId | role name | userId
            $table->string('effect')->default('deny');
            $table->string('reason')->nullable();
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['action', 'scope_type']);
            $table->index('active_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enforcement_policies');
    }
};
