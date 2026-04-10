<?php

namespace App\Services;

use App\Models\Research;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RelatedResearchService
{
    private OllamaService $ollama;

    private int $cacheMinutes;

    private array $stopWords = [
        'about', 'after', 'also', 'among', 'analysis', 'and', 'application', 'approach', 'assessment', 'based',
        'between', 'can', 'case', 'data', 'design', 'development', 'effect', 'effects', 'for', 'from', 'implementation',
        'importance', 'important', 'into', 'its', 'method', 'methods', 'model', 'models', 'new', 'not', 'of', 'paper',
        'research', 'results', 'role', 'school', 'study', 'system', 'that', 'the', 'their', 'there', 'these', 'this',
        'thesis', 'through', 'using', 'web', 'with', 'within', 'your', 'than', 'such', 'used', 'use', 'into', 'was',
    ];

    public function __construct(?OllamaService $ollama = null)
    {
        $this->ollama = $ollama ?? new OllamaService();
        $this->cacheMinutes = (int) config('services.ollama.cache_minutes', 60);
    }

    public function generateForResearch(Research $research): array
    {
        $cacheKey = 'related_research:v2:'.$research->id.':'.md5(implode('|', [
            $research->title,
            $research->abstract,
            $research->keywords,
            $research->updated_at,
        ]));

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($research) {
            $candidates = $this->collectCandidates($research);

            if ($candidates->isEmpty()) {
                return [
                    'items' => [],
                    'source' => 'none',
                ];
            }

            if (config('services.ollama.enabled', false) && $this->ollama->isAvailable()) {
                $ranked = $this->rerankWithOllama($research, $candidates);

                if (! empty($ranked)) {
                    return [
                        'items' => $ranked,
                        'source' => 'ollama',
                    ];
                }
            }

            return [
                'items' => $this->fallbackMatches($candidates),
                'source' => 'fallback',
            ];
        });
    }

    private function collectCandidates(Research $research): Collection
    {
        $keywords = $this->extractKeywords($research);

        $query = Research::with(['college', 'category'])
            ->where('id', '!=', $research->id)
            ->where(function ($builder) use ($research, $keywords) {
                if ($research->category_id) {
                    $builder->where('category_id', $research->category_id)
                        ->orWhere('college_id', $research->college_id);
                }

                if (! empty($keywords)) {
                    foreach (array_slice($keywords, 0, 6) as $keyword) {
                        $builder->orWhere('keywords', 'like', '%'.$keyword.'%')
                            ->orWhere('title', 'like', '%'.$keyword.'%')
                            ->orWhere('abstract', 'like', '%'.$keyword.'%');
                    }
                }
            });

        return $query->limit(20)->get()
            ->filter(fn (Research $candidate) => (int) $candidate->id !== (int) $research->id)
            ->map(function (Research $candidate) use ($research, $keywords) {
                $candidateKeywords = $this->extractKeywords($candidate);
                $sharedKeywords = array_values(array_intersect($keywords, $candidateKeywords));
                $score = count($sharedKeywords) * 4;

                if ((int) $candidate->category_id === (int) $research->category_id) {
                    $score += 3;
                }

                if ((int) $candidate->college_id === (int) $research->college_id) {
                    $score += 2;
                }

                $currentTokens = $this->tokenize($research->title.' '.$research->abstract);
                $candidateTokens = $this->tokenize($candidate->title.' '.$candidate->abstract);
                $contentOverlap = count(array_intersect($currentTokens, $candidateTokens));
                $score += min($contentOverlap, 8);

                $candidate->match_score = $score;
                $candidate->shared_keywords = array_slice($sharedKeywords, 0, 4);
                $candidate->match_reason = $this->buildFallbackReason($candidate);

                return $candidate;
            })
            ->filter(fn (Research $candidate) => $candidate->match_score > 0)
            ->sortByDesc('match_score')
            ->take(8)
            ->values();
    }

    private function rerankWithOllama(Research $research, Collection $candidates): array
    {
        $candidateText = $candidates->map(function (Research $candidate) {
            return [
                'id' => $candidate->id,
                'title' => $candidate->title,
                'keywords' => $candidate->keywords,
                'abstract' => $this->truncateText((string) $candidate->abstract, 320),
                'score_hint' => $candidate->match_score,
            ];
        })->values()->all();

        $systemPrompt = 'You are matching related academic research papers. Return valid JSON only. Choose up to 3 most related papers. Each item must have id and reason. The reason must be one short sentence explaining the overlap in topic, keywords, method, or problem domain.';

        $prompt = json_encode([
            'current_research' => [
                'title' => $research->title,
                'keywords' => $research->keywords,
                'abstract' => $this->truncateText((string) $research->abstract, 500),
            ],
            'candidate_research' => $candidateText,
            'required_output' => [
                'related' => [
                    ['id' => 1, 'reason' => 'Short reason here.'],
                ],
            ],
        ], JSON_PRETTY_PRINT);

        $response = $this->ollama->chat($prompt, $systemPrompt, [
            'temperature' => 0.2,
            'max_tokens' => 500,
        ]);

        if (! $response) {
            return [];
        }

        $decoded = $this->decodeJsonResponse($response);
        $items = $decoded['related'] ?? null;

        if (! is_array($items)) {
            return [];
        }

        $candidateMap = $candidates->keyBy('id');
        $results = [];

        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $reason = trim((string) ($item['reason'] ?? ''));

            if (! $id || $id === (int) $research->id || ! $candidateMap->has($id)) {
                continue;
            }

            $candidate = $candidateMap->get($id);
            $results[] = [
                'research' => $candidate,
                'reason' => $reason !== '' ? $reason : $candidate->match_reason,
                'shared_keywords' => $candidate->shared_keywords,
            ];
        }

        return array_slice(array_values(array_filter($results, function (array $item) use ($research) {
            return (int) $item['research']->id !== (int) $research->id;
        })), 0, 3);
    }

    private function fallbackMatches(Collection $candidates): array
    {
        return $candidates->filter(fn (Research $candidate) => $candidate->id !== null)
            ->take(3)
            ->map(function (Research $candidate) {
            return [
                'research' => $candidate,
                'reason' => $candidate->match_reason,
                'shared_keywords' => $candidate->shared_keywords,
            ];
        })->values()->all();
    }

    private function buildFallbackReason(Research $candidate): string
    {
        if (! empty($candidate->shared_keywords)) {
            return 'Related through shared keywords such as '.implode(', ', $candidate->shared_keywords).'.';
        }

        if ($candidate->category) {
            return 'Related because it belongs to the same research area: '.$candidate->category->name.'.';
        }

        return 'Related based on overlapping topic and abstract content.';
    }

    private function extractKeywords(Research $research): array
    {
        $keywordField = collect(explode(',', (string) $research->keywords))
            ->map(fn (string $keyword) => $this->normalizeToken($keyword))
            ->filter()
            ->all();

        $contentTokens = $this->tokenize($research->title.' '.$this->truncateText((string) $research->abstract, 400));

        return array_values(array_unique(array_merge($keywordField, array_slice($contentTokens, 0, 12))));
    }

    private function tokenize(string $text): array
    {
        $clean = strtolower(strip_tags($text));
        $clean = preg_replace('/[^a-z0-9\s-]/', ' ', $clean) ?? $clean;
        $parts = preg_split('/\s+/', $clean) ?: [];

        return array_values(array_unique(array_filter(array_map(function (string $part) {
            return $this->normalizeToken($part);
        }, $parts))));
    }

    private function normalizeToken(string $value): ?string
    {
        $token = strtolower(trim($value));
        $token = trim($token, ".,;:!?()[]{}\"'");

        if ($token === '' || strlen($token) < 4 || in_array($token, $this->stopWords, true) || is_numeric($token)) {
            return null;
        }

        return $token;
    }

    private function truncateText(string $text, int $maxLength): string
    {
        $clean = preg_replace('/\s+/', ' ', trim(strip_tags($text))) ?? $text;

        if (strlen($clean) <= $maxLength) {
            return $clean;
        }

        return rtrim(substr($clean, 0, $maxLength), ' .,;:!?').'.';
    }

    private function decodeJsonResponse(string $response): ?array
    {
        $clean = trim($response);
        $clean = preg_replace('/^```json\s*|```$/m', '', $clean) ?? $clean;

        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $clean, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}