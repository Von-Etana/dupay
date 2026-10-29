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
        Schema::table('foreign_account_requests', function (Blueprint $table) {
            $table->string('state', 100)->after('merchantReference');
            $table->string('city', 100)->after('state');
            $table->string('street', 255)->after('city');
            $table->string('zip_code', 20)->after('street');
            $table->string('countryOfResidence', 100)->after('zip_code');
            $table->string('house_number', 50)->after('countryOfResidence');
            $table->string('birthDate')->after('house_number');
            $table->string('document_type', 50)->after('birthDate');
            $table->string('document_number', 100)->after('document_type');
            $table->string('issuedCountryCode', 10)->after('document_number');
            $table->string('issuedBy', 100)->after('issuedCountryCode');
            $table->string('issuedDate')->after('issuedBy');
            $table->string('expirationDate')->after('issuedDate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('foreign_account_requests', function (Blueprint $table) {
            //
        });
    }
};
