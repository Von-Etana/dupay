<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registers the ATM dispute permissions so the page can be granted to staff
 * roles from Admin > Roles, the same way Physical Cards is handled.
 *
 * AuthServiceProvider turns every row in `permissions` into a Gate, and admin
 * id 1 always passes, so the super admin sees the page immediately. Other
 * staff need the permission ticked on their role.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('modules')) {
            return;
        }

        // Reuse the existing card module if there is one, otherwise create it.
        $module = DB::table('modules')
            ->where('name', 'like', '%Card%')
            ->orderBy('id')
            ->first();

        if (! $module) {
            $moduleId = DB::table('modules')->insertGetId([
                'name'         => 'Cards',
                'module'       => 'cards',
                'general_view' => '1',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } else {
            $moduleId = $module->id;
        }

        $permissions = [
            'view_card_disputes'   => 'View ATM Disputes',
            'create_card_disputes' => 'Create ATM Dispute',
            'delete_card_disputes' => 'Delete ATM Dispute',
        ];

        foreach ($permissions as $key => $name) {
            $exists = DB::table('permissions')->where('key', $key)->exists();

            if ($exists) {
                continue;
            }

            DB::table('permissions')->insert([
                'module_id'  => $moduleId,
                'name'       => $name,
                'key'        => $key,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $keys = ['view_card_disputes', 'create_card_disputes', 'delete_card_disputes'];

        $ids = DB::table('permissions')->whereIn('key', $keys)->pluck('id');

        if (Schema::hasTable('role_permissions') && $ids->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        }

        DB::table('permissions')->whereIn('key', $keys)->delete();
    }
};
