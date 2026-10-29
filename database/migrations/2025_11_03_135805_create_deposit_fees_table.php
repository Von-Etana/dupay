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
        Schema::create('deposit_fees', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 3);
            $table->decimal('minimum_amount', 10, 2)->default('0.00')->nullable();
            $table->decimal('maximum_amount', 10, 2)->default('0.00')->nullable();
            $table->decimal('fee', 10, 2)->default('0.00');
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
        Schema::dropIfExists('deposit_fees');
    }
};
