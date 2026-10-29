<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wallet_id')->default(1);
            $table->unsignedBigInteger('payment_gateway_id');
            $table->string("type")->nullable();
            $table->string("reference")->nullable();
            $table->string("amount")->default(0);
            $table->string("charge")->default(0);
            $table->unsignedBigInteger('status_id')->default(1);
            //$table->string("status")->nullable();
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
        Schema::dropIfExists('payments');
    }
}
