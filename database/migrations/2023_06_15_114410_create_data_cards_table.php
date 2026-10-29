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
        Schema::create('data_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('network_id');
            $table->string("plan")->nullable();
            $table->text("dump_pin")->nullable();
            $table->string("load_code")->nullable();
            $table->string("size")->nullable();
            $table->string("real_price")->nullable();
            $table->string("price")->nullable();
            $table->string("total")->nullable();
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
        Schema::dropIfExists('data_cards');
    }
};
