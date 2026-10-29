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
        Schema::create('airtime_types', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->unsignedBigInteger('network_id');
            $table->unsignedBigInteger('api_connection_id');
            $table->enum('orderDelivery', ['0','1'])->default('1');
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
        Schema::dropIfExists('airtime_types');
    }
};
