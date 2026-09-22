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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_type')->default('adult')->after('email');
            $table->string('learner_login_id')->nullable()->unique()->after('account_type');
            $table->timestamp('learner_activated_at')->nullable()->after('learner_login_id');
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['learner_login_id']);
            $table->dropColumn(['account_type', 'learner_login_id', 'learner_activated_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
