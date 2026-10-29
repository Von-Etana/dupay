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
        Schema::create('foreign_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('foreign_account_id')->nullable()->index();
            $table->enum('type', ['credit', 'debit'])->nullable();
            $table->string('kind')->nullable(); // deposit, payout
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['successful', 'failed', 'pending'])->nullable();
            $table->decimal('balance_before', 20, 2)->nullable();
            $table->decimal('balance_after', 20, 2)->nullable();
            $table->string('payer_name')->nullable();
            $table->string('payer_bank_name')->nullable();
            $table->string('payer_account_number')->nullable();
            $table->string('payer_routing_number')->nullable();
            $table->decimal('fee', 20, 2)->nullable();
            $table->decimal('amount_settled', 20, 2)->nullable();
            $table->string('reference_id')->nullable();
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
        Schema::dropIfExists('foreign_account_transactions');
    }
};
