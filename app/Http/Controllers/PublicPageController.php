<?php

namespace App\Http\Controllers;

use App\Models\AboutUsContent;
use App\Models\Watch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PublicPageController extends Controller
{
    private const LISTED_STATUSES = [
        'available',
        'reserved',
        'in_transit',
        'sold',
    ];

    public function welcome(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $normalizedSearch = $this->normalizeSearchTerm($search);

        $condition = trim((string) $request->input('condition', ''));

        $gender = $this->normalizeGenderFilter(
            $request->input('gender', $request->input('category', ''))
        );

        $inDemand = trim((string) $request->input('in_demand', ''));
        $sort = $this->normalizeSortFilter($request->input('sort', 'random'));

        $randomSeed = (int) $request->input('random_seed');

        if ($randomSeed <= 0) {
            $randomSeed = random_int(1, 999999999);
        }

        $hasGenderColumn = Schema::hasColumn('watches', 'gender');
        $hasCategoryColumn = Schema::hasColumn('watches', 'category');
        $hasInDemandColumn = Schema::hasColumn('watches', 'is_in_demand');

        $conditionMap = [
            'brand_new' => [
                'Brand New',
                'Brandnew',
                'Brand-New',
                'New',
            ],
            'pre_owned' => [
                'Pre-owned',
                'Pre-Owned',
                'Pre Owned',
                'Preowned',
                'Used',
                'Pre-loved',
                'Preloved',
            ],
        ];

        $categoryMap = [
            'men' => [
                "Men's",
                'Mens',
                'Men',
                'Male',
                'mens',
            ],
            'women' => [
                "Women's",
                'Womens',
                'Women',
                'Female',
                'womens',
            ],
            'unisex' => [
                'Unisex',
                'unisex',
            ],
        ];

        $heroWatches = Watch::query()
            ->with($this->watchCardRelations())
            ->whereIn('status', self::LISTED_STATUSES)
            ->where('is_visible', true)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn ($watch) => $this->watchCard($watch))
            ->values();

        $watchesQuery = Watch::query()
            ->with($this->watchCardRelations())
            ->whereIn('status', self::LISTED_STATUSES)
            ->where('is_visible', true)
            ->when($search !== '', function ($query) use ($search, $normalizedSearch) {
                $query->where(function ($subQuery) use ($search, $normalizedSearch) {
                    $subQuery
                        ->where('model_name', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%");

                    if ($normalizedSearch !== '') {
                        $subQuery
                            ->orWhereRaw(
                                "LOWER(REPLACE(REPLACE(model_name, ' ', ''), '-', '')) LIKE ?",
                                ["%{$normalizedSearch}%"]
                            )
                            ->orWhereRaw(
                                "LOWER(REPLACE(REPLACE(reference_number, ' ', ''), '-', '')) LIKE ?",
                                ["%{$normalizedSearch}%"]
                            );
                    }
                });
            })
            ->when(isset($conditionMap[$condition]), function ($query) use ($conditionMap, $condition) {
                $query->whereIn('condition', $conditionMap[$condition]);
            })
            ->when($inDemand === '1' && $hasInDemandColumn, function ($query) {
                $query->where('is_in_demand', true);
            })
            ->when($gender !== '' && $hasGenderColumn, function ($query) use ($gender) {
                if ($gender === 'unisex') {
                    $query->where(function ($subQuery) {
                        $subQuery
                            ->where('gender', 'unisex')
                            ->orWhereNull('gender')
                            ->orWhere('gender', '');
                    });

                    return;
                }

                $query->where('gender', $gender);
            })
            ->when(
                $gender !== '' && ! $hasGenderColumn && $hasCategoryColumn && isset($categoryMap[$gender]),
                function ($query) use ($categoryMap, $gender) {
                    $query->whereIn('category', $categoryMap[$gender]);
                }
            );

        $this->applyCollectionSort($watchesQuery, $sort, $randomSeed, $hasInDemandColumn);

        $queryString = collect([
            'search' => $search !== '' ? $search : null,
            'condition' => $condition !== '' ? $condition : null,
            'gender' => $gender !== '' ? $gender : null,
            'in_demand' => $inDemand !== '' ? $inDemand : null,
            'sort' => $sort,
            'random_seed' => $sort === 'random' ? $randomSeed : null,
        ])
            ->filter(fn ($value) => filled($value))
            ->all();

        $watches = $watchesQuery
            ->paginate(12)
            ->appends($queryString)
            ->through(fn ($watch) => $this->watchCard($watch));

        return Inertia::render('Welcome', [
            'heroWatches' => $heroWatches,
            'watches' => $watches,
            'transactions' => $this->visibleSoldWatches(),
            'aboutUs' => $this->aboutUsContent(),
            'availableCount' => Watch::query()
                ->where('status', 'available')
                ->where('is_visible', true)
                ->count(),
            'listedCount' => Watch::query()
                ->whereIn('status', self::LISTED_STATUSES)
                ->where('is_visible', true)
                ->count(),
            'filters' => [
                'search' => $search,
                'condition' => $condition,
                'gender' => $gender,
                'in_demand' => $inDemand,
                'sort' => $sort,
                'random_seed' => $sort === 'random' ? $randomSeed : null,
                'category' => $gender,
            ],
        ]);
    }

    public function show(string $identifier)
    {
        abort_unless(ctype_digit($identifier), 404);

        $watch = Watch::query()
            ->with([
                'primaryImage',
                'images' => function ($query) {
                    $query
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->where('id', (int) $identifier)
            ->firstOrFail();

        abort_unless(
            $watch->is_visible && in_array($watch->status, self::LISTED_STATUSES, true),
            404
        );

        return Inertia::render('WatchDetails', [
            'watch' => $this->watchDetails($watch),
            'otherWatches' => $this->randomWatchCards($watch),
        ]);
    }

    private function watchCard(Watch $watch): array
    {
        $actualPrice = $this->actualWatchPrice($watch);
        $displayName = $this->watchDisplayName($watch);
        $status = $this->normalizeWatchStatus($watch->status);

        return [
            'id' => $watch->id,
            'url' => url('/watches/' . $watch->id),

            'brand' => $watch->brand,
            'model_name' => $watch->model_name,
            'display_name' => $displayName,
            'name' => $displayName,
            'title' => $displayName,

            'reference_number' => $watch->reference_number,
            'condition' => $watch->condition,
            'description' => $watch->description,
            'category' => $watch->category,
            'gender' => $this->watchGender($watch),
            'gender_label' => $this->genderLabel($this->watchGender($watch)),

            'movement' => $watch->movement,
            'case_size' => $watch->case_size,
            'case_material' => $watch->case_material,
            'dial_color' => $watch->dial_color,
            'crystal' => $watch->crystal,
            'bracelet_or_strap' => $watch->bracelet_or_strap,
            'water_resistance' => $watch->water_resistance,
            'box_papers' => $watch->box_papers,
            'warranty_type' => $watch->warranty_type,

            'selling_price' => $watch->selling_price,
            'discounted_price' => $watch->discounted_price,
            'suggested_srp' => $watch->suggested_srp,
            'actual_price' => $actualPrice,
            'display_price' => $actualPrice,

            'status' => $status,
            'status_label' => $this->watchStatusLabel($status),
            'is_visible' => (bool) $watch->is_visible,
            'is_in_demand' => (bool) ($watch->is_in_demand ?? false),
            'is_featured' => (bool) ($watch->is_featured ?? false),
            'allow_inquiry' => (bool) ($watch->allow_inquiry ?? true),

            'image_url' => $this->watchImageUrl($watch),
        ];
    }

    private function watchDetails(Watch $watch): array
    {
        $images = $watch->images
            ? $watch->images
                ->map(fn ($image) => [
                    'id' => $image->id,
                    'image_url' => $this->storageUrl($image->image_path),
                    'is_primary' => (bool) $image->is_primary,
                ])
                ->filter(fn ($image) => filled($image['image_url']))
                ->values()
            : collect();

        if ($images->isEmpty() && $watch->primaryImage) {
            $images = collect([
                [
                    'id' => $watch->primaryImage->id,
                    'image_url' => $this->storageUrl($watch->primaryImage->image_path),
                    'is_primary' => true,
                ],
            ]);
        }

        return [
            ...$this->watchCard($watch),
            'images' => $images->values(),
            'case_material' => $watch->case_material,
            'crystal' => $watch->crystal,
            'bracelet_or_strap' => $watch->bracelet_or_strap,
            'water_resistance' => $watch->water_resistance,
            'warranty_type' => $watch->warranty_type,
            'box_papers' => $watch->box_papers,
        ];
    }

    private function visibleSoldWatches()
    {
        return Watch::query()
            ->with($this->watchCardRelations())
            ->where('status', 'sold')
            ->where('is_visible', true)
            ->latest('date_sold')
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(function (Watch $watch) {
                $displayName = $this->watchDisplayName($watch);

                return [
                    'id' => $watch->id,
                    'watch_id' => $watch->id,
                    'title' => $displayName,
                    'caption' => trim((string) ($watch->brand ?: '')) ?: 'Thank you for trusting Watch Gallery Manila.',
                    'transaction_date' => $watch->date_sold ?: $watch->created_at,
                    'status' => 'sold',
                    'status_label' => 'Sold',
                    'image_url' => $this->watchImageUrl($watch),
                ];
            })
            ->values();
    }

    private function randomWatchCards(Watch $currentWatch)
    {
        return Watch::query()
            ->with($this->watchCardRelations())
            ->whereIn('status', self::LISTED_STATUSES)
            ->where('is_visible', true)
            ->where('id', '!=', $currentWatch->id)
            ->inRandomOrder()
            ->limit(10)
            ->get()
            ->map(fn ($watch) => $this->watchCard($watch))
            ->values();
    }

    private function aboutUsContent()
    {
        $query = AboutUsContent::query();

        if (method_exists(AboutUsContent::class, 'images')) {
            $query->with([
                'images' => function ($imageQuery) {
                    if (Schema::hasColumn('about_us_images', 'sort_order')) {
                        $imageQuery->orderBy('sort_order');
                    }

                    $imageQuery->orderBy('id');
                },
            ]);
        }

        if (Schema::hasColumn('about_us_contents', 'is_active')) {
            $query->where('is_active', true);
        }

        $aboutUs = $query->latest('id')->first();

        if (! $aboutUs) {
            return null;
        }

        return [
            'id' => $aboutUs->id,
            'eyebrow' => $aboutUs->eyebrow,
            'title' => $aboutUs->title,
            'body' => $aboutUs->body,
            'dealer_name' => $aboutUs->dealer_name,
            'dealer_message' => $aboutUs->dealer_message,
            'owner_bio' => $aboutUs->owner_bio,
            'owner_image_url' => $this->storageUrl($aboutUs->owner_image_path),
            'images' => $aboutUs->images
                ? $aboutUs->images
                    ->sortBy([
                        ['sort_order', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->map(fn ($image) => [
                        'id' => $image->id,
                        'caption' => $image->caption,
                        'sort_order' => $image->sort_order ?? 0,
                        'is_primary' => (bool) ($image->is_primary ?? false),
                        'image_url' => $this->storageUrl(
                            $image->image_path
                                ?: ($image->image ?? null)
                                ?: ($image->photo_path ?? null)
                        ),
                    ])
                    ->filter(fn ($image) => filled($image['image_url']))
                    ->values()
                : [],
        ];
    }


    private function watchCardRelations(): array
    {
        return [
            'primaryImage',
            'images' => function ($query) {
                $query
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->orderBy('id');
            },
        ];
    }

    private function watchImageUrl(Watch $watch): ?string
    {
        if ($watch->primaryImage?->image_path) {
            return $this->storageUrl($watch->primaryImage->image_path);
        }

        if ($watch->relationLoaded('images')) {
            $fallbackImage = $watch->images
                ? $watch->images->first(fn ($image) => filled($image->image_path))
                : null;

            if ($fallbackImage?->image_path) {
                return $this->storageUrl($fallbackImage->image_path);
            }
        }

        return null;
    }

    private function normalizeSortFilter($value): string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'newest', 'latest' => 'newest',
            'price_low', 'price-low', 'low_to_high', 'low-to-high' => 'price_low',
            'price_high', 'price-high', 'high_to_low', 'high-to-low' => 'price_high',
            'in_demand', 'indemand', 'demand' => 'in_demand',
            default => 'random',
        };
    }

    private function applyCollectionSort($query, string $sort, int $randomSeed, bool $hasInDemandColumn): void
    {
        if ($sort === 'price_low') {
            $query
                ->orderByRaw('COALESCE(discounted_price, selling_price, 999999999) ASC')
                ->orderBy('id');

            return;
        }

        if ($sort === 'price_high') {
            $query
                ->orderByRaw('COALESCE(discounted_price, selling_price, 0) DESC')
                ->orderByDesc('id');

            return;
        }

        if ($sort === 'in_demand') {
            if ($hasInDemandColumn) {
                $query->orderByDesc('is_in_demand');
            }

            $query->latest('id');

            return;
        }

        if ($sort === 'newest') {
            $query->latest('id');

            return;
        }

        $query->orderByRaw('RAND(' . (int) $randomSeed . ')');
    }

    private function normalizeGenderFilter($value): string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'mens', 'men', "men's", 'male' => 'men',
            'womens', 'women', "women's", 'female' => 'women',
            'unisex', 'uni-sex', 'both' => 'unisex',
            default => '',
        };
    }

    private function normalizeSearchTerm(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $value));
    }

    private function normalizeWatchStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        $status = str_replace(['-', ' '], '_', $status);

        return match ($status) {
            'reserved' => 'reserved',
            'in_transit', 'intransit', 'transit' => 'in_transit',
            'sold' => 'sold',
            default => 'available',
        };
    }

    private function watchStatusLabel(?string $status): string
    {
        return match ($this->normalizeWatchStatus($status)) {
            'reserved' => 'Reserved',
            'in_transit' => 'In Transit',
            'sold' => 'Sold',
            default => 'Available',
        };
    }

    private function watchGender(Watch $watch): string
    {
        $gender = $this->normalizeGenderFilter($watch->gender ?? '');

        if ($gender !== '') {
            return $gender;
        }

        $categoryGender = $this->normalizeGenderFilter($watch->category ?? '');

        return $categoryGender !== '' ? $categoryGender : 'unisex';
    }

    private function genderLabel(?string $gender): string
    {
        return match ($gender) {
            'men' => 'Men',
            'women' => 'Women',
            default => 'Unisex',
        };
    }

    private function watchDisplayName(Watch $watch): string
    {
        return trim((string) (
            $watch->model_name
            ?: $watch->reference_number
            ?: $watch->brand
            ?: 'Watch Details'
        ));
    }

    private function actualWatchPrice(Watch $watch)
    {
        return $watch->discounted_price
            ?: $watch->selling_price
            ?: $watch->sold_price
            ?: null;
    }

    private function storageUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return '/' . $path;
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        return Storage::url($path);
    }
}
