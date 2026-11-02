<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('login_challenges', function (Blueprint $table) {
            $table->unsignedTinyInteger('send_count')->default(1)->after('attempts');
        });
    }
    public function down(): void { Schema::table('login_challenges', fn (Blueprint $table) => $table->dropColumn('send_count')); }
};
