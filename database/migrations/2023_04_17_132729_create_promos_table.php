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
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string('code')->nullable();
            $table->string("description")->nullable();
            $table->string("image")->nullable();
            $table->string("amount");
            $table->date('start_at')->useCurrent();
            $table->date('end_at')->useCurrent();
            $table->string("times")->default("1");
            $table->string("min_amount")->default("0");
            $table->enum('min_amount_type', ['1','2','3'])->default('1'); //1 wallet  2 deposit 3 Transaction
            $table->unsignedBigInteger('wallet_id')->default(1);
            $table->unsignedBigInteger('user_level_id')->default(0);
            $table->unsignedBigInteger('service_type_id')->default(0);
            $table->enum('platform', ['1','2','3'])->default('1'); //1 general, 2 websit 3 app
            $table->enum('status', ['0','1','2','3'])->default('1'); //0 inactive 1 active 2 suspended 3 cancel
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
        Schema::dropIfExists('promos');
    }
};
