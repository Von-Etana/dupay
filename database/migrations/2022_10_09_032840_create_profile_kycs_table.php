<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfileKycsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profile_kycs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string("name")->nullable();
            $table->string("state")->nullable();
            $table->string("lga")->nullable();
            $table->string("pratice_file")->nullable();
            $table->string("premise_file")->nullable();
            $table->string("extra_file")->nullable();
            $table->string("comment")->nullable();
            $table->enum('status', ['0','1','2'])->default('0');
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
        Schema::dropIfExists('profile_kycs');
    }
}
