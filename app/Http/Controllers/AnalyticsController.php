<?php

namespace App\Http\Controllers;

use App\Models\PageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AnalyticsController extends Controller
{
    private const VALID_AFTER_SECONDS = 5;
    private const ENGAGED_AFTER_SECONDS = 10;
    private const SAME_PAGE_WINDOW_MINUTES = 30;
    private const MAX_DURATION_SECONDS = 1800;

    public function start(Request $request)
    {
        $validated = $request->validate([
            'visitor_id' => ['required', 'string', 'max:120'],
            'session_id' => ['required', 'string', 'max:120'],
            'page_path' => ['required', 'string', 'max:500'],
            'page_url' => ['nullable', 'string', 'max:1000'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'page_type' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:1000'],
            'device' => ['nullable', 'string', 'max:255'],
        ]);

        $pagePath = $this->normalizePagePath($validated['page_path']);
        $userAgent = (string) $request->userAgent();
        $isBot = $this->isBotUserAgent($userAgent);

        if ($this->shouldIgnorePath($pagePath)) {
            return response()->json([
                'ignored' => true,
            ]);
        }

        $existingVisit = PageVisit::query()
            ->where('visitor_id', $validated['visitor_id'])
            ->where('page_path', $pagePath)
            ->where('last_seen_at', '>=', now()->subMinutes(self::SAME_PAGE_WINDOW_MINUTES))
            ->latest('last_seen_at')
            ->first();

        if ($existingVisit) {
            $existingVisit->update([
                'session_id' => $validated['session_id'],
                'page_url' => $validated['page_url'] ?? $existingVisit->page_url,
                'page_title' => $validated['page_title'] ?? $existingVisit->page_title,
                'page_type' => $validated['page_type'] ?? $existingVisit->page_type,
                'referrer' => $validated['referrer'] ?? $existingVisit->referrer,
                'device' => $validated['device'] ?? $existingVisit->device,
                'last_seen_at' => now(),
            ]);

            return response()->json([
                'visit_id' => $existingVisit->id,
                'baseline_duration_seconds' => (int) $existingVisit->duration_seconds,
                'is_existing' => true,
            ]);
        }

        $visit = PageVisit::create([
            'visitor_id' => $validated['visitor_id'],
            'session_id' => $validated['session_id'],
            'page_path' => $pagePath,
            'page_url' => $validated['page_url'] ?? null,
            'page_title' => $validated['page_title'] ?? null,
            'page_type' => $validated['page_type'] ?? null,
            'referrer' => $validated['referrer'] ?? null,
            'device' => $validated['device'] ?? null,
            'user_agent' => $userAgent,
            'ip_hash' => $this->hashIp($request->ip()),
            'duration_seconds' => 0,
            'interactions_count' => 0,
            'is_valid' => false,
            'is_engaged' => false,
            'is_bot' => $isBot,
            'entered_at' => now(),
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'visit_id' => $visit->id,
            'baseline_duration_seconds' => 0,
            'is_existing' => false,
        ]);
    }

    public function ping(Request $request)
    {
        $validated = $request->validate([
            'visit_id' => ['required', 'integer', 'exists:page_visits,id'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
            'interactions_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $visit = PageVisit::findOrFail($validated['visit_id']);

        $durationSeconds = min(
            max((int) $visit->duration_seconds, (int) $validated['duration_seconds']),
            self::MAX_DURATION_SECONDS
        );

        $interactionsCount = max(
            (int) $visit->interactions_count,
            (int) ($validated['interactions_count'] ?? 0)
        );

        $isValid = ! $visit->is_bot && $durationSeconds >= self::VALID_AFTER_SECONDS;

        $isEngaged = ! $visit->is_bot && (
            $durationSeconds >= self::ENGAGED_AFTER_SECONDS ||
            $interactionsCount > 0
        );

        $payload = [
            'duration_seconds' => $durationSeconds,
            'interactions_count' => $interactionsCount,
            'is_valid' => $isValid,
            'is_engaged' => $isEngaged,
            'last_seen_at' => now(),
        ];

        if ($isValid && ! $visit->validated_at) {
            $payload['validated_at'] = now();
        }

        $visit->update($payload);

        return response()->json([
            'ok' => true,
        ]);
    }

    private function normalizePagePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '/';
        }

        if (! Str::startsWith($path, '/')) {
            $path = '/' . $path;
        }

        return strtok($path, '?') ?: '/';
    }

    private function shouldIgnorePath(string $path): bool
    {
        return Str::startsWith($path, [
            '/admin',
            '/dashboard',
            '/login',
            '/register',
            '/profile',
            '/analytics',
        ]);
    }

    private function isBotUserAgent(string $userAgent): bool
    {
        $userAgent = Str::lower($userAgent);

        $botKeywords = [
            'bot',
            'crawler',
            'spider',
            'slurp',
            'facebookexternalhit',
            'whatsapp',
            'telegrambot',
            'preview',
            'curl',
            'wget',
            'python',
        ];

        foreach ($botKeywords as $keyword) {
            if (Str::contains($userAgent, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function hashIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        return hash('sha256', $ip . config('app.key'));
    }
}