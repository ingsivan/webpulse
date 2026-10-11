<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->unique()->after('id');
            $table->boolean('is_down')->default(false)->after('check_interval_minutes');
            $table->index(['is_monitored', 'last_checked_at']);
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->unique()->after('id');
            $table->unsignedTinyInteger('progress')->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropUnique(['ulid']);
            $table->dropColumn(['ulid', 'progress']);
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropIndex(['is_monitored', 'last_checked_at']);
            $table->dropUnique(['ulid']);
            $table->dropColumn(['ulid', 'is_down']);
        });
    }
};
