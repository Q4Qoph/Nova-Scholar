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
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained()->restrictOnDelete();
            $table->foreignId('teaching_assignment_id')->constrained()->restrictOnDelete();
            $table->date('session_date');
            $table->string('status')->default('open');
            $table->unsignedInteger('version')->default(0);
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'class_group_id', 'teaching_assignment_id', 'session_date']);
            $table->index(['school_id', 'session_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
