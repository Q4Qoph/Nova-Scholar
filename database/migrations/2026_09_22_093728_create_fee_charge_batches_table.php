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
        Schema::create('fee_charge_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('batch_key');
            $table->string('status')->default('draft');
            $table->unsignedInteger('eligible_count')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'batch_key']);
            $table->index(['school_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_charge_batches');
    }
};
