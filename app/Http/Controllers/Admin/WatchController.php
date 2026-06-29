<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Watch;
use App\Models\WatchImage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WatchController extends Controller
{
    private const MAX_WATCH_IMAGES = 6;

    private const ADMIN_STATUSES = [
        'available',
        'reserved',
        'sold',
    ];

    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = trim((string) $request->input('search', ''));

        if ($status !== 'all' && ! in_array($status, self::ADMIN_STATUSES, true)) {
            $status = 'all';
        }

        $baseCountQuery = Watch::query()
            ->whereIn('status', self::ADMIN_STATUSES);

        $counts = [
            'all' => (clone $baseCountQuery)->count(),
            'available' => Watch::query()->where('status', 'available')->count(),
            'reserved' => Watch::query()->where('status', 'reserved')->count(),
            'sold' => Watch::query()->where('status', 'sold')->count(),
        ];

        $watches = Watch::query()
            ->with(['primaryImage', 'images'])
            ->whereIn('status', self::ADMIN_STATUSES)
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('brand', 'like', "%{$search}%")
                        ->orWhere('model_name', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('condition', 'like', "%{$search}%")
                        ->orWhere('gender', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(function ($watch) {
                $images = $watch->images
                    ->sortBy([
                        ['sort_order', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->values();

                return [
                    'id' => $watch->id,
                    'brand' => $watch->brand,
                    'model_name' => $watch->model_name,
                    'reference_number' => $watch->reference_number,
                    'condition' => $watch->condition,
                    'gender' => $watch->gender ?? 'unisex',
                    'description' => $watch->description,

                    'movement' => $watch->movement,
                    'case_size' => $watch->case_size,
                    'case_material' => $watch->case_material,
                    'dial_color' => $watch->dial_color,
                    'crystal' => $watch->crystal,
                    'bracelet_or_strap' => $watch->bracelet_or_strap,
                    'water_resistance' => $watch->water_resistance,
                    'box_papers' => $watch->box_papers,

                    'capital_price' => $watch->capital_price,
                    'suggested_srp' => $watch->suggested_srp,
                    'selling_price' => $watch->selling_price,
                    'discounted_price' => $watch->discounted_price,

                    'status' => $watch->status,
                    'is_visible' => (bool) $watch->is_visible,
                    'is_in_demand' => (bool) $watch->is_in_demand,

                    'sold_price' => $watch->sold_price,
                    'date_sold' => optional($watch->date_sold)->format('Y-m-d'),
                    'buyer_name' => $watch->buyer_name,

                    'image_url' => $watch->primaryImage
                        ? Storage::url($watch->primaryImage->image_path)
                        : null,

                    'images' => $images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_path' => $image->image_path,
                            'image_url' => Storage::url($image->image_path),
                            'is_primary' => (bool) $image->is_primary,
                            'sort_order' => $image->sort_order,
                        ];
                    })->values(),
                ];
            });

        return Inertia::render('Admin/Watches/Index', [
            'watches' => $watches,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'counts' => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWatch($request);

        $this->validateTotalImageLimit(null, $request);

        $watchData = $this->prepareWatchData($validated);

        $watch = Watch::create($watchData);

        $uploadedImages = $this->uploadImages($watch, $request);

        $this->applyImageOrder(
            $watch,
            $uploadedImages,
            $request->input('image_order', [])
        );
        $this->applyPrimaryImage($watch, $uploadedImages, $request);
        $this->normalizeImageOrder($watch);

        return redirect()
            ->route('admin.watches.index')
            ->with('success', 'Watch added successfully.');
    }

    public function update(Request $request, Watch $watch)
    {
        $validated = $this->validateWatch($request);

        $this->validateTotalImageLimit($watch, $request);

        $watchData = $this->prepareWatchData($validated);

        $this->deleteRemovedImages($watch, $request->input('removed_image_ids', []));

        $watch->update($watchData);

        $uploadedImages = $this->uploadImages($watch, $request);

        $this->applyImageOrder(
            $watch,
            $uploadedImages,
            $request->input('image_order', [])
        );
        $this->applyPrimaryImage($watch, $uploadedImages, $request);
        $this->normalizeImageOrder($watch);

        return redirect()
            ->route('admin.watches.index')
            ->with('success', 'Watch updated successfully.');
    }

    public function destroy(Watch $watch)
    {
        foreach ($watch->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $watch->delete();

        return redirect()
            ->route('admin.watches.index')
            ->with('success', 'Watch deleted successfully.');
    }

    public function markAsSold(Request $request, Watch $watch)
    {
        $validated = $request->validate([
            'sold_price' => ['nullable', 'numeric', 'min:0'],
            'date_sold' => ['nullable', 'date'],
            'buyer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $soldPrice = $validated['sold_price']
            ?: $watch->discounted_price
            ?: $watch->selling_price;

        $watch->update([
            'status' => 'sold',
            'sold_price' => $soldPrice,
            'date_sold' => $validated['date_sold'] ?? now()->toDateString(),
            'buyer_name' => $validated['buyer_name'] ?? null,
        ]);

        return redirect()
            ->route('admin.watches.index')
            ->with('success', 'Watch marked as sold successfully.');
    }

    public function duplicate(Watch $watch)
    {
        $watch->load([
            'images' => function ($query) {
                $query
                    ->orderBy('sort_order')
                    ->orderBy('id');
            },
        ]);

        $duplicate = $watch->replicate();
        $duplicate->model_name = trim((string) $watch->model_name) . ' (duplicate)';
        $duplicate->status = 'available';
        $duplicate->is_visible = true;
        $duplicate->sold_price = null;
        $duplicate->date_sold = null;
        $duplicate->buyer_name = null;
        $duplicate->save();

        foreach ($watch->images as $image) {
            $copiedPath = $this->copyWatchImagePath($image->image_path);

            if (! $copiedPath) {
                continue;
            }

            WatchImage::create([
                'watch_id' => $duplicate->id,
                'image_path' => $copiedPath,
                'is_primary' => (bool) $image->is_primary,
                'sort_order' => $image->sort_order,
            ]);
        }

        $this->normalizeImageOrder($duplicate);

        return redirect()
            ->route('admin.watches.index')
            ->with('success', 'Watch duplicated successfully.');
    }

    private function validateWatch(Request $request): array
    {
        return $request->validate([
            'brand' => ['required', 'string', 'max:255'],
            'model_name' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', 'string', 'max:255'],

            'gender' => ['nullable', 'in:unisex,men,women'],

            'description' => ['nullable', 'string'],

            'movement' => ['nullable', 'string', 'max:255'],
            'case_size' => ['nullable', 'string', 'max:255'],
            'case_material' => ['nullable', 'string', 'max:255'],
            'dial_color' => ['nullable', 'string', 'max:255'],
            'crystal' => ['nullable', 'string', 'max:255'],
            'bracelet_or_strap' => ['nullable', 'string', 'max:255'],
            'water_resistance' => ['nullable', 'string', 'max:255'],
            'box_papers' => ['nullable', 'string', 'max:255'],

            'capital_price' => ['nullable', 'numeric', 'min:0'],
            'suggested_srp' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'discounted_price' => ['nullable', 'numeric', 'min:0'],

            'status' => ['required', 'in:available,reserved,sold'],
            'is_visible' => ['nullable', 'boolean'],
            'is_in_demand' => ['nullable', 'boolean'],

            'sold_price' => ['nullable', 'numeric', 'min:0'],
            'date_sold' => ['nullable', 'date'],
            'buyer_name' => ['nullable', 'string', 'max:255'],

            'images' => ['nullable', 'array', 'max:' . self::MAX_WATCH_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'removed_image_ids' => ['nullable', 'array'],
            'removed_image_ids.*' => ['integer'],

            'image_order' => ['nullable', 'array'],
            'image_order.*' => ['string', 'max:80'],
            'primary_image_id' => ['nullable', 'integer'],
            'primary_new_image_index' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function prepareWatchData(array $validated): array
    {
        unset(
            $validated['images'],
            $validated['removed_image_ids'],
            $validated['image_order'],
            $validated['primary_image_id'],
            $validated['primary_new_image_index']
        );

        $validated['gender'] = $validated['gender'] ?? 'unisex';

        if (($validated['status'] ?? null) === 'hidden') {
            $validated['status'] = 'available';
        }

        $validated['is_visible'] = filter_var(
            $validated['is_visible'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $validated['is_in_demand'] = filter_var(
            $validated['is_in_demand'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (($validated['status'] ?? null) === 'sold') {
            if (empty($validated['date_sold'])) {
                $validated['date_sold'] = now()->toDateString();
            }

            if (empty($validated['sold_price'])) {
                $validated['sold_price'] = ($validated['discounted_price'] ?? null)
                    ?: ($validated['selling_price'] ?? null);
            }
        }

        if (($validated['status'] ?? null) !== 'sold') {
            $validated['date_sold'] = null;
            $validated['sold_price'] = null;
            $validated['buyer_name'] = null;
        }

        return $validated;
    }

    private function validateTotalImageLimit(?Watch $watch, Request $request): void
    {
        $incomingImagesCount = $request->hasFile('images')
            ? count($request->file('images'))
            : 0;

        if (! $watch) {
            if ($incomingImagesCount > self::MAX_WATCH_IMAGES) {
                throw ValidationException::withMessages([
                    'images' => 'This watch can only have up to ' . self::MAX_WATCH_IMAGES . ' photos.',
                ]);
            }

            return;
        }

        $removedImageIds = collect($request->input('removed_image_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $remainingExistingImagesCount = $watch->images()
            ->when($removedImageIds->isNotEmpty(), function ($query) use ($removedImageIds) {
                $query->whereNotIn('id', $removedImageIds);
            })
            ->count();

        $totalImagesAfterUpdate = $remainingExistingImagesCount + $incomingImagesCount;

        if ($totalImagesAfterUpdate > self::MAX_WATCH_IMAGES) {
            throw ValidationException::withMessages([
                'images' => 'This watch can only have up to ' . self::MAX_WATCH_IMAGES . ' photos. Delete some existing photos first.',
            ]);
        }
    }

    private function deleteRemovedImages(Watch $watch, array $imageIds): void
    {
        if (empty($imageIds)) {
            return;
        }

        $images = $watch->images()
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }
    }

    private function uploadImages(Watch $watch, Request $request): Collection
    {
        $uploadedImages = collect();

        if (! $request->hasFile('images')) {
            return $uploadedImages;
        }

        $currentMaxSort = $watch->images()->max('sort_order') ?? 0;

        foreach ($request->file('images') as $index => $imageFile) {
            $path = $imageFile->store('watches', 'public');

            $uploadedImages->push(
                WatchImage::create([
                    'watch_id' => $watch->id,
                    'image_path' => $path,
                    'is_primary' => false,
                    'sort_order' => $currentMaxSort + $index + 1,
                ])
            );
        }

        return $uploadedImages;
    }

    private function applyImageOrder(Watch $watch, Collection $uploadedImages, array $imageOrder): void
    {
        if (empty($imageOrder)) {
            return;
        }

        $sortOrder = 1;
        $usedImageIds = [];

        foreach ($imageOrder as $orderItem) {
            $image = $this->resolveOrderedImage($watch, $uploadedImages, $orderItem);

            if (! $image || in_array($image->id, $usedImageIds, true)) {
                continue;
            }

            $image->update([
                'sort_order' => $sortOrder,
            ]);

            $usedImageIds[] = $image->id;
            $sortOrder++;
        }

        $watch->images()
            ->whereNotIn('id', $usedImageIds)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->each(function (WatchImage $image) use (&$sortOrder) {
                $image->update([
                    'sort_order' => $sortOrder,
                ]);

                $sortOrder++;
            });
    }

    private function resolveOrderedImage(Watch $watch, Collection $uploadedImages, mixed $orderItem): ?WatchImage
    {
        $orderItem = trim((string) $orderItem);

        if ($orderItem === '') {
            return null;
        }

        if (preg_match('/^new[:_-](\d+)$/', $orderItem, $matches)) {
            return $uploadedImages->values()->get((int) $matches[1]);
        }

        if (preg_match('/^existing[:_-](\d+)$/', $orderItem, $matches)) {
            return $watch->images()
                ->where('id', (int) $matches[1])
                ->first();
        }

        if (ctype_digit($orderItem)) {
            return $watch->images()
                ->where('id', (int) $orderItem)
                ->first();
        }

        return null;
    }

    private function applyPrimaryImage(Watch $watch, Collection $uploadedImages, Request $request): void
    {
        $hasPrimaryImageId = $request->filled('primary_image_id');
        $hasPrimaryNewImageIndex = $request->has('primary_new_image_index')
            && $request->input('primary_new_image_index') !== null
            && $request->input('primary_new_image_index') !== '';

        if (! $hasPrimaryImageId && ! $hasPrimaryNewImageIndex) {
            if (! $watch->images()->where('is_primary', true)->exists()) {
                $this->setFallbackPrimaryImage($watch);
            }

            return;
        }

        $primaryImage = null;

        if ($hasPrimaryImageId) {
            $primaryImage = $watch->images()
                ->where('id', (int) $request->input('primary_image_id'))
                ->first();
        }

        if (! $primaryImage && $hasPrimaryNewImageIndex) {
            $primaryImage = $uploadedImages
                ->values()
                ->get((int) $request->input('primary_new_image_index'));
        }

        if (! $primaryImage) {
            $this->setFallbackPrimaryImage($watch);
            return;
        }

        $watch->images()->update(['is_primary' => false]);

        $primaryImage->update([
            'is_primary' => true,
        ]);
    }

    private function setFallbackPrimaryImage(Watch $watch): void
    {
        $watch->images()->update(['is_primary' => false]);

        $watch->images()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first()
            ?->update(['is_primary' => true]);
    }

    private function copyWatchImagePath(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $safeFilename = Str::slug($filename) ?: 'watch-photo';
        $newFilename = $safeFilename . '-copy-' . Str::random(8);

        if ($extension) {
            $newFilename .= '.' . $extension;
        }

        $newPath = $directory && $directory !== '.'
            ? trim($directory, '/') . '/' . $newFilename
            : $newFilename;

        $disk->copy($path, $newPath);

        return $newPath;
    }

    private function normalizeImageOrder(Watch $watch): void
    {
        $images = $watch->images()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($images as $index => $image) {
            $image->update([
                'sort_order' => $index + 1,
            ]);
        }

        if ($images->isEmpty()) {
            return;
        }

        $hasPrimary = $watch->images()
            ->where('is_primary', true)
            ->exists();

        if (! $hasPrimary) {
            $watch->images()->update(['is_primary' => false]);

            $watch->images()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first()
                ?->update(['is_primary' => true]);
        }
    }
}