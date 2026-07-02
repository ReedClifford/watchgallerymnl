<?php

namespace App\Http\Controllers;

use App\Models\PageVisit;
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
            'analytics' => $this->getAnalytics(),
        ]);
    }

    private function getAnalytics(): array
    {
        $todayStart = now()->startOfDay();
        $last7DaysStart = now()->subDays(6)->startOfDay();
        $last30Days = now()->subDays(30)->startOfDay();

        $todayVisitsQuery = PageVisit::query()
            ->where('is_valid', true)
            ->where('is_bot', false)
            ->where('entered_at', '>=', $todayStart);

        $todayValidVisits = (clone $todayVisitsQuery)->count();

        $todayUniqueVisitors = (clone $todayVisitsQuery)
            ->distinct('visitor_id')
            ->count('visitor_id');

        $todayEngagedVisits = (clone $todayVisitsQuery)
            ->where('is_engaged', true)
            ->count();

        $averageDurationSeconds = (int) round(
            (clone $todayVisitsQuery)->avg('duration_seconds') ?? 0
        );

        $totalValidVisits = PageVisit::query()
            ->where('is_valid', true)
            ->where('is_bot', false)
            ->count();

        $dailyVisitsRaw = PageVisit::query()
            ->selectRaw('DATE(entered_at) as visit_date')
            ->selectRaw('COUNT(*) as visits')
            ->selectRaw('COUNT(DISTINCT visitor_id) as unique_visitors')
            ->selectRaw('ROUND(AVG(duration_seconds)) as avg_duration')
            ->where('is_valid', true)
            ->where('is_bot', false)
            ->where('entered_at', '>=', $last7DaysStart)
            ->groupByRaw('DATE(entered_at)')
            ->orderByRaw('DATE(entered_at) ASC')
            ->get()
            ->keyBy('visit_date');

        $dailyVisits = collect(range(6, 0))
            ->map(function ($daysAgo) use ($dailyVisitsRaw) {
                $date = now()->subDays($daysAgo);
                $key = $date->toDateString();
                $row = $dailyVisitsRaw->get($key);

                return [
                    'date' => $key,
                    'label' => $date->format('M j'),
                    'visits' => (int) ($row->visits ?? 0),
                    'unique_visitors' => (int) ($row->unique_visitors ?? 0),
                    'avg_duration' => (int) ($row->avg_duration ?? 0),
                ];
            })
            ->values();

        $topWatches = PageVisit::query()
            ->selectRaw('page_path')
            ->selectRaw('MAX(page_title) as page_title')
            ->selectRaw('COUNT(*) as visits')
            ->selectRaw('COUNT(DISTINCT visitor_id) as unique_visitors')
            ->selectRaw('ROUND(AVG(duration_seconds)) as avg_duration')
            ->where('is_valid', true)
            ->where('is_bot', false)
            ->where('page_path', 'like', '/watches/%')
            ->where('entered_at', '>=', $last30Days)
            ->groupBy('page_path')
            ->orderByDesc('visits')
            ->limit(5)
            ->get()
            ->map(function ($watchPage) {
                return [
                    'page_path' => $watchPage->page_path,
                    'page_title' => $watchPage->page_title ?: $watchPage->page_path,
                    'visits' => (int) $watchPage->visits,
                    'unique_visitors' => (int) $watchPage->unique_visitors,
                    'avg_duration' => (int) $watchPage->avg_duration,
                ];
            })
            ->values();

        $deviceBreakdownRaw = PageVisit::query()
            ->selectRaw("COALESCE(device, 'unknown') as device")
            ->selectRaw('COUNT(*) as visits')
            ->where('is_valid', true)
            ->where('is_bot', false)
            ->where('entered_at', '>=', $todayStart)
            ->groupBy('device')
            ->orderByDesc('visits')
            ->get();

        $deviceBreakdown = $deviceBreakdownRaw
            ->map(function ($device) use ($todayValidVisits) {
                return [
                    'device' => $device->device ?: 'unknown',
                    'visits' => (int) $device->visits,
                    'percentage' => $todayValidVisits > 0
                        ? round(((int) $device->visits / $todayValidVisits) * 100)
                        : 0,
                ];
            })
            ->values();

        return [
            'today_valid_visits' => $todayValidVisits,
            'today_unique_visitors' => $todayUniqueVisitors,
            'today_engaged_visits' => $todayEngagedVisits,
            'average_duration_seconds' => $averageDurationSeconds,
            'total_valid_visits' => $totalValidVisits,
            'daily_visits' => $dailyVisits,
            'top_watches' => $topWatches,
            'device_breakdown' => $deviceBreakdown,
        ];
    }
}