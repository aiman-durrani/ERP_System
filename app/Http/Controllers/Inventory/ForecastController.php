<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\ForecastResult;
use App\Models\StockLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForecastController extends Controller
{
    public function run(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
        ]);

        $item = InventoryItem::withoutGlobalScopes()
            ->with('stocks')
            ->findOrFail($request->inventory_item_id);

        $getUsageForDay = function ($daysAgo) use ($item) {
            return abs(StockLog::where('inventory_item_id', $item->id)
                ->where('type', 'Out')
                ->whereDate('created_at', Carbon::today()->subDays($daysAgo))
                ->sum('quantity'));
        };

        $sales_lag_1 = $getUsageForDay(1);
        $sales_lag_7 = $getUsageForDay(7);
        $sales_lag_30 = $getUsageForDay(30);

        $rolling_mean_7 = abs(StockLog::where('inventory_item_id', $item->id)
            ->where('type', 'Out')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->sum('quantity')) / 7;

        $rolling_mean_30 = abs(StockLog::where('inventory_item_id', $item->id)
            ->where('type', 'Out')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->sum('quantity')) / 30;

        $now = Carbon::now();
        $pythonDayOfWeek = $now->dayOfWeek === 0 ? 6 : $now->dayOfWeek - 1;

        $payload = [
            'item_id' => (int) $item->id,
            'dept_id' => (int) ($item->department_id ?? 0),
            'cat_id' => (int) ($item->category_id ?? 0),
            'store_id' => (int) ($item->warehouse_id ?? 0),
            'state_id' => 0,
            'sell_price' => (float) ($item->sell_price ?? 0),
            'day' => (int) $now->day,
            'month' => (int) $now->month,
            'year' => (int) $now->year,
            'day_of_week' => (int) $pythonDayOfWeek,
            'is_weekend' => (int) ($now->isWeekend() ? 1 : 0),
            'holiday_flag' => 0,
            'sales_lag_1' => (float) $sales_lag_1,
            'sales_lag_7' => (float) $sales_lag_7,
            'sales_lag_30' => (float) $sales_lag_30,
            'rolling_mean_7' => (float) $rolling_mean_7,
            'rolling_mean_30' => (float) $rolling_mean_30,
            'snap_CA' => 0,
            'snap_TX' => 0,
            'snap_WI' => 0,
        ];

        $state = strtoupper(trim($item->location ?? ''));

        if ($state === 'CA') {
            $payload['state_id'] = 1;
            $payload['snap_CA'] = 1;
        } elseif ($state === 'TX') {
            $payload['state_id'] = 2;
            $payload['snap_TX'] = 1;
        } elseif ($state === 'WI') {
            $payload['state_id'] = 3;
            $payload['snap_WI'] = 1;
        } elseif (is_numeric($item->location)) {
            $payload['state_id'] = (int) $item->location;
        }

        $apiUrl = env('PYTHON_AI_URL', 'http://127.0.0.1:8001/predict');

        try {
            $response = Http::timeout(5)->post($apiUrl, $payload);

            if (!$response->successful()) {
                Log::error('FastAPI forecast prediction failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'AI server returned an error: ' . $response->status(),
                ], 500);
            }

            $data = $response->json();

            $currentStock = (float) $item->stocks->sum('quantity');

            $rawPredictedDemand = (float) ($data['predicted_demand'] ?? 0);
            $rawRecommendedStock = (float) ($data['recommended_stock'] ?? 0);

            $predictedDemand = (int) round($rawPredictedDemand);
            $recommendedStock = (int) ceil($rawRecommendedStock);

            if ($currentStock == 0) {
                $finalStatus = 'Critical';
            } elseif ($currentStock <= (float) $item->min_stock_level || $currentStock <= (float) $item->reorder_point) {
                $finalStatus = 'Low';
            } elseif ($predictedDemand > $currentStock) {
                $finalStatus = 'Reorder Required';
            } else {
                $finalStatus = 'Stock Sufficient';
            }

            $suggestedReorderQuantity = 0;

            if ($currentStock <= (float) $item->min_stock_level) {
                $suggestedReorderQuantity = max(0, (float) $item->min_stock_level - $currentStock);
            } elseif ($predictedDemand > $currentStock) {
                $suggestedReorderQuantity = max(0, $recommendedStock - $currentStock);
            }

            $forecast = ForecastResult::create([
                'inventory_item_id' => $item->id,
                'warehouse_id' => $item->warehouse_id,
                'department_id' => $item->department_id,
                'predicted_demand' => $predictedDemand,
                'recommended_stock' => $recommendedStock,
                'suggested_reorder_quantity' => ceil($suggestedReorderQuantity),
                'suggested_order_date' => $suggestedReorderQuantity > 0
                    ? Carbon::today()->toDateString()
                    : null,
                'status' => $finalStatus,
                'confidence_score' => $data['confidence_score'] ?? 0.95,
                'forecast_date' => Carbon::today()->toDateString(),
                'model_version' => $data['model_version'] ?? 'v1.0',
                'input_features' => $payload,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Forecast run successfully for ' . $item->name,
                'data' => [
                    'predicted_demand' => $forecast->predicted_demand,
                    'recommended_stock' => $forecast->recommended_stock,
                    'suggested_reorder_quantity' => $forecast->suggested_reorder_quantity,
                    'status' => $forecast->status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Connection to FastAPI forecast server failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not connect to the AI Forecasting Service. Please ensure FastAPI is running on '
                    . parse_url($apiUrl, PHP_URL_HOST) . ':'
                    . (parse_url($apiUrl, PHP_URL_PORT) ?? '8001'),
            ], 500);
        }
    }
}