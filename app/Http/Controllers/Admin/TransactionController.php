<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $visibility = $request->input('visibility', 'all');
        $search = trim((string) $request->input('search', ''));

        $counts = [
            'all' => Transaction::query()->count(),
            'visible' => Transaction::query()->where('is_visible', true)->count(),
            'hidden' => Transaction::query()->where('is_visible', false)->count(),
        ];

        $transactions = Transaction::query()
            ->with(['firstImage', 'images'])
            ->when($visibility === 'visible', function ($query) {
                $query->where('is_visible', true);
            })
            ->when($visibility === 'hidden', function ($query) {
                $query->where('is_visible', false);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('caption', 'like', "%{$search}%");
                });
            })
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'title' => $transaction->title,
                    'caption' => $transaction->caption,
                    'transaction_date' => optional($transaction->transaction_date)->format('Y-m-d'),
                    'is_visible' => $transaction->is_visible,
                    'image_url' => $transaction->firstImage
                        ? Storage::url($transaction->firstImage->image_path)
                        : null,
                    'images' => $transaction->images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_path' => $image->image_path,
                            'image_url' => Storage::url($image->image_path),
                            'sort_order' => $image->sort_order,
                        ];
                    }),
                    'images_count' => $transaction->images->count(),
                ];
            });

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => [
                'visibility' => $visibility,
                'search' => $search,
            ],
            'counts' => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateTransaction($request);

        $transaction = Transaction::create($validated);

        $this->uploadImages($transaction, $request);

        return redirect()
            ->route('admin.transactions.index')
            ->with('success', 'Transaction added successfully.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $this->validateTransaction($request);

        $transaction->update($validated);

        $this->uploadImages($transaction, $request);

        return redirect()
            ->route('admin.transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction)
    {
        foreach ($transaction->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $transaction->delete();

        return redirect()
            ->route('admin.transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }

    private function validateTransaction(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'transaction_date' => ['nullable', 'date'],
            'is_visible' => ['boolean'],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }

    private function uploadImages(Transaction $transaction, Request $request): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $currentMaxSort = $transaction->images()->max('sort_order') ?? 0;

        foreach ($request->file('images') as $index => $imageFile) {
            $path = $imageFile->store('transactions', 'public');

            TransactionImage::create([
                'transaction_id' => $transaction->id,
                'image_path' => $path,
                'sort_order' => $currentMaxSort + $index + 1,
            ]);
        }
    }
}