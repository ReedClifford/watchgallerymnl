<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $soldWatches = Watch::query()
            ->where('status', 'sold')
            ->get(['sold_price', 'capital_price']);

        $totalSales = $soldWatches->sum(function ($watch) {
            return (float) ($watch->sold_price ?? 0);
        });

        $totalProfit = $soldWatches->sum(function ($watch) {
            return (float) ($watch->sold_price ?? 0) - (float) ($watch->capital_price ?? 0);
        });

        $bestSellers = Watch::query()
            ->select('brand', 'model_name', 'reference_number')
            ->selectRaw('COUNT(*) as sold_count')
            ->selectRaw('SUM(COALESCE(sold_price, 0)) as total_sales')
            ->selectRaw('SUM(COALESCE(sold_price, 0) - COALESCE(capital_price, 0)) as total_profit')
            ->where('status', 'sold')
            ->groupBy('brand', 'model_name', 'reference_number')
            ->orderByDesc('sold_count')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(function ($watch) {
                return [
                    'brand' => $watch->brand,
                    'model_name' => $watch->model_name,
                    'reference_number' => $watch->reference_number,
                    'sold_count' => (int) $watch->sold_count,
                    'total_sales' => (float) $watch->total_sales,
                    'total_profit' => (float) $watch->total_profit,
                ];
            });

        $latestSold = Watch::query()
            ->with('primaryImage')
            ->where('status', 'sold')
            ->latest('date_sold')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function ($watch) {
                return [
                    'id' => $watch->id,
                    'brand' => $watch->brand,
                    'model_name' => $watch->model_name,
                    'reference_number' => $watch->reference_number,
                    'sold_price' => $watch->sold_price,
                    'capital_price' => $watch->capital_price,
                    'profit' => (float) ($watch->sold_price ?? 0) - (float) ($watch->capital_price ?? 0),
                    'date_sold' => optional($watch->date_sold)->format('Y-m-d'),
                    'buyer_name' => $watch->buyer_name,
                    'image_url' => $watch->primaryImage
                        ? Storage::url($watch->primaryImage->image_path)
                        : null,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_watches' => Watch::query()->count(),
                'available_watches' => Watch::query()->where('status', 'available')->count(),
                'reserved_watches' => Watch::query()->where('status', 'reserved')->count(),
                'sold_watches' => Watch::query()->where('status', 'sold')->count(),
                'hidden_watches' => Watch::query()->where('status', 'hidden')->count(),
                'total_sales' => $totalSales,
                'total_profit' => $totalProfit,
            ],
            'bestSellers' => $bestSellers,
            'latestSold' => $latestSold,
        ]);
    }
}