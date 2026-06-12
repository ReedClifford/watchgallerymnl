<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutUsContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AboutUsController extends Controller
{
    public function edit()
    {
        $aboutUs = AboutUsContent::query()
            ->with([
                'images' => function ($query) {
                    $query
                        ->orderByDesc('is_primary')
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->firstOrCreate(
                ['id' => 1],
                [
                    'eyebrow' => 'About Us',
                    'title' => 'Meet your watch dealer',
                    'body' => 'Welcome to Watch Gallery Manila.',
                    'dealer_name' => 'Nel Miranda',
                    'dealer_message' => "I'm Nel Miranda pala, your watch dealer. Hope to meet you soon!",
                    'owner_bio' => 'Your trusted watch dealer, helping clients find quality timepieces with clear details, smooth transactions, and reliable after-sales support.',
                    'is_active' => true,
                ]
            );

        $this->ensurePrimaryImage($aboutUs);

        $aboutUs->load([
            'images' => function ($query) {
                $query
                    ->orderByDesc('is_primary')
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
                'is_active' => $aboutUs->is_active,
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
            | These are the main About Us carousel photos.
            */
            'images' => ['nullable', 'array', 'max:8'],
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
        ]);

        $aboutUs = AboutUsContent::query()->firstOrCreate(
            ['id' => 1],
            [
                'eyebrow' => 'About Us',
                'title' => 'Meet your watch dealer',
                'body' => 'Welcome to Watch Gallery Manila.',
                'dealer_name' => 'Nel Miranda',
                'dealer_message' => "I'm Nel Miranda pala, your watch dealer. Hope to meet you soon!",
                'owner_bio' => 'Your trusted watch dealer, helping clients find quality timepieces with clear details, smooth transactions, and reliable after-sales support.',
                'is_active' => true,
            ]
        );

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
        $removeImageIds = collect($validated['remove_image_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

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

        if ($request->hasFile('images')) {
            $nextSort = (int) $aboutUs->images()->max('sort_order') + 1;

            foreach ($request->file('images') as $file) {
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
        | Set Primary Showroom Image
        |--------------------------------------------------------------------------
        */
        $primaryImageId = $validated['primary_image_id'] ?? null;
        $primaryNewImageIndex = $validated['primary_new_image_index'] ?? null;

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

        return back()->with('success', 'About Us section updated successfully.');
    }

    private function ensurePrimaryImage(AboutUsContent $aboutUs): void
    {
        $imagesQuery = $aboutUs->images();

        if (! $imagesQuery->exists()) {
            return;
        }

        $hasPrimary = $aboutUs->images()
            ->where('is_primary', true)
            ->exists();

        if ($hasPrimary) {
            $primaryImage = $aboutUs->images()
                ->where('is_primary', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

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
}