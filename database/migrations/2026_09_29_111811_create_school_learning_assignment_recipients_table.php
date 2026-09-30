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
        Schema::create('school_learning_assignment_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_learning_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learner_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $table->foreignId('learner_class_membership_id')->constrained()->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->unique(['school_learning_assignment_id', 'enrolment_id'], 'learning_recipient_assignment_enrolment_unique');
            $table->index(['school_id', 'learner_profile_id', 'school_learning_assignment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_learning_assignment_recipients');
    }
};
