<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('money_request_keys', function (Blueprint $table) {
            $table->string('status', 20)->default('processing');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('money_request_keys', function (Blueprint $table) {
            $table->dropColumn(['status', 'http_status', 'response_body', 'completed_at']);
        });
    }
};
