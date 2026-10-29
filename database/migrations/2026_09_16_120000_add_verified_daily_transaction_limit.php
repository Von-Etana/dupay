<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the higher daily limit that applies once a user's wallet KYC is approved.
 *
 * Both columns default to "-" (unlimited), which is exactly how verified users
 * behaved before this change — they were skipped by the limit check entirely.
 * Deploying this therefore changes nothing on its own; set a real figure on the
 * account levels (or on individual users) to switch the tier on.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'verified_daily_transaction_limit')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('verified_daily_transaction_limit')->nullable()->after('daily_transaction_limit');
            });
        }

        if (!Schema::hasColumn('user_levels', 'verified_daily_transaction_limit')) {
            Schema::table('user_levels', function (Blueprint $table) {
                $table->string('verified_daily_transaction_limit')->default('-')->after('daily_transaction_limit');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'verified_daily_transaction_limit')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('verified_daily_transaction_limit');
            });
        }

        if (Schema::hasColumn('user_levels', 'verified_daily_transaction_limit')) {
            Schema::table('user_levels', function (Blueprint $table) {
                $table->dropColumn('verified_daily_transaction_limit');
            });
        }
    }
};
