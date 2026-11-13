<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('login_challenges')) {
            DB::statement('ALTER TABLE login_challenges MODIFY id VARCHAR(64) NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('login_challenges')) {
            DB::statement('ALTER TABLE login_challenges MODIFY id CHAR(36) NOT NULL');
        }
    }
};
