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
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->string("sub_title")->nullable();
            $table->string("model")->nullable();
            $table->string("route")->nullable();
            $table->string("route_parameter")->nullable();
            $table->string("parent_id")->nullable();
            $table->string("icon")->nullable();
            $table->string("order_id")->nullable();
            $table->enum('admin_side_bar', ['0','1'])->default('1');
            $table->enum('user_side_bar', ['0','1'])->default('1');
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
        Schema::dropIfExists('servicetypes');
    }
};
