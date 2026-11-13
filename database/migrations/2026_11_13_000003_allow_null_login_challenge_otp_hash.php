<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('login_challenges')) {
            DB::statement('ALTER TABLE login_challenges MODIFY otp_hash VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        // A challenge without a sent OTP legitimately has a null hash.
        // Reinstating NOT NULL would break login and discard that state.
    }
};
