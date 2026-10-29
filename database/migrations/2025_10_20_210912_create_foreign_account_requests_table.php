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
        Schema::create('foreign_account_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('currency', 10);
            $table->string('bvn', 11)->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('occupation')->nullable();
            $table->string('primary_purpose')->nullable();
            $table->string('source_of_funds')->nullable();
            $table->decimal('expected_monthly_inflow', 15, 2)->nullable();
            $table->string('document')->nullable();
            $table->string('document_type')->nullable();
            $table->date('document_issue_date')->nullable();
            $table->date('document_expiry_date')->nullable();
            $table->string('document2')->nullable();
            $table->string('document2_type')->nullable();
            $table->date('document2_issue_date')->nullable();
            $table->date('document2_expiry_date')->nullable();
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
        Schema::dropIfExists('foreign_account_requests');
    }
};
