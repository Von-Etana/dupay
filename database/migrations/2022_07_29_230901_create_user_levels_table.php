<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_levels', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("amount")->default("0");
            $table->string("rank")->default("0");
            $table->string("wallet_minimum")->default("0");
            $table->string("transfer_charge")->default("0");
            $table->string("withdrawal_charge")->default("0");
            $table->string("min_withdrawal")->default("0");
            $table->string("min_transfer")->default("0");
            $table->string("min_deposit")->default("0");
            $table->string("min_transaction")->default("0");
            $table->string("min_purchase")->default("0");
            $table->string("deposit_charge")->default("0");
            $table->string("purchase_charge")->default("0");
            $table->string("transaction_charge")->default("0");
            $table->string("web_charge")->default("0");
            $table->string("api_charge")->default("0");
            $table->string("mobile_charge")->default("0");
            $table->enum('enable_api', ['0','1'])->default('1');
            $table->unsignedBigInteger('limit_id');
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
        Schema::dropIfExists('user_levels');
    }
}
