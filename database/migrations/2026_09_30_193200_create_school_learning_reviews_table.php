<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_learning_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_learning_submission_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('feedback');
            $table->unsignedInteger('score')->nullable();
            $table->unsignedInteger('maximum_score')->nullable();
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_learning_reviews');
    }
};
