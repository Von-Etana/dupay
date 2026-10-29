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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('account_type_id');
            $table->string('username')->unique();
            $table->string("commission")->default("0.0");
            $table->string("first_name")->nullable();
            $table->string("last_name")->nullable();
            $table->string("ref_id")->nullable();
            $table->enum('verified', ['0','1'])->default('0');
            $table->enum('account_complete', ['0','1'])->default('0');
            $table->string("profile_img")->nullable();
            $table->string("profile_bg")->nullable();
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
        Schema::dropIfExists('profiles');
    }
};
