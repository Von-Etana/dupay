<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $columns = [
            'safehaven_review_ready_at' => fn (Blueprint $t) => $t->timestamp('safehaven_review_ready_at')->nullable(),
            'safehaven_identity_id' => fn (Blueprint $t) => $t->text('safehaven_identity_id')->nullable(),
            'safehaven_identity_method' => fn (Blueprint $t) => $t->string('safehaven_identity_method', 10)->nullable(),
            'safehaven_identity_number_hash' => fn (Blueprint $t) => $t->char('safehaven_identity_number_hash', 64)->nullable(),
            'safehaven_identity_initiated_at' => fn (Blueprint $t) => $t->timestamp('safehaven_identity_initiated_at')->nullable(),
            'safehaven_identity_expires_at' => fn (Blueprint $t) => $t->timestamp('safehaven_identity_expires_at')->nullable(),
            'safehaven_identity_attempts' => fn (Blueprint $t) => $t->unsignedTinyInteger('safehaven_identity_attempts')->default(0),
            'safehaven_identity_validated_at' => fn (Blueprint $t) => $t->timestamp('safehaven_identity_validated_at')->nullable(),
            'safehaven_external_reference' => fn (Blueprint $t) => $t->string('safehaven_external_reference', 64)->nullable(),
        ];
        foreach ($columns as $name => $definition) {
            if (!Schema::hasColumn('wallet_verificaitons', $name)) {
                Schema::table('wallet_verificaitons', $definition);
            }
        }
    }

    public function down(): void
    {
        $columns = array_keys([
            'safehaven_review_ready_at' => true, 'safehaven_identity_id' => true,
            'safehaven_identity_method' => true, 'safehaven_identity_number_hash' => true,
            'safehaven_identity_initiated_at' => true, 'safehaven_identity_expires_at' => true,
            'safehaven_identity_attempts' => true, 'safehaven_identity_validated_at' => true,
            'safehaven_external_reference' => true,
        ]);
        foreach ($columns as $name) {
            if (Schema::hasColumn('wallet_verificaitons', $name)) {
                Schema::table('wallet_verificaitons', fn (Blueprint $t) => $t->dropColumn($name));
            }
        }
    }
};
