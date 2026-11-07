<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'password_failed_attempts')) {
                $table->unsignedInteger('password_failed_attempts')->default(0);
            }
            if (!Schema::hasColumn('users', 'password_locked_until')) {
                $table->timestamp('password_locked_until')->nullable();
            }
            if (!Schema::hasColumn('users', 'otp_failed_attempts')) {
                $table->unsignedInteger('otp_failed_attempts')->default(0);
            }
            if (!Schema::hasColumn('users', 'otp_locked_until')) {
                $table->timestamp('otp_locked_until')->nullable();
            }
        });

        if (!Schema::hasTable('security_events')) {
            Schema::create('security_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event', 64);
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['user_id', 'created_at']);
                $table->index(['event', 'created_at']);
            });
        }

        if (!Schema::hasTable('trusted_devices')) {
            Schema::create('trusted_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('token_hash', 64)->unique();
                $table->string('name')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('trusted_devices');
        Schema::dropIfExists('security_events');
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            foreach (['password_failed_attempts', 'password_locked_until', 'otp_failed_attempts', 'otp_locked_until'] as $c) {
                if (Schema::hasColumn('users', $c)) {
                    $cols[] = $c;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
