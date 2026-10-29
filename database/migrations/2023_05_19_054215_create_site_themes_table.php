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
        Schema::create('site_themes', function (Blueprint $table) {
            $table->id();
            $table->string("typography")->default("poppins");
            $table->string("version")->default("light");
            $table->string("layout")->default("vertical");
            $table->string("headerBg")->default("color_1");
            $table->string("primary")->default("color_1");
            $table->string("navheaderBg")->default("color_1");
            $table->string("sidebarBg")->default("  color_1");
            $table->string("sidebarStyle")->default("full");
            $table->string("sidebarPosition")->default("fixed");
            $table->string("headerPosition")->default("fixed");
            $table->string("containerLayout")->default("full");
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
        Schema::dropIfExists('site_themes');
    }
};
