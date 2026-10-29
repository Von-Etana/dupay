<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_connections', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("token");
            $table->enum('encrypt_token', ['0','1'])->default('1');
            $table->text("payload")->nullable();
            $table->text("url");
            $table->text("header")->nullable();
            $table->enum('header_type', ['0','1'])->default('1');
            $table->enum('method', ['0','1'])->default('1');
            $table->text("success_field");
            $table->text("success_response");
            $table->enum('api_auth_method', ['0','1'])->default('1');
            $table->unsignedBigInteger('auth_api_connection_id')->nullable();
            $table->enum('auto_refund', ['0','1'])->default('1');
            $table->enum('status', ['0','1'])->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_connections');
    }
};
