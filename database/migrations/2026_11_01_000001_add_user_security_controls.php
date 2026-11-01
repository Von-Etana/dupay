<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'failed_login_attempts')) {
                    $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('blocked_status');
                }
                if (! Schema::hasColumn('users', 'login_locked_until')) {
                    $table->timestamp('login_locked_until')->nullable()->after('failed_login_attempts');
                }
                if (! Schema::hasColumn('users', 'otp_failed_attempts')) {
                    $table->unsignedTinyInteger('otp_failed_attempts')->default(0)->after('login_locked_until');
                }
                if (! Schema::hasColumn('users', 'otp_locked_until')) {
                    $table->timestamp('otp_locked_until')->nullable()->after('otp_failed_attempts');
                }
            });
        }

        if (! Schema::hasTable('user_devices')) {
            Schema::create('user_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_id_hash', 128);
                $table->string('user_agent', 512)->nullable();
                $table->ipAddress('last_ip')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'device_id_hash']);
                $table->index(['user_id', 'revoked_at']);
            });
        }

        if (! Schema::hasTable('security_events')) {
            Schema::create('security_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->string('event_type', 80);
                $table->string('severity', 20)->default('medium');
                $table->string('ip_address', 45)->nullable();
                $table->string('device_id_hash', 128)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['event_type', 'created_at']);
                $table->index(['severity', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('user_devices');
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                foreach (['failed_login_attempts', 'login_locked_until', 'otp_failed_attempts', 'otp_locked_until'] as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
