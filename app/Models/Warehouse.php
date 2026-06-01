<?php

namespace App\Models;

use App\Traits\BelongsToHR;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory, BelongsToHR;

    protected $fillable = [
        'hr_id',
        'name',
        'location',
        'description',
        'is_active',
    ];

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function usageHistory()
    {
        return $this->hasMany(InventoryUsageHistory::class);
    }

    public function forecastResults()
    {
        return $this->hasMany(ForecastResult::class);
    }
}
