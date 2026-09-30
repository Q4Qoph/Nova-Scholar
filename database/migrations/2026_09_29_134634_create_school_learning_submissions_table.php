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
        Schema::create('school_learning_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_learning_assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_learning_assignment_recipient_id')->unique('learning_submission_recipient_unique')->constrained()->restrictOnDelete();
            $table->foreignId('learner_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('response_text');
            $table->string('status', 32)->default('draft');
            $table->boolean('is_late')->default(false);
            $table->timestamp('draft_saved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('acknowledgement_reference', 26)->nullable()->unique();
            $table->timestamps();
            $table->index(['school_id', 'school_learning_assignment_id', 'status']);
            $table->index(['learner_profile_id', 'status', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_learning_submissions');
    }
};
