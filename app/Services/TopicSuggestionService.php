<?php

namespace App\Services;

use App\Models\Category;
use App\Models\College;
use App\Models\Research;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TopicSuggestionService
{
    private const SEARCHABLE_FIELDS = [
        'title',
        'keywords',
        'abstract',
        'introduction',
        'methodology',
        'results',
        'discussion',
        'conclusion',
        'recommendations',
    ];

    private OllamaService $ollama;

    private int $cacheMinutes;

    private array $stopWords = [
        'about', 'analysis', 'and', 'approach', 'assessment', 'based', 'between', 'capstone', 'data', 'design',
        'effect', 'effects', 'exact', 'field', 'find', 'focus', 'for', 'from', 'important',
        'into', 'keyword', 'keywords', 'looking', 'method', 'methods', 'need', 'paper', 'papers', 'project',
        'research', 'results', 'role', 'student', 'students', 'study', 'that', 'the', 'their', 'these',
        'this', 'thesis', 'topic', 'topics', 'using', 'with', 'within', 'your',
    ];

    private array $phraseSynonyms = [
        'system development' => [
            'design development implementation',
            'development and implementation',
            'information system',
            'management system',
            'monitoring system',
            'web based system',
            'mobile application',
        ],
        'system design' => [
            'design development implementation',
            'prototype development',
            'application design',
        ],
    ];

    private array $termSynonyms = [
        'system' => ['application', 'platform', 'tool'],
        'development' => ['implementation', 'design', 'prototype'],
        'implementation' => ['development', 'design'],
        'app' => ['application', 'mobile app', 'web app'],
        'application' => ['system', 'platform'],
        'monitoring' => ['tracking'],
        'management' => ['monitoring'],
    ];

    public function __construct(?OllamaService $ollama = null)
    {
        $this->ollama = $ollama ?? new OllamaService();
        $this->cacheMinutes = (int) config('services.ollama.cache_minutes', 60);
    }

    public function generate(array $filters, bool $useAi = false): array
    {
        $interest = trim((string) ($filters['interest'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;
        $collegeId = $filters['college_id'] ?? null;

        if ($interest === '' && ! $categoryId && ! $collegeId) {
            return [
                'items' => [],
                'source' => 'none',
                'search_terms' => [],
            ];
        }

        $cacheKey = 'topic_suggestions:v5:'.md5(json_encode([
            'interest' => $interest,
            'category_id' => $categoryId,
            'college_id' => $collegeId,
            'use_ai' => $useAi,
        ]));

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($interest, $categoryId, $collegeId, $useAi) {
            $normalizedInterest = $this->normalizePhrase($interest) ?? '';
            $searchTerms = $this->buildSearchTerms($interest, $normalizedInterest);

            if ($useAi && config('services.ollama.enabled', false) && $this->ollama->isAvailable()) {
                $aiTerms = $this->expandSearchTermsWithOllama($interest, $categoryId, $collegeId);
                $searchTerms = $this->expandSearchTerms(array_merge($searchTerms, $aiTerms), $normalizedInterest);
            }

            $matches = $this->findRelevantResearch($searchTerms, $categoryId, $collegeId);

            if ($useAi && config('services.ollama.enabled', false) && $this->ollama->isAvailable() && $matches->isNotEmpty()) {
                $rankedMatches = $this->rerankWithOllama($interest, $searchTerms, $matches);

                if (! empty($rankedMatches)) {
                    return [
                        'items' => $rankedMatches,
                        'source' => 'ollama',
                        'search_terms' => $searchTerms,
                    ];
                }
            }

            return [
                'items' => $this->formatFallbackMatches($matches, $searchTerms),
                'source' => 'fallback',
                'search_terms' => $searchTerms,
            ];
        });
    }

    private function expandSearchTermsWithOllama(string $interest, $categoryId, $collegeId): array
    {
        $category = $categoryId ? Category::find($categoryId) : null;
        $college = $collegeId ? College::find($collegeId) : null;

        $systemPrompt = 'You help students find existing archived research papers. Return valid JSON only. Extract up to 8 practical search terms or phrases from the student description. Include related academic concepts that may appear in archived paper titles, abstracts, or keywords.';

        $prompt = json_encode([
            'student_need' => $interest,
            'preferred_category' => $category?->name,
            'preferred_college' => $college?->name,
            'required_output' => [
                'search_terms' => ['example term', 'example phrase'],
            ],
        ], JSON_PRETTY_PRINT);

        $response = $this->ollama->chat($prompt, $systemPrompt, [
            'temperature' => 0.2,
            'max_tokens' => 220,
            'timeout' => 15,
        ]);

        if (! $response) {
            return [];
        }

        $decoded = $this->decodeJsonResponse($response);
        $terms = $decoded['search_terms'] ?? [];

        if (! is_array($terms)) {
            return [];
        }

        return $this->expandSearchTerms(array_filter(array_map(function ($term) {
            return $this->normalizePhrase((string) $term);
        }, $terms)), $this->normalizePhrase($interest));
    }

    private function buildSearchTerms(string $interest, string $normalizedInterest = ''): array
    {
        $terms = $this->tokenize($interest);

        if ($normalizedInterest !== '') {
            $terms = array_merge($terms, $this->extractPhraseTerms($normalizedInterest));
        }

        return $this->expandSearchTerms($terms, $normalizedInterest);
    }

    private function findRelevantResearch(array $searchTerms, $categoryId, $collegeId): Collection
    {
        $query = Research::with(['category', 'college'])->approved()
            ->when($categoryId, fn ($builder) => $builder->where('category_id', $categoryId))
            ->when($collegeId, fn ($builder) => $builder->where('college_id', $collegeId));

        if (! empty($searchTerms)) {
            $query->where(function ($builder) use ($searchTerms) {
                foreach (array_slice($searchTerms, 0, 8) as $term) {
                    $builder->orWhere('title', 'like', '%'.$term.'%')
                        ->orWhere('abstract', 'like', '%'.$term.'%')
                        ->orWhere('keywords', 'like', '%'.$term.'%')
                        ->orWhere('introduction', 'like', '%'.$term.'%')
                        ->orWhere('methodology', 'like', '%'.$term.'%')
                        ->orWhere('results', 'like', '%'.$term.'%')
                        ->orWhere('discussion', 'like', '%'.$term.'%')
                        ->orWhere('conclusion', 'like', '%'.$term.'%')
                        ->orWhere('recommendations', 'like', '%'.$term.'%');
                }
            });
        }

        return $query->limit(15)->get()
            ->map(function (Research $research) use ($searchTerms) {
                $matchedFieldMap = $this->collectMatchedFieldMap($searchTerms, $research);
                $matchedTerms = array_keys($matchedFieldMap);
                $research->topic_match_score = count($matchedTerms);
                $research->matched_terms = array_slice($matchedTerms, 0, 4);
                $research->matched_sections = $this->summarizeMatchedSections($matchedFieldMap);

                return $research;
            })
            ->sortByDesc('topic_match_score')
            ->values();
    }

    private function rerankWithOllama(string $interest, array $searchTerms, Collection $matches): array
    {
        $candidatePayload = $matches->take(8)->map(function (Research $research) {
            return [
                'id' => $research->id,
                'title' => $research->title,
                'keywords' => $research->keywords,
                'abstract' => $this->truncateText((string) $research->abstract, 180),
                'match_terms' => $research->matched_terms,
            ];
        })->values()->all();

        $systemPrompt = 'You help students find the most relevant archived research papers. Return valid JSON only. Choose up to 5 papers from the provided candidates. Each item must have id and reason. The reason must briefly explain why the archived paper matches the student need.';

        $prompt = json_encode([
            'student_need' => $interest,
            'search_terms' => $searchTerms,
            'candidate_papers' => $candidatePayload,
            'required_output' => [
                'matches' => [
                    ['id' => 1, 'reason' => 'Short explanation here.'],
                ],
            ],
        ], JSON_PRETTY_PRINT);

        $response = $this->ollama->chat($prompt, $systemPrompt, [
            'temperature' => 0.2,
            'max_tokens' => 350,
            'timeout' => 18,
        ]);

        if (! $response) {
            return [];
        }

        $decoded = $this->decodeJsonResponse($response);
        $items = $decoded['matches'] ?? null;

        if (! is_array($items)) {
            return [];
        }

        $matchMap = $matches->keyBy('id');
        $results = [];

        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $reason = trim((string) ($item['reason'] ?? ''));

            if (! $id || ! $matchMap->has($id)) {
                continue;
            }

            $research = $matchMap->get($id);
            $results[] = [
                'research' => $research,
                'reason' => $reason !== '' ? $reason : $this->buildFallbackReason($research),
                'matched_terms' => $research->matched_terms,
            ];
        }

        return array_slice($results, 0, 5);
    }

    private function formatFallbackMatches(Collection $matches, array $searchTerms): array
    {
        if ($matches->isEmpty()) {
            return [];
        }

        return $matches->take(5)->map(function (Research $research) use ($searchTerms) {
            if (empty($research->matched_terms)) {
                $research->matched_terms = array_slice($searchTerms, 0, 3);
            }

            return [
                'research' => $research,
                'reason' => $this->buildFallbackReason($research),
                'matched_terms' => $research->matched_terms,
                'matched_sections' => $research->matched_sections ?? [],
            ];
        })->values()->all();
    }

    private function buildFallbackReason(Research $research): string
    {
        $matchedSections = $research->matched_sections ?? [];

        if (! empty($research->matched_terms)) {
            $reason = 'Matched archive terms: '.implode(', ', $research->matched_terms).'.';

            if (! empty($matchedSections)) {
                $reason .= ' Found in '.implode(', ', $matchedSections).'.';
            }

            return $reason;
        }

        if (! empty($matchedSections)) {
            return 'Matched through similar content found in '.implode(', ', $matchedSections).'.';
        }

        return 'Matched through similar content in the archived paper chapters.';
    }

    private function tokenize(string $text): array
    {
        $clean = strtolower(strip_tags($text));
        $clean = preg_replace('/[^a-z0-9\s-]/', ' ', $clean) ?? $clean;
        $parts = preg_split('/\s+/', $clean) ?: [];

        return array_values(array_unique(array_filter(array_map(function (string $part) {
            $token = trim($part);

            if ($token === '' || strlen($token) < 4 || in_array($token, $this->stopWords, true) || is_numeric($token)) {
                return null;
            }

            return $token;
        }, $parts))));
    }

    private function expandSearchTerms(array $terms, string $normalizedInterest = ''): array
    {
        $expanded = [];

        if ($normalizedInterest !== '') {
            foreach ($this->phraseSynonyms as $phrase => $synonyms) {
                if (! str_contains($normalizedInterest, $phrase)) {
                    continue;
                }

                $expanded[] = $phrase;

                foreach ($synonyms as $synonym) {
                    $expanded[] = $synonym;
                }
            }
        }

        foreach ($terms as $term) {
            $normalized = $this->normalizePhrase((string) $term);

            if (! $normalized) {
                continue;
            }

            $expanded[] = $normalized;

            foreach ($this->generateWordVariants($normalized) as $variant) {
                $expanded[] = $variant;
            }

            foreach ($this->termSynonyms[$normalized] ?? [] as $synonym) {
                $expanded[] = $synonym;
            }
        }

        return array_values(array_unique(array_filter($expanded)));
    }

    private function extractPhraseTerms(string $normalizedInterest): array
    {
        $phrases = [];

        foreach (array_keys($this->phraseSynonyms) as $phrase) {
            if (str_contains($normalizedInterest, $phrase)) {
                $phrases[] = $phrase;
            }
        }

        return $phrases;
    }

    private function collectMatchedFieldMap(array $searchTerms, Research $research): array
    {
        $matchedFieldMap = [];

        foreach (self::SEARCHABLE_FIELDS as $field) {
            $fieldText = (string) ($research->{$field} ?? '');

            if ($fieldText === '') {
                continue;
            }

            $normalizedFieldText = $this->normalizePhrase($fieldText) ?? '';
            $fieldTerms = $this->tokenize($fieldText);

            foreach ($searchTerms as $term) {
                if (str_contains($term, ' ')) {
                    if ($normalizedFieldText !== '' && str_contains($normalizedFieldText, $term)) {
                        $matchedFieldMap[$term][] = $field;
                    }

                    continue;
                }

                if (in_array($term, $fieldTerms, true)) {
                    $matchedFieldMap[$term][] = $field;
                }
            }
        }

        return collect($matchedFieldMap)
            ->map(fn (array $fields) => array_values(array_unique($fields)))
            ->all();
    }

    private function summarizeMatchedSections(array $matchedFieldMap): array
    {
        $fieldLabels = [
            'title' => 'title',
            'keywords' => 'keywords',
            'abstract' => 'abstract',
            'introduction' => 'introduction',
            'methodology' => 'methodology',
            'results' => 'results',
            'discussion' => 'discussion',
            'conclusion' => 'conclusion',
            'recommendations' => 'recommendations',
        ];

        return collect($matchedFieldMap)
            ->flatten()
            ->unique()
            ->map(fn (string $field) => $fieldLabels[$field] ?? $field)
            ->values()
            ->all();
    }

    private function generateWordVariants(string $term): array
    {
        $variants = [];

        if (str_contains($term, ' ')) {
            return $variants;
        }

        if (str_ends_with($term, 'ies') && strlen($term) > 4) {
            $variants[] = substr($term, 0, -3).'y';
        }

        if (str_ends_with($term, 'ches') || str_ends_with($term, 'shes') || str_ends_with($term, 'sses') || str_ends_with($term, 'xes') || str_ends_with($term, 'zes')) {
            $variants[] = substr($term, 0, -2);
        }

        if (str_ends_with($term, 's') && ! str_ends_with($term, 'ss') && strlen($term) > 4) {
            $variants[] = substr($term, 0, -1);
        }

        if (! str_ends_with($term, 's')) {
            if (str_ends_with($term, 'ch') || str_ends_with($term, 'sh') || str_ends_with($term, 'x') || str_ends_with($term, 'z') || str_ends_with($term, 's')) {
                $variants[] = $term.'es';
            } elseif (str_ends_with($term, 'y') && strlen($term) > 3) {
                $variants[] = substr($term, 0, -1).'ies';
            } else {
                $variants[] = $term.'s';
            }
        }

        return array_values(array_unique(array_filter(array_map(function ($variant) {
            return $this->normalizePhrase($variant);
        }, $variants))));
    }

    private function normalizePhrase(string $text): ?string
    {
        $text = strtolower(trim(strip_tags($text)));
        $text = preg_replace('/[^a-z0-9\s-]/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = trim($text);

        if ($text === '' || strlen($text) < 4) {
            return null;
        }

        return $text;
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