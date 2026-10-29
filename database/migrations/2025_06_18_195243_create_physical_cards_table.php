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
        Schema::create('physical_cards', function (Blueprint $table) {
            $table->id();
$table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('card_id')->nullable();
        $table->string('account_id')->nullable();
        $table->string('currency')->default('NGN');
        $table->string('card_hash')->nullable();
        $table->string('card_pan')->nullable();           
        $table->string('cvv')->nullable();
        $table->string('send_to')->nullable();
        $table->string('bin_check_name')->nullable();

        $table->string('customer_id')->nullable();        
        $table->enum('type', ['virtual', 'physical'])->default('physical');
        $table->string('brand')->nullable();              
        $table->string('masked_pan')->nullable();        // masked version of p an
        $table->string('expiry_month')->nullable();      // e.g. 04 
        $table->string('expiry_year')->nullable();       // e.g. 2028
        $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');

        $table->boolean('atm')->default(false);
        $table->boolean('pos')->default(false);
        $table->boolean('web')->default(false);
        $table->boolean('mobile')->default(false);

        $table->decimal('spending_limit_amount', 15, 2)->default(0.00);
        $table->string('spending_limit_interval')->default('daily');
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
        Schema::dropIfExists('physical_cards');
    }
};
