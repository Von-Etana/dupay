<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('api_connection_id')->nullable();
            $table->unsignedBigInteger('service_type_id')->nullable();
            $table->unsignedBigInteger('service_plan_id')->nullable();
            $table->unsignedBigInteger('user_level_id')->nullable();
            $table->unsignedBigInteger('wallet_id')->default(1);
            $table->string("service")->nullable();
            $table->string("service_type")->nullable();
            $table->string("description")->nullable();
            $table->string("phone_number")->nullable();
            $table->string("amount")->nullable();
            $table->enum('type', ['0','1'])->default('1');
            $table->string("initial_balance")->default("0.0");
            $table->string("final_balance")->default("0.0");
            $table->string("reference")->nullable();
            $table->string("paymentReference")->nullable();
            $table->string("message")->nullable();
            //$table->enum('status', ['pending','success', 'unsucessful', 'cancel', 'refund'])->default('pending');
            $table->unsignedBigInteger('status_id')->default(1);
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
        Schema::dropIfExists('transactions');
    }
}
