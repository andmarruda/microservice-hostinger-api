<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vps_access_grants', function (Blueprint $table) {
            $table->timestamp('stale_at')->nullable()->after('granted_at');
        });
    }

    public function down(): void
    {
        Schema::table('vps_access_grants', function (Blueprint $table) {
            $table->dropColumn('stale_at');
        });
    }
};
