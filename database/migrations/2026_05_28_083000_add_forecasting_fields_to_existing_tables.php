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
        // 1. Add fields to inventory_items
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('category_id')->constrained('departments')->onDelete('set null');
            $table->foreignId('warehouse_id')->nullable()->after('department_id')->constrained('warehouses')->onDelete('set null');
            $table->string('location')->nullable()->after('warehouse_id');
            $table->decimal('sell_price', 15, 2)->default(0.00)->after('min_stock_level');
            $table->decimal('current_stock', 15, 2)->default(0.00)->after('sell_price');
        });

        // 2. Add fields to suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->integer('lead_time_days')->nullable()->after('rating');
        });

        // 3. Add fields to asset_issuances
        Schema::table('asset_issuances', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('employee_id')->constrained('departments')->onDelete('set null');
        });

        // 4. Add fields to job_postings
        Schema::table('job_postings', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('branch_id')->constrained('departments')->onDelete('set null');
            $table->integer('number_of_positions')->default(1)->after('job_type');
            $table->date('expected_joining_date')->nullable()->after('closing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id', 'number_of_positions', 'expected_joining_date']);
        });

        Schema::table('asset_issuances', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['lead_time_days']);
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['department_id', 'warehouse_id', 'location', 'sell_price', 'current_stock']);
        });
    }
};
