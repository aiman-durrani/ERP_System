<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryUsageHistory extends Model
{
    use HasFactory;

    protected $table = 'inventory_usage_history';

    protected $fillable = [
        'inventory_item_id',
        'department_id',
        'warehouse_id',
        'quantity_used',
        'usage_date',
        'current_stock',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'quantity_used' => 'decimal:2',
        'current_stock' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
