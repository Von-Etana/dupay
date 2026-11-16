<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recharge_card_pins', function (Blueprint $table) {
            $table->id();
            $table->string('network', 32);
            $table->decimal('denomination', 12, 2);
            $table->text('pin');
            $table->string('serial_number')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('supplier')->nullable();
            $table->date('expires_at')->nullable();
            $table->enum('status', ['available','reserved','sold','refunded','expired','invalid'])->default('available')->index();
            $table->foreignId('added_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->index(['network', 'denomination']);
            $table->unique(['network', 'pin']);
        });
    }

    public function down(): void { Schema::dropIfExists('recharge_card_pins'); }
};
