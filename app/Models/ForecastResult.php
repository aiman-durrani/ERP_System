<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForecastResult extends Model
{
    use HasFactory;

    protected $table = 'forecast_results';

    protected $fillable = [
        'inventory_item_id',
        'warehouse_id',
        'department_id',
        'predicted_demand',
        'recommended_stock',
        'suggested_reorder_quantity',
        'suggested_order_date',
        'status',
        'confidence_score',
        'forecast_date',
        'model_version',
        'input_features',
    ];

    protected $casts = [
        'suggested_order_date' => 'date',
        'forecast_date' => 'date',
        'predicted_demand' => 'decimal:2',
        'recommended_stock' => 'decimal:2',
        'suggested_reorder_quantity' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'input_features' => 'array',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
