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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->string("subject")->nullable();
            $table->text("content")->nullable();
            $table->string("image")->nullable();
            $table->enum('type', ['1','2', '3'])->default('1'); //1 moving, 2 pop, 3 image pop
            $table->enum('platform', ['1','2','3'])->default('1'); //1 website, 2 mobile app 3 email
            $table->enum('status', ['0','1'])->default('1'); //0 inactive //2 active
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
        Schema::dropIfExists('notifications');
    }
};
