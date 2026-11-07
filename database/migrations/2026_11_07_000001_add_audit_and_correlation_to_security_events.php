<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_events')) {
            Schema::table('security_events', function (Blueprint $table) {
                if (!Schema::hasColumn('security_events', 'admin_id')) {
                    $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete()->after('user_id');
                }
                if (!Schema::hasColumn('security_events', 'device_hash')) {
                    $table->string('device_hash', 64)->nullable()->after('user_agent');
                }
                if (!Schema::hasColumn('security_events', 'reason')) {
                    $table->string('reason', 255)->nullable()->after('device_hash');
                }
                if (!Schema::hasColumn('security_events', 'request_id')) {
                    $table->string('request_id', 64)->nullable()->index()->after('reason');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('security_events')) {
            Schema::table('security_events', function (Blueprint $table) {
                $columns = [];
                foreach (['admin_id', 'device_hash', 'reason', 'request_id'] as $col) {
                    if (Schema::hasColumn('security_events', $col)) {
                        $columns[] = $col;
                    }
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
