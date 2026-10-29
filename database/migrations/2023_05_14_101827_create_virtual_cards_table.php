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
            $table->unsignedBigInteger('user_id');
            $table->string("card_id")->nullable();
            $table->string("account_id")->nullable();
            $table->string("amount")->nullable();
            $table->string("currency")->nullable();
            $table->string("card_hash")->nullable();
            $table->string("card_pan")->nullable();
            $table->string("masked_pan")->nullable();
            $table->string("city")->nullable();
            $table->string("state")->nullable();
            $table->string("address_1")->nullable();
            $table->string("address_2")->nullable();
            $table->string("zip_code")->nullable();
            $table->string("cvv")->nullable();
            $table->string("expiration")->nullable();
            $table->string("send_to")->nullable();
            $table->string("bin_check_name")->nullable();
            $table->string("card_type")->nullable();
            $table->string("name_on_card")->nullable();
            $table->enum("is_active", [true,false])->default(true);
            $table->string("callback_url")->nullable();
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
        Schema::dropIfExists('virtual_cards');
    }
};
