<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('title');
            $table->text('message');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamps();

            $table->index(['severity', 'acknowledged_at']);
            $table->index(['type', 'created_at']);
        });

        if (Schema::hasTable('permissions')) {
            foreach ([
                ['name' => 'View Security Alerts', 'key' => 'view_security_alerts'],
                ['name' => 'Acknowledge Security Alerts', 'key' => 'manage_security_alerts'],
            ] as $permission) {
                if (! DB::table('permissions')->where('key', $permission['key'])->exists()) {
                    DB::table('permissions')->insert([
                        'name' => $permission['name'],
                        'key' => $permission['key'],
                        'module_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
    }
};
