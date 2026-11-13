<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('login_challenges')) {
            return;
        }

        Schema::table('login_challenges', function (Blueprint $table) {
            if (!Schema::hasColumn('login_challenges', 'purpose')) {
                $table->string('purpose', 16)->default('api');
            }
            if (!Schema::hasColumn('login_challenges', 'device_hash')) {
                $table->string('device_hash', 64)->nullable();
            }
            if (!Schema::hasColumn('login_challenges', 'password_fingerprint')) {
                $table->string('password_fingerprint', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Keep challenge data intact during rollback.
    }
};
