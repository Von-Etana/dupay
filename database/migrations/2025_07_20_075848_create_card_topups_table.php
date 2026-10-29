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
        Schema::create('card_topups', function (Blueprint $table) {
            $table->id();
            $table->uuid('card_id');
            $table->uuid('customer_id');
            $table->string('currency')->nullable();
            $table->decimal('prev_balance', 16, 2)->default(0);
            $table->decimal('balance', 16, 2)->default(0);
            $table->string('ref_id');
            $table->string('trans_id');
            $table->string('action')->nullable();
            $table->timestamp('transaction_time')->nullable();
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
        Schema::dropIfExists('card_topups');
    }
};
