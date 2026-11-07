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
            $table->timestamp('login_otp_sent_at')->nullable();
            $table->unsignedInteger('security_session_version')->default(0);
        });
        Schema::table('trusted_devices', function (Blueprint $table) {
            $table->char('device_hash', 64)->nullable();
            $table->unique(['user_id', 'device_hash']);
        });
        Schema::table('security_events', function (Blueprint $table) {
            $table->char('device_hash', 64)->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('reason')->nullable();
        });
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
        Schema::table('security_events', fn (Blueprint $table) => $table->dropColumn(['device_hash', 'admin_id', 'reason']));
        Schema::table('trusted_devices', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'device_hash']);
            $table->dropColumn('device_hash');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['login_otp_sent_at', 'security_session_version']));
    }
};
