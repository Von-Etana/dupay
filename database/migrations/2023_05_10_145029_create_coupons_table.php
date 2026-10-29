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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string("name")->nullable();
            $table->string("code")->nullable();
            $table->string("number_to_generate")->nullable();
            $table->string("prefix")->nullable();
            $table->string("suffix")->nullable();
            $table->string("length")->nullable();
            $table->string("code_type")->nullable();
            $table->string("description")->nullable();
            $table->string("discount")->nullable();
            $table->string("start_date")->nullable();
            $table->string("end_date")->nullable();
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
        Schema::dropIfExists('coupons');
    }
};
