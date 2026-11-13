<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('login_challenges') && !Schema::hasColumn('login_challenges', 'otp_expires_at')) {
            Schema::table('login_challenges', function (Blueprint $table) {
                $table->timestamp('otp_expires_at')->nullable()->after('expires_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('login_challenges') && Schema::hasColumn('login_challenges', 'otp_expires_at')) {
            Schema::table('login_challenges', function (Blueprint $table) {
                $table->dropColumn('otp_expires_at');
            });
        }
    }
};
