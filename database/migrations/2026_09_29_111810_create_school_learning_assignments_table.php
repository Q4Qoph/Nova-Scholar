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
        Schema::create('school_learning_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teaching_assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_course_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_lesson_version_id')->constrained('school_lesson_versions')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 255);
            $table->text('instructions');
            $table->string('submission_type', 32)->default('text');
            $table->timestamp('due_at');
            $table->timestamp('cutoff_at')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status', 'due_at']);
            $table->index(['teaching_assignment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_learning_assignments');
    }
};
