<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Physical ATM card disputes raised with StroWallet.
 *
 * A row is created the moment an admin files a dispute. `dispute_id` is the
 * reference StroWallet hands back from POST /api/naira_CreateDispute; if the
 * call fails the row is still kept with status = failed so nothing is lost and
 * the admin can retry.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('card_disputes')) {
            return;
        }

        Schema::create('card_disputes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('physical_card_id')->nullable()->index();

            // StroWallet identifiers
            $table->string('card_id')->nullable()->index();
            $table->string('masked_pan')->nullable();
            $table->string('transaction_id')->index();
            $table->string('dispute_id')->nullable()->index();

            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 10)->default('NGN');
            $table->string('transaction_date')->nullable();
            $table->string('location')->nullable();

            // StroWallet accepts exactly these reasons:
            // other, canceled, not_received, fraudulent, duplicate,
            // product_not_as_described, service_not_as_described
            $table->string('reason', 50);
            $table->text('explanation');

            $table->enum('status', ['pending', 'submitted', 'failed', 'won', 'lost', 'withdrawn'])
                  ->default('pending')
                  ->index();

            $table->text('provider_message')->nullable();
            $table->longText('provider_response')->nullable();
            $table->text('admin_note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable(); // admins.id
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('card_disputes');
    }
};
