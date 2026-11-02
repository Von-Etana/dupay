<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('login_challenges', function(Blueprint $table){ $table->uuid('id')->primary(); $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); $table->string('device_id_hash',128)->nullable(); $table->string('otp_hash'); $table->unsignedTinyInteger('attempts')->default(0); $table->timestamp('expires_at'); $table->timestamp('sent_at')->nullable(); $table->timestamp('consumed_at')->nullable(); $table->timestamps(); $table->index(['user_id','expires_at']); }); }
 public function down(): void { Schema::dropIfExists('login_challenges'); }
};
