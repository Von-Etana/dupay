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
        Schema::create('wallet_verificaitons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string("bvn")->nullable();
            $table->string("name")->nullable();
            $table->string("mobile_no")->nullable();
            $table->string("date_of_birth")->nullable();
            $table->text("result")->nullable();
            $table->string("comment")->nullable();
            $table->enum('status', ['0','1','2'])->default('0');
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
        Schema::dropIfExists('wallet_verificaitons');
    }
};
