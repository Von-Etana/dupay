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
        Schema::create('pos_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string("business_address")->nullable();
            $table->string("number_of_terminal")->nullable();
            $table->string("transaction_volume")->nullable();
            $table->text("file_one")->nullable();
            $table->text("file_two")->nullable();
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
        Schema::dropIfExists('pos_requests');
    }
};
