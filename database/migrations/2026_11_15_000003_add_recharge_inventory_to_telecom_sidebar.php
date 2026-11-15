<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_side_bars')) return;

        $parent = DB::table('admin_side_bars')
            ->whereNull('parent_id')
            ->whereRaw('LOWER(name) like ?', ['%telecom%'])
            ->first();
        if (!$parent) return;

        $exists = DB::table('admin_side_bars')
            ->where('route', 'admin.recharge_card_pins.index')
            ->exists();
        if ($exists) return;

        $columns = Schema::getColumnListing('admin_side_bars');
        $row = [
            'name' => 'Recharge Card PIN Inventory',
            'other_name' => 'Recharge Card PIN Inventory',
            'model' => 'recharge_card_pins',
            'route' => 'admin.recharge_card_pins.index',
            'order' => '999',
            'parent_id' => $parent->id,
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        foreach (['use_route', 'is_menu', 'can_view', 'can_add'] as $column) {
            if (in_array($column, $columns, true)) $row[$column] = $column === 'use_route' ? '1' : '0';
        }
        if (in_array('rule', $columns, true)) $row['rule'] = 'view_recharge_card_epin_rates';
        DB::table('admin_side_bars')->insert($row);
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_side_bars')) {
            DB::table('admin_side_bars')->where('route', 'admin.recharge_card_pins.index')->delete();
        }
    }
};
