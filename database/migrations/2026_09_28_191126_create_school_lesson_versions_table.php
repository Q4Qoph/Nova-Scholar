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
        Schema::create('school_lesson_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_lesson_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('title', 255);
            $table->longText('body');
            $table->string('status')->default('draft');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['school_lesson_id', 'version_number']);
            $table->index(['school_lesson_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_lesson_versions');
    }
};
