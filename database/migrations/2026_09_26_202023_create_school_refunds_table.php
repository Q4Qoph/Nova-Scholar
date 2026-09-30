<?php

declare(strict_types=1);

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
        Schema::create('school_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('refund_key', 64);
            $table->string('completion_key', 64)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('refund_method', 20);
            $table->string('payout_reference', 120)->nullable();
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->string('reason', 500);
            $table->string('review_note', 500)->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'refund_key']);
            $table->unique(['school_id', 'completion_key']);
            $table->unique(['school_id', 'refund_method', 'payout_reference']);
            $table->index(['school_id', 'status', 'created_at']);
            $table->index(['school_id', 'school_receipt_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_refunds');
    }
};
