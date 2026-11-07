<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'login_otp_sent_at')) {
                $table->timestamp('login_otp_sent_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'security_session_version')) {
                $table->unsignedInteger('security_session_version')->default(0);
            }
        });

        if (Schema::hasTable('trusted_devices')) {
            Schema::table('trusted_devices', function (Blueprint $table) {
                if (!Schema::hasColumn('trusted_devices', 'device_hash')) {
                    $table->char('device_hash', 64)->nullable();
                    $table->unique(['user_id', 'device_hash']);
                }
            });
        }

        if (Schema::hasTable('security_events')) {
            Schema::table('security_events', function (Blueprint $table) {
                if (!Schema::hasColumn('security_events', 'device_hash')) {
                    $table->char('device_hash', 64)->nullable();
                }
                if (!Schema::hasColumn('security_events', 'admin_id')) {
                    $table->unsignedBigInteger('admin_id')->nullable();
                }
                if (!Schema::hasColumn('security_events', 'reason')) {
                    $table->text('reason')->nullable();
                }
            });
        }

        if (!Schema::hasTable('login_challenges')) {
            Schema::create('login_challenges', function (Blueprint $table) {
                $table->char('id', 64)->primary();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('purpose', 8)->default('api');
                $table->char('device_hash', 64);
                $table->string('otp_hash')->nullable();
                $table->char('password_fingerprint', 64);
                $table->timestamp('expires_at');
                $table->timestamp('otp_expires_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('consumed_at')->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamps();
                $table->index(['user_id', 'consumed_at']);
            });
        }

        if (Schema::hasTable('permissions')) {
            foreach (['view_user_security' => 'View user security', 'unlock_user_security' => 'Unlock user accounts', 'revoke_user_sessions' => 'Revoke user sessions', 'export_user_security' => 'Export security audit'] as $key => $name) {
                if (!DB::table('permissions')->where('key', $key)->exists()) {
                    DB::table('permissions')->insert(['name' => $name, 'key' => $key, 'module_id' => null, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('login_challenges');
        if (Schema::hasTable('security_events')) {
            Schema::table('security_events', function (Blueprint $table) {
                $cols = [];
                foreach (['device_hash', 'admin_id', 'reason'] as $col) {
                    if (Schema::hasColumn('security_events', $col)) {
                        $cols[] = $col;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
        if (Schema::hasTable('trusted_devices')) {
            Schema::table('trusted_devices', function (Blueprint $table) {
                if (Schema::hasColumn('trusted_devices', 'device_hash')) {
                    $table->dropUnique(['user_id', 'device_hash']);
                    $table->dropColumn('device_hash');
                }
            });
        }
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            foreach (['login_otp_sent_at', 'security_session_version'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
