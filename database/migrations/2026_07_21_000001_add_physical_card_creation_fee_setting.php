<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ensures there is an admin-editable "Physical Card Creation Fee" field in
 * Basic/General Settings. The amount is what a user is debited when they
 * request a physical ATM card (see PhysicalCardController@store).
 *
 * This migration is safe to run more than once: it only inserts the row when
 * it is missing, and it places the new field on the SAME settings page/group
 * as the existing card-charge settings (e.g. naira_card_charge) so admins find
 * it next to the other card fees.
 */
return new class extends Migration
{
    public function up()
    {
        $column = 'physical_card_creation_fee';

        // Already there? Do nothing.
        $exists = DB::table('basic_settings')->where('column_name', $column)->exists();
        if ($exists) {
            return;
        }

        // Copy the page grouping (model + group_type) from an existing card
        // charge setting so this field shows on the same admin page. Fall back
        // to sensible defaults if none of those rows exist yet.
        $reference = DB::table('basic_settings')
            ->whereIn('column_name', ['naira_card_charge', 'dollar_card_charge', 'title'])
            ->first();

        DB::table('basic_settings')->insert([
            'name'         => 'Physical Card Creation Fee',
            'description'  => 'Amount to debit a user when they request a physical ATM card',
            'input_type'   => '0', // 0 = text input
            'column_name'  => $column,
            'value'        => '1000',
            'input_option' => null,
            'group_type'   => $reference->group_type ?? '3',
            'required'     => '',
            'rule'         => 'nullable|numeric',
            'model'        => $reference->model ?? 'BasicSetting',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down()
    {
        DB::table('basic_settings')
            ->where('column_name', 'physical_card_creation_fee')
            ->delete();
    }
};
