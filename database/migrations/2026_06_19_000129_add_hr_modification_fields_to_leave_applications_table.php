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
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->date('original_start_date')->nullable();
            $table->date('original_end_date')->nullable();
            $table->boolean('hr_modified')->default(false);
            $table->text('hr_modification_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn([
                'original_start_date',
                'original_end_date',
                'hr_modified',
                'hr_modification_reason'
            ]);
        });
    }
};
