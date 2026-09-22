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
        Schema::create('attendance_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('unmarked');
            $table->timestamp('marked_at')->nullable();
            $table->foreignId('marked_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['attendance_session_id', 'enrolment_id']);
            $table->index(['attendance_session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_entries');
    }
};
