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
        Schema::table('job_applications', function (Blueprint $table) {
            $table->unsignedTinyInteger('ai_score')->nullable()->after('resume_path');
            $table->text('ai_feedback')->nullable()->after('ai_score');
            $table->timestamp('screened_at')->nullable()->after('ai_feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['ai_score', 'ai_feedback', 'screened_at']);
        });
    }
};
