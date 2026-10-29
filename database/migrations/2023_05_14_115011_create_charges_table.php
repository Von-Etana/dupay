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
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->string("amount_from")->nullable();
            $table->string("amount_to")->nullable();
            $table->string("fee")->nullable();
            $table->enum('type', ['0','1','2'])->default('0'); //0 deposit 1 transfer 2 withdraw
            $table->enum('mode', ['0','1'])->default('0'); //0 flat 1 vat
            $table->enum('default', ['0','1'])->default('0');
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
        Schema::dropIfExists('charges');
    }
};
