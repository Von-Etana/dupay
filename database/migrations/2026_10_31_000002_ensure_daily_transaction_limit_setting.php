<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ensures there are admin-editable master switches for:
 *   1. limit_daily_transaction: Daily Transaction Limit across all services
 *   2. limit_daily_transfer: Daily Transfer Limit for fund transfers
 *
 * This migration is idempotent: if the rows already exist, it leaves them untouched.
 */
return new class extends Migration
{
    public function up()
    {
        $settings = [
            [
                'name'         => 'Limit Daily Transaction',
                'description'  => 'Enable daily transaction limit across all payment and debit services',
                'input_type'   => '5', // 5 = radio button
                'column_name'  => 'limit_daily_transaction',
                'value'        => 'Yes',
                'input_option' => 'Yes,No',
                'group_type'   => '3',
                'required'     => 'required',
                'rule'         => '',
                'model'        => 'BasicSetting',
            ],
            [
                'name'         => 'Limit Daily Transfer',
                'description'  => 'Enable daily fund transfer limit',
                'input_type'   => '5', // 5 = radio button
                'column_name'  => 'limit_daily_transfer',
                'value'        => 'Yes',
                'input_option' => 'Yes,No',
                'group_type'   => '3',
                'required'     => 'required',
                'rule'         => '',
                'model'        => 'BasicSetting',
            ],
        ];

        foreach ($settings as $setting) {
            $exists = DB::table('basic_settings')
                ->where('column_name', $setting['column_name'])
                ->exists();

            if (!$exists) {
                DB::table('basic_settings')->insert(array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down()
    {
        DB::table('basic_settings')
            ->whereIn('column_name', ['limit_daily_transaction', 'limit_daily_transfer'])
            ->delete();
    }
};
