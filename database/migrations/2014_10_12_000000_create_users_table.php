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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('phone_number')->unique();
            $table->string('balance')->default('0.00');
            $table->string('bonus')->default('0.00');
            $table->unsignedBigInteger('user_level_id')->default(1);
            $table->string('referral')->default('0');
            $table->enum('ban_status', ['0','1'])->default('0');
            $table->enum('account_activation', ['0','1'])->default('0');
            $table->string('password');
            $table->string('s_password');
            $table->string('transaction_pin')->nullable();
            $table->string('login_time')->nullable();
            $table->string('email_code')->nullable();
            $table->string('email_time')->nullable();
            $table->enum('email_verify', ['0','1','2'])->default('0');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone_code')->nullable();
            $table->string('phone_time')->nullable();
            $table->enum('phone_verify', ['0','1','2'])->default('0');
            $table->string('fcmToken')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};
