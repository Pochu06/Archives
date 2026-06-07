<?php

namespace App\Services;

use App\Jobs\GenerateResearchSummaryJob;
use App\Models\Research;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ResearchSummaryService
{
    private const CACHE_VERSION = 'v1';

    private const PENDING_MINUTES = 15;

    private OllamaService $ollama;

    private int $cacheMinutes;

    public function __construct(?OllamaService $ollama = null)
    {
        $this->ollama = $ollama ?? new OllamaService();
        $this->cacheMinutes = (int) config('services.ollama.cache_minutes', 60);
    }

    public function generateForResearch(Research $research): array
    {
        $abstract = $this->extractAbstract($research);

        if ($abstract === '') {
            return [
                'summary' => null,
                'source' => 'none',
                'pending' => false,
            ];
        }

        $aiSummary = Cache::get($this->aiCacheKey($research));

        if (is_string($aiSummary) && trim($aiSummary) !== '') {
            return [
                'summary' => $aiSummary,
                'source' => 'ollama',
                'pending' => false,
            ];
        }

        return [
            'summary' => $this->generateFallbackSummary($abstract),
            'source' => 'fallback',
            'pending' => $this->isPending($research),
        ];
    }

    public function queueForResearch(Research $research): void
    {
        if (! $this->shouldQueue($research)) {
            return;
        }

        if (! Cache::add($this->pendingCacheKey($research), true, now()->addMinutes(self::PENDING_MINUTES))) {
            return;
        }

        GenerateResearchSummaryJob::dispatchAfterResponse($research->id);
    }

    public function generateAndStoreForResearch(Research $research): void
    {
        try {
            if (! $this->canGenerateAi($research) || Cache::has($this->aiCacheKey($research))) {
                return;
            }

            $aiSummary = $this->generateWithOllama($research->title, $this->extractAbstract($research));

            if ($aiSummary) {
                Cache::put($this->aiCacheKey($research), $aiSummary, now()->addMinutes($this->cacheMinutes));
            }
        } finally {
            Cache::forget($this->pendingCacheKey($research));
        }
    }

    private function extractAbstract(Research $research): string
    {
        return trim(strip_tags((string) $research->abstract));
    }

    private function shouldQueue(Research $research): bool
    {
        return config('services.ollama.enabled', false)
            && $this->extractAbstract($research) !== ''
            && ! Cache::has($this->aiCacheKey($research));
    }

    private function canGenerateAi(Research $research): bool
    {
        return config('services.ollama.enabled', false)
            && $this->extractAbstract($research) !== ''
            && $this->ollama->isAvailable();
    }

    private function isPending(Research $research): bool
    {
        return Cache::has($this->pendingCacheKey($research));
    }

    private function aiCacheKey(Research $research): string
    {
        return 'research_summary_ai:'.self::CACHE_VERSION.':'.$research->id.':'.$this->contentHash($research);
    }

    private function pendingCacheKey(Research $research): string
    {
        return 'research_summary_pending:'.self::CACHE_VERSION.':'.$research->id.':'.$this->contentHash($research);
    }

    private function contentHash(Research $research): string
    {
        return md5($this->extractAbstract($research).'|'.$research->updated_at);
    }

    private function generateWithOllama(string $title, string $abstract): ?string
    {
        $systemPrompt = 'You summarize thesis abstracts for students. Return only one short paragraph in plain English. Keep it to 1 or 2 sentences and no more than 40 words. Do not use bullet points. Do not mention that you are an AI.';

        $prompt = "Create a short thesis summary based only on this abstract.\n\nTitle: {$title}\n\nAbstract:\n{$abstract}";

        $response = $this->ollama->chat($prompt, $systemPrompt, [
            'temperature' => 0.2,
            'max_tokens' => 120,
        ]);

        if (! $response) {
            return null;
        }

        $cleaned = trim(preg_replace('/\s+/', ' ', str_replace(['"', '“', '”'], '', $response)));

        if ($cleaned === '') {
            return null;
        }

        return $this->limitWords($cleaned, 40);
    }

    private function generateFallbackSummary(string $abstract): string
    {
        $plainAbstract = preg_replace('/\s+/', ' ', $abstract) ?? $abstract;
        $sentences = preg_split('/(?<=[.!?])\s+/', $plainAbstract, 2);
        $firstSentence = trim($sentences[0] ?? $plainAbstract);

        if (str_word_count($firstSentence) >= 12) {
            return $this->limitWords($firstSentence, 32);
        }

        return $this->limitWords($plainAbstract, 32);
    }

    private function limitWords(string $text, int $maxWords): string
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];

        if (count($words) <= $maxWords) {
            return rtrim($text);
        }

        $trimmed = implode(' ', array_slice($words, 0, $maxWords));
        $trimmed = rtrim($trimmed, ".,;:!?");

        return Str::finish($trimmed, '.');
    }
}
