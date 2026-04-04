<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('vps_id');
            $table->boolean('can_manage_firewall')->default(false);
            $table->boolean('can_manage_ssh_keys')->default(false);
            $table->boolean('can_manage_snapshots')->default(false);
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'vps_id']);
            $table->index('vps_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_permissions');
    }
};
