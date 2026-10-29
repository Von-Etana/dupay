<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('money_request_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->char('endpoint_hash', 64);
            $table->char('key_hash', 64);
            $table->char('payload_hash', 64);
            $table->timestamp('created_at');
            $table->unique(['user_id', 'endpoint_hash', 'key_hash'], 'money_request_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('money_request_keys');
    }
};
