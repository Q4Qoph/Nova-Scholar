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
        Schema::create('fee_receipt_allocation_reversals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_receipt_allocation_id')->constrained()->restrictOnDelete();
            $table->foreignId('reversed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('reversal_key', 64);
            $table->unsignedBigInteger('amount_minor');
            $table->string('reason', 500);
            $table->timestamp('reversed_at');
            $table->timestamps();

            $table->unique('fee_receipt_allocation_id');
            $table->unique(['school_id', 'reversal_key']);
            $table->index(['school_id', 'reversed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_receipt_allocation_reversals');
    }
};
