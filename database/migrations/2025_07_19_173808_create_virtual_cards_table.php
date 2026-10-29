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
        Schema::create('virtual_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('payscribe_card_id');
            $table->string('trans_id')->nullable();
            $table->string('card_type');
            $table->string('currency');
            $table->string('brand');
            $table->string('name');
            $table->string('first_six');
            $table->string('last_four');
            $table->string('masked')->nullable();
            $table->string('number')->nullable();
            $table->string('expiry')->nullable();
            $table->string('ccv')->nullable();
            $table->json('billing')->nullable();
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
        Schema::dropIfExists('virtual_cards');
    }
};
