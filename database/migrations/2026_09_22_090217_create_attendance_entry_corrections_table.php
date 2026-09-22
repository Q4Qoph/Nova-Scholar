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
        Schema::create('attendance_entry_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->text('reason');
            $table->foreignId('corrected_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('session_version');
            $table->timestamp('corrected_at');
            $table->timestamps();
            $table->index(['attendance_session_id', 'session_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_entry_corrections');
    }
};
