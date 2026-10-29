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
        Schema::create('withdrawal_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('bank_code')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string("wallet_id")->nullable();
            $table->string("amount")->nullable();
            $table->string("charge")->nullable();
            $table->string("referrence")->nullable();
            $table->string("comment")->nullable();
            $table->string("admin_comment")->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->enum('status', ['0','1', '2', '3', '4'])->default('0'); //0 pending //1 approve 2 processing 3 canceled 4 fail
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
        Schema::dropIfExists('withdrawl_logs');
    }
};
