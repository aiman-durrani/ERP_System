<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\JobPosting;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryDashboardController extends Controller
{
    public function index()
    {
        $lowStockCount = InventoryItem::whereHas('stocks', function ($query) {
            $query->select(DB::raw('sum(quantity) as total_qty'))
                  ->groupBy('inventory_item_id')
                  ->havingRaw('total_qty <= inventory_items.min_stock_level');
        })->count();

        return Inertia::render('Inventory/Dashboard', [
            'stats' => [
                'totalItems' => InventoryItem::count(),
                'totalSuppliers' => Supplier::count(),
                'totalWarehouses' => Warehouse::count(),
                'pendingPOs' => PurchaseOrder::where('status', 'Pending')->count(),
                'lowStockItems' => $lowStockCount,
            ],
            'recentLogs' => StockLog::with(['item', 'warehouse'])->latest()->take(5)->get(),
            'warehouseStocks' => Warehouse::with(['stocks' => function($q) {
                $q->with('item');
            }])->get(),
        ]);
    }

    public function forecast()
    {
        $openJobPositions = \App\Models\JobPosting::where('status', 'published')->count();

        $items = \App\Models\InventoryItem::withoutGlobalScopes()
            ->with([
                'category',
                'stocks',
                'stockLogs' => function ($q) {
                    $q->where('type', 'Out')
                      ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(30));
                },
                'latestForecast',
            ])
            ->get();

        $forecastReports = $items->map(function ($item) {
            $currentStock = (float) $item->stocks->sum('quantity');
            
            $dailyUsage = abs($item->stockLogs->sum('quantity')) / 30.0;
            
            if ($dailyUsage > 0) {
                $daysVal = $currentStock / $dailyUsage;
                $daysUntilEmpty = $daysVal > 365 ? '365+' : (string) round($daysVal);
            } else {
                $daysUntilEmpty = $currentStock > 0 ? '365+' : '0';
            }

            $hrLinkedDemand = 0;
            if ($item->department_id) {
                $hrLinkedDemand = \App\Models\JobPosting::where('status', 'published')
                    ->where('department_id', $item->department_id)
                    ->sum('number_of_positions');
            }

            $latestForecast = $item->latestForecast;
            
            if ($currentStock == 0) {
                $status = 'Critical';
            } elseif ($currentStock <= $item->min_stock_level) {
                $status = 'Low';
            } else {
                $status = ($latestForecast && $latestForecast->status) ? $latestForecast->status : 'Healthy';
            }

            $suggestedQty = 0;
            if ($latestForecast && isset($latestForecast->suggested_reorder_quantity)) {
                $suggestedQty = (float) $latestForecast->suggested_reorder_quantity;
            }

            if ($currentStock <= $item->reorder_point) {
                $safetyQty = max(0, $item->min_stock_level - $currentStock);
                $suggestedQty = max($suggestedQty, $safetyQty);
                $suggestedOrderDate = \Carbon\Carbon::today()->format('Y-m-d');
            } else {
                $suggestedOrderDate = ($latestForecast && $latestForecast->suggested_order_date) 
                    ? $latestForecast->suggested_order_date->format('Y-m-d') 
                    : 'Stock Sufficient';
            }

            $suggestedReorderQty = $suggestedQty . ' ' . ($item->uom ?? '');

            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'category' => $item->category?->name ?? 'Uncategorized',
                'current_stock' => $currentStock . ' ' . ($item->uom ?? ''),
                'daily_usage' => round($dailyUsage, 2) . ' / day',
                'days_until_empty' => $daysUntilEmpty,
                'hr_linked_demand' => $hrLinkedDemand,
                'suggested_reorder_qty' => $suggestedReorderQty,
                'suggested_order_date' => $suggestedOrderDate,
                'status' => $status,
            ];
        })->values();

        return \Inertia\Inertia::render('Inventory/ForecastReport', [
            'forecastData' => $forecastReports,
            'hiringContext' => [
                'openPositions' => $openJobPositions,
            ],
        ]);
    }

}
