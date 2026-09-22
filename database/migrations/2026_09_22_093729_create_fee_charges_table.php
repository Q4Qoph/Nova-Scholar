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
        Schema::create('fee_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_charge_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $table->string('description');
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->string('status')->default('posted');
            $table->date('charged_on');
            $table->timestamps();
            $table->unique(['fee_charge_batch_id', 'enrolment_id']);
            $table->index(['school_id', 'enrolment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_charges');
    }
};
