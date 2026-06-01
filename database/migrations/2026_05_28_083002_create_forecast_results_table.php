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
        Schema::create('forecast_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->decimal('predicted_demand', 15, 2);
            $table->decimal('recommended_stock', 15, 2);
            $table->decimal('suggested_reorder_quantity', 15, 2)->nullable();
            $table->date('suggested_order_date')->nullable();
            $table->string('status')->default('Pending'); // e.g. Pending, Approved, Rejected
            $table->decimal('confidence_score', 5, 2)->nullable(); // e.g. 95.50 for 95.5% confidence
            $table->date('forecast_date');
            $table->string('model_version')->nullable();
            $table->json('input_features')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_results');
    }
};
