<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new \RuntimeException('Legacy credential cleanup requires a reviewed MySQL migration.');
        }

        if (Schema::hasColumn('users', 's_password')) {
            DB::statement('ALTER TABLE `users` MODIFY `s_password` VARCHAR(255) NULL');
            DB::table('users')->update(['s_password' => null]);
        }

        if (Schema::hasColumn('admins', 's_password')) {
            DB::statement('ALTER TABLE `admins` MODIFY `s_password` VARCHAR(255) NULL');
            DB::table('admins')->update(['s_password' => null]);
        }

        if (Schema::hasColumn('admins', 's_pin')) {
            DB::statement('ALTER TABLE `admins` MODIFY `s_pin` VARCHAR(255) NULL');
            DB::table('admins')->update(['s_pin' => null]);
        }
    }

    public function down(): void
    {
        // Plaintext credentials are intentionally not restored.
    }
};
