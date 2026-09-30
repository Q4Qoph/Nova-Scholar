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
        Schema::create('fee_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_charge_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('adjustment_key', 64);
            $table->string('kind', 20)->default('credit');
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('amount_minor');
            $table->string('reason', 500);
            $table->string('review_note', 500)->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'adjustment_key']);
            $table->index(['school_id', 'status', 'created_at']);
            $table->index(['school_id', 'fee_charge_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_adjustments');
    }
};
