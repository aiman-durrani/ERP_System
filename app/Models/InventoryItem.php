<?php

namespace App\Models;

use App\Traits\BelongsToHR;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryItem extends Model
{
    use HasFactory, BelongsToHR;

    protected $fillable = [
        'hr_id',
        'category_id',
        'department_id',
        'warehouse_id',
        'item_code',
        'name',
        'uom',
        'min_stock_level',
        'reorder_point',
        'sell_price',
        'current_stock',
        'location',
        'description',
    ];

    protected $casts = [
        'sell_price' => 'decimal:2',
        'current_stock' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function usageHistory()
    {
        return $this->hasMany(InventoryUsageHistory::class, 'inventory_item_id');
    }

    public function forecastResults()
    {
        return $this->hasMany(ForecastResult::class, 'inventory_item_id');
    }

    public function latestForecast()
    {
        return $this->hasOne(ForecastResult::class, 'inventory_item_id')->latestOfMany();
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'inventory_item_supplier')
                    ->withPivot('last_purchase_price')
                    ->withTimestamps();
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function totalStock()
    {
        return $this->stocks()->sum('quantity');
    }

    public function stockLogs()
    {
        return $this->hasMany(StockLog::class);
    }
}
