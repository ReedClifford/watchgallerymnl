<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutUsContent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AboutUsController extends Controller
{
    private const MAX_SHOWROOM_IMAGES = 6;

    public function edit()
    {
        $aboutUs = AboutUsContent::query()
            ->with([
                'images' => function ($query) {
                    $query
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->firstOrCreate(
                ['id' => 1],
                $this->defaultAboutUsContent()
            );

        $this->ensurePrimaryImage($aboutUs);

        $aboutUs->load([
            'images' => function ($query) {
                $query
                    ->orderBy('sort_order')
                    ->orderBy('id');
            },
        ]);

        return Inertia::render('Admin/AboutUs/Edit', [
            'aboutUs' => [
                'id' => $aboutUs->id,
                'eyebrow' => $aboutUs->eyebrow,
                'title' => $aboutUs->title,
                'body' => $aboutUs->body,
                'dealer_name' => $aboutUs->dealer_name,
                'dealer_message' => $aboutUs->dealer_message,
                'owner_bio' => $aboutUs->owner_bio,
                'owner_image_url' => $aboutUs->owner_image_path
                    ? Storage::url($aboutUs->owner_image_path)
                    : null,
                'is_active' => (bool) $aboutUs->is_active,
                'max_showroom_images' => self::MAX_SHOWROOM_IMAGES,
                'images' => $aboutUs->images
                    ->map(fn ($image) => [
                        'id' => $image->id,
                        'caption' => $image->caption,
                        'sort_order' => $image->sort_order,
                        'is_primary' => (bool) $image->is_primary,
                        'image_url' => Storage::url($image->image_path),
                    ])
                    ->values(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'dealer_name' => ['nullable', 'string', 'max:255'],
            'dealer_message' => ['nullable', 'string'],
            'owner_bio' => ['nullable', 'string'],
            'is_active' => ['boolean'],

            /*
            |--------------------------------------------------------------------------
            | Owner Photo
            |--------------------------------------------------------------------------
            | This is separate from showroom carousel photos.
            */
            'owner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_owner_image' => ['nullable', 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | Showroom Carousel Photos
            |--------------------------------------------------------------------------
            | Max 6 total active showroom photos after delete + upload.
            */
            'images' => ['nullable', 'array', 'max:' . self::MAX_SHOWROOM_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],

            /*
            |--------------------------------------------------------------------------
            | Primary Showroom Image
            |--------------------------------------------------------------------------
            | primary_image_id = existing image
            | primary_new_image_index = newly uploaded image index
            */
            'primary_image_id' => ['nullable', 'integer'],
            'primary_new_image_index' => ['nullable', 'integer', 'min:0'],

            /*
            |--------------------------------------------------------------------------
            | Showroom Image Ordering
            |--------------------------------------------------------------------------
            | gallery_order supports mixed existing/new tokens:
            | - existing:12, existing-12, existing_12
            | - new:0, new-0, new_0
            | - { type: "existing", id: 12 }
            | - { type: "new", index: 0 }
            |
            | existing_image_order is a fallback list of existing image IDs.
            */
            'gallery_order' => ['nullable', 'array'],
            'gallery_order.*' => ['nullable'],
            'existing_image_order' => ['nullable', 'array'],
            'existing_image_order.*' => ['integer'],
        ]);

        $aboutUs = AboutUsContent::query()->firstOrCreate(
            ['id' => 1],
            $this->defaultAboutUsContent()
        );

        $removeImageIds = collect($validated['remove_image_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $uploadedFiles = $this->uploadedShowroomFiles($request);

        /*
        |--------------------------------------------------------------------------
        | Enforce 6 showroom photos total
        |--------------------------------------------------------------------------
        | Count active existing images after removals + newly uploaded photos.
        */
        $existingAfterRemovalCount = $aboutUs->images()
            ->when(
                $removeImageIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $removeImageIds)
            )
            ->count();

        $totalAfterSave = $existingAfterRemovalCount + count($uploadedFiles);

        if ($totalAfterSave > self::MAX_SHOWROOM_IMAGES) {
            throw ValidationException::withMessages([
                'images' => "Showroom gallery allows a maximum of " . self::MAX_SHOWROOM_IMAGES . " photos only. You will have {$totalAfterSave} photos after saving.",
            ]);
        }

        $primaryNewImageIndex = $validated['primary_new_image_index'] ?? null;

        if (
            $primaryNewImageIndex !== null &&
            ! array_key_exists((int) $primaryNewImageIndex, $uploadedFiles)
        ) {
            throw ValidationException::withMessages([
                'primary_new_image_index' => 'The selected new primary photo is no longer available.',
            ]);
        }

        $aboutUs->update([
            'eyebrow' => $validated['eyebrow'] ?? 'About Us',
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'dealer_name' => $validated['dealer_name'] ?? null,
            'dealer_message' => $validated['dealer_message'] ?? null,
            'owner_bio' => $validated['owner_bio'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Remove Owner Photo
        |--------------------------------------------------------------------------
        */
        if ($request->boolean('remove_owner_image') && $aboutUs->owner_image_path) {
            Storage::disk('public')->delete($aboutUs->owner_image_path);

            $aboutUs->update([
                'owner_image_path' => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Upload / Replace Owner Photo
        |--------------------------------------------------------------------------
        */
        if ($request->hasFile('owner_image')) {
            if ($aboutUs->owner_image_path) {
                Storage::disk('public')->delete($aboutUs->owner_image_path);
            }

            $path = $request->file('owner_image')->store('about-us/owner', 'public');

            $aboutUs->update([
                'owner_image_path' => $path,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Showroom Carousel Photos
        |--------------------------------------------------------------------------
        */
        if ($removeImageIds->isNotEmpty()) {
            $imagesToRemove = $aboutUs->images()
                ->whereIn('id', $removeImageIds)
                ->get();

            foreach ($imagesToRemove as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Upload Showroom Carousel Photos
        |--------------------------------------------------------------------------
        */
        $uploadedImages = collect();

        if (count($uploadedFiles)) {
            $nextSort = (int) $aboutUs->images()->max('sort_order') + 1;

            foreach ($uploadedFiles as $file) {
                $path = $file->store('about-us/showroom', 'public');

                $uploadedImages->push(
                    $aboutUs->images()->create([
                        'image_path' => $path,
                        'caption' => null,
                        'sort_order' => $nextSort++,
                        'is_primary' => false,
                    ])
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Apply Showroom Photo Order
        |--------------------------------------------------------------------------
        | This persists the arrange order from the Vue form.
        */
        $this->applyShowroomImageOrder(
            aboutUs: $aboutUs,
            uploadedImages: $uploadedImages,
            galleryOrder: $validated['gallery_order'] ?? [],
            existingImageOrder: $validated['existing_image_order'] ?? []
        );

        /*
        |--------------------------------------------------------------------------
        | Set Primary Showroom Image
        |--------------------------------------------------------------------------
        */
        $primaryImageId = $validated['primary_image_id'] ?? null;
        $selectedPrimaryImage = null;

        if ($primaryNewImageIndex !== null) {
            $selectedPrimaryImage = $uploadedImages->get((int) $primaryNewImageIndex);
        }

        if (! $selectedPrimaryImage && $primaryImageId) {
            $selectedPrimaryImage = $aboutUs->images()
                ->where('id', (int) $primaryImageId)
                ->first();
        }

        if ($selectedPrimaryImage) {
            $aboutUs->images()->update([
                'is_primary' => false,
            ]);

            $selectedPrimaryImage->update([
                'is_primary' => true,
            ]);
        }

        $this->ensurePrimaryImage($aboutUs);
        $this->normalizeShowroomSortOrder($aboutUs);

        return back()->with('success', 'About Us section updated successfully.');
    }

    private function defaultAboutUsContent(): array
    {
        return [
            'eyebrow' => 'About Us',
            'title' => 'Meet your watch dealer',
            'body' => 'Welcome to Watch Gallery Manila.',
            'dealer_name' => 'Nel Miranda',
            'dealer_message' => "I'm Nel Miranda pala, your watch dealer. Hope to meet you soon!",
            'owner_bio' => 'Your trusted watch dealer, helping clients find quality timepieces with clear details, smooth transactions, and reliable after-sales support.',
            'is_active' => true,
        ];
    }

    private function uploadedShowroomFiles(Request $request): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        $files = $request->file('images');

        if (! is_array($files)) {
            return [$files];
        }

        return array_values($files);
    }

    private function applyShowroomImageOrder(
        AboutUsContent $aboutUs,
        Collection $uploadedImages,
        array $galleryOrder = [],
        array $existingImageOrder = []
    ): void {
        $orderedImageIds = collect($galleryOrder)
            ->map(fn ($item) => $this->resolveGalleryOrderItem($item, $uploadedImages))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Fallback for older frontend payload
        |--------------------------------------------------------------------------
        | If gallery_order is not present, still honor existing_image_order.
        */
        if ($orderedImageIds->isEmpty() && ! empty($existingImageOrder)) {
            $orderedImageIds = collect($existingImageOrder)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        $validImageIds = $aboutUs->images()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $orderedImageIds = $orderedImageIds
            ->filter(fn ($id) => $validImageIds->contains((int) $id))
            ->values();

        $remainingImageIds = $aboutUs->images()
            ->when(
                $orderedImageIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $orderedImageIds)
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $finalImageIds = $orderedImageIds
            ->merge($remainingImageIds)
            ->unique()
            ->values();

        foreach ($finalImageIds as $index => $imageId) {
            $aboutUs->images()
                ->where('id', (int) $imageId)
                ->update([
                    'sort_order' => $index + 1,
                ]);
        }
    }

    private function resolveGalleryOrderItem(mixed $item, Collection $uploadedImages): ?int
    {
        if (is_array($item)) {
            $type = $item['type'] ?? $item['kind'] ?? null;

            if ($type === 'existing' && isset($item['id'])) {
                return (int) $item['id'];
            }

            if ($type === 'new' && isset($item['index'])) {
                return $uploadedImages->get((int) $item['index'])?->id;
            }

            return null;
        }

        if (is_numeric($item)) {
            return (int) $item;
        }

        if (! is_string($item)) {
            return null;
        }

        $token = trim($item);

        if (preg_match('/^existing[:\-_](\d+)$/', $token, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^new[:\-_](\d+)$/', $token, $matches)) {
            return $uploadedImages->get((int) $matches[1])?->id;
        }

        return null;
    }

    private function ensurePrimaryImage(AboutUsContent $aboutUs): void
    {
        if (! $aboutUs->images()->exists()) {
            return;
        }

        $primaryImage = $aboutUs->images()
            ->where('is_primary', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($primaryImage) {
            $aboutUs->images()
                ->where('id', '!=', $primaryImage->id)
                ->update([
                    'is_primary' => false,
                ]);

            return;
        }

        $firstImage = $aboutUs->images()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($firstImage) {
            $firstImage->update([
                'is_primary' => true,
            ]);
        }
    }

    private function normalizeShowroomSortOrder(AboutUsContent $aboutUs): void
    {
        $aboutUs->images()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values()
            ->each(function ($image, int $index) {
                $image->update([
                    'sort_order' => $index + 1,
                ]);
            });
    }
}
