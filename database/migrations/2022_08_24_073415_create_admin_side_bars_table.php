<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminSideBarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_side_bars', function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable();
            $table->string("other_name")->nullable();
            $table->string("icon")->nullable();
            $table->string("image")->nullable();
            $table->string("model")->nullable();
            $table->string("route")->nullable();
            $table->string("order")->nullable();
            $table->string("parent_id")->nullable();
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
        Schema::dropIfExists('admin_side_bars');
    }
}
