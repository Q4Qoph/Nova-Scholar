<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fee_charge_batches', function (Blueprint $table): void {
            $table->char('preview_hash', 64)->nullable()->after('total_minor');
            $table->timestamp('previewed_at')->nullable()->after('preview_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_charge_batches', function (Blueprint $table): void {
            $table->dropColumn(['preview_hash', 'previewed_at']);
        });
    }
};
