<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recharge_card_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained('networks')->cascadeOnDelete();
            $table->decimal('value', 10, 2);
            $table->text('pin');
            $table->string('serial')->nullable();
            $table->enum('status', ['available', 'sold', 'void'])->default('available')->index();
            $table->foreignId('sold_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sold_reference')->nullable()->index();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
            $table->index(['network_id', 'value', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharge_card_pins');
    }
};
