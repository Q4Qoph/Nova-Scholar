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
        Schema::create('school_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verified_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('source', 20);
            $table->string('source_reference', 120);
            $table->string('submission_key', 64);
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->date('received_on');
            $table->string('verification_note', 500)->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();
            $table->unique(['school_id', 'source', 'source_reference']);
            $table->unique(['school_id', 'submission_key']);
            $table->index(['school_id', 'received_on']);
        });

        Schema::create('fee_receipt_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_charge_id')->constrained()->restrictOnDelete();
            $table->foreignId('allocated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('allocation_key', 64);
            $table->unsignedBigInteger('amount_minor');
            $table->timestamp('allocated_at');
            $table->timestamps();
            $table->unique(['school_id', 'allocation_key']);
            $table->index(['school_id', 'fee_charge_id']);
            $table->index(['school_id', 'school_receipt_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_receipt_allocations');
        Schema::dropIfExists('school_receipts');
    }
};
