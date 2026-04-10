<?php

namespace App\Services;

use App\Models\Research;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ResearchSummaryService
{
    private OllamaService $ollama;

    private int $cacheMinutes;

    public function __construct(?OllamaService $ollama = null)
    {
        $this->ollama = $ollama ?? new OllamaService();
        $this->cacheMinutes = (int) config('services.ollama.cache_minutes', 60);
    }

    public function generateForResearch(Research $research): array
    {
        $abstract = trim(strip_tags((string) $research->abstract));

        if ($abstract === '') {
            return [
                'summary' => null,
                'source' => 'none',
            ];
        }

        $cacheKey = 'research_summary:'.$research->id.':'.md5($abstract.'|'.$research->updated_at);

        if (config('services.ollama.enabled', false) && $this->ollama->isAvailable()) {
            $aiSummary = Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($research, $abstract) {
                return $this->generateWithOllama($research->title, $abstract);
            });

            if ($aiSummary) {
                return [
                    'summary' => $aiSummary,
                    'source' => 'ollama',
                ];
            }

            Cache::forget($cacheKey);
        }

        return [
            'summary' => $this->generateFallbackSummary($abstract),
            'source' => 'fallback',
        ];
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
