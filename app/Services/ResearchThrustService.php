<?php

namespace App\Services;

use App\Models\Thrust;
use Illuminate\Support\Facades\Cache;

class ResearchThrustService
{
    private const DEFAULT_THRUSTS = [
        [
            'name' => 'Food Security, Self-sufficiency and Safety',
            'description' => 'Research on agriculture, fisheries, food production, sufficiency, affordability, and safe food systems.',
            'keywords' => ['food security', 'agriculture', 'fisheries', 'livestock', 'crop', 'farm', 'nutrition', 'food safety', 'food production', 'self-sufficiency'],
        ],
        [
            'name' => 'Climate Change',
            'description' => 'Research on climate impacts, resilience, mitigation, adaptation, weather extremes, and emissions.',
            'keywords' => ['climate change', 'climate', 'global warming', 'resilience', 'mitigation', 'adaptation', 'emissions', 'carbon', 'weather', 'extreme heat', 'drought', 'flood'],
        ],
        [
            'name' => 'Environmental Resource Management',
            'description' => 'Research on land, air, water, ecosystems, conservation, pollution, and resource protection.',
            'keywords' => ['environment', 'ecosystem', 'conservation', 'pollution', 'waste', 'water quality', 'soil', 'air quality', 'biodiversity', 'resource management', 'forestry'],
        ],
        [
            'name' => 'Human Health and Nutrition',
            'description' => 'Research on health, disease prevention, public health, sanitation, hygiene, medicine, and nutrition.',
            'keywords' => ['health', 'nutrition', 'disease', 'public health', 'sanitation', 'hygiene', 'medicine', 'vaccine', 'diagnostic', 'wellness', 'malnutrition'],
        ],
        [
            'name' => 'Disaster Risk Reduction and Management',
            'description' => 'Research on hazard preparedness, disaster resilience, emergency response, mitigation, and recovery.',
            'keywords' => ['disaster', 'risk reduction', 'risk management', 'preparedness', 'hazard', 'emergency', 'evacuation', 'resilience', 'typhoon', 'earthquake', 'flood'],
        ],
        [
            'name' => 'Sustainable Renewable Energy Sources',
            'description' => 'Research on renewable energy, efficiency, energy security, and clean power systems.',
            'keywords' => ['renewable energy', 'solar', 'wind', 'biomass', 'energy', 'electricity', 'power', 'energy efficiency', 'clean energy', 'microgrid', 'sustainability'],
        ],
        [
            'name' => 'Emerging Technologies',
            'description' => 'Research on artificial intelligence, robotics, biotechnology, nanotechnology, information technology, and other new technologies.',
            'keywords' => ['technology', 'artificial intelligence', 'ai', 'machine learning', 'robotics', 'biotechnology', 'nanotechnology', 'information technology', 'automation', 'data science', 'system'],
        ],
        [
            'name' => 'Social Sciences',
            'description' => 'Research on society, economics, entrepreneurship, governance, higher education, and law.',
            'keywords' => ['social science', 'society', 'economics', 'entrepreneurship', 'education', 'governance', 'law', 'policy', 'community', 'behavior', 'livelihood'],
        ],
    ];

    public static function options(): array
    {
        return self::activeThrustRecords()
            ->pluck('name')
            ->values()
            ->all();
    }

    private static function activeThrustRecords()
    {
        $records = Thrust::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['name', 'description', 'keywords']);

        if ($records->isNotEmpty()) {
            return $records;
        }

        return collect(self::DEFAULT_THRUSTS);
    }

    public function suggest(array $payload, bool $useAi = true): array
    {
        $content = $this->combineContent($payload);

        if ($content === '') {
            return [
                'thrust' => null,
                'thrusts' => [],
                'reason' => 'Add a title, abstract, or keywords to detect the closest CSU thrust.',
                'source' => 'none',
                'confidence' => 0,
            ];
        }

        $thrustSignature = $this->thrustSignature();
        $cacheKey = 'research_thrust:v2:'.md5(json_encode([
            'title' => trim((string) ($payload['title'] ?? '')),
            'keywords' => trim((string) ($payload['keywords'] ?? '')),
            'abstract' => trim((string) ($payload['abstract'] ?? '')),
            'introduction' => trim((string) ($payload['introduction'] ?? '')),
            'use_ai' => $useAi,
            'thrust_signature' => $thrustSignature,
        ]));

        return Cache::remember($cacheKey, now()->addMinutes((int) config('services.ollama.cache_minutes', 60)), function () use ($payload, $content, $useAi) {
            if ($useAi && config('services.ollama.enabled', false) && $this->ollamaAvailable()) {
                $aiSuggestion = $this->suggestWithAi($payload, $content);

                if (($aiSuggestion['thrust'] ?? null) !== null) {
                    return $aiSuggestion;
                }
            }

            return $this->suggestWithKeywords($content);
        });
    }

    private function suggestWithAi(array $payload, string $content): array
    {
        $ollama = new OllamaService();
        $thrusts = $this->candidateThrusts();

        $systemPrompt = 'You classify research topics into CSU RDE thrusts. Return valid JSON only. Choose one to three thrusts from the allowed list and never invent a new one. Use only thrusts directly supported by explicit research content. If evidence is weak for secondary thrusts, return only one thrust.';

        $prompt = json_encode([
            'allowed_thrusts' => $thrusts->map(static function (array $thrust) {
                return [
                    'name' => $thrust['name'],
                    'description' => $thrust['description'],
                ];
            })->values()->all(),
            'research_title' => trim((string) ($payload['title'] ?? '')),
            'research_keywords' => trim((string) ($payload['keywords'] ?? '')),
            'research_abstract' => trim((string) ($payload['abstract'] ?? '')),
            'research_content' => $content,
            'required_output' => [
                'thrust' => 'Primary thrust name from the allowed list.',
                'thrusts' => ['Primary thrust name', 'Optional secondary thrust name'],
                'reason' => 'Short explanation grounded in the provided content.',
            ],
        ], JSON_PRETTY_PRINT);

        $response = $ollama->chat($prompt, $systemPrompt, [
            'temperature' => 0.1,
            'max_tokens' => 220,
            'timeout' => 12,
        ]);

        if (! $response) {
            return [];
        }

        $decoded = $this->decodeJsonResponse($response);
        $thrusts = $this->normalizeThrustList($decoded['thrusts'] ?? $decoded['thrust'] ?? []);
        $thrusts = $this->filterThrustsByEvidence($content, $thrusts);
        $thrust = $this->normalizeThrust((string) ($decoded['thrust'] ?? ($thrusts[0] ?? '')));

        if ($thrust !== null && ! in_array($thrust, $thrusts, true)) {
            array_unshift($thrusts, $thrust);
            $thrusts = $this->filterThrustsByEvidence($content, $thrusts);
        }

        if ($thrust === null && ! empty($thrusts[0])) {
            $thrust = $thrusts[0];
        }

        if ($thrust === null) {
            return [];
        }

        return [
            'thrust' => $thrust,
            'thrusts' => $thrusts ?: [$thrust],
            'reason' => trim((string) ($decoded['reason'] ?? 'AI matched the nearest CSU RDE thrust.')),
            'source' => 'ollama',
            'confidence' => 80,
        ];
    }

    private function suggestWithKeywords(string $content): array
    {
        $best = null;
        $bestScore = 0;
        $bestMatches = [];
        $ranked = [];
        $thrusts = $this->candidateThrusts();

        foreach ($thrusts as $thrust) {
            $score = 0;
            $matches = [];
            $keywords = $this->parseKeywords($thrust['keywords'] ?? '');
            $scoringTerms = array_merge([
                $thrust['name'] ?? '',
                $thrust['description'] ?? '',
            ], $keywords);

            foreach ($scoringTerms as $keyword) {
                $occurrences = $this->countOccurrences($content, $keyword);

                if ($occurrences > 0) {
                    $score += $occurrences;
                    $matches[] = $keyword;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $thrust;
                $bestMatches = $matches;
            }

            if ($score > 0) {
                $ranked[] = [
                    'name' => $thrust['name'],
                    'score' => $score,
                    'matches' => array_values(array_unique($matches)),
                ];
            }
        }

        if ($best === null || $bestScore === 0) {
            return [
                'thrust' => null,
                'thrusts' => [],
                'reason' => 'No CSU thrust stood out yet. Add more topic detail to get a better match.',
                'source' => 'keyword',
                'confidence' => 0,
            ];
        }

        usort($ranked, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $primaryScore = max(1, (int) $bestScore);
        $secondaryThreshold = max(2, (int) ceil($primaryScore * 0.55));

        $thrustNames = collect($ranked)
            ->filter(static fn (array $item) => $item['score'] >= $secondaryThreshold)
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        if ($thrustNames === []) {
            $thrustNames = [$best['name']];
        }

        $thrustNames = array_slice($thrustNames, 0, 3);

        return [
            'thrust' => $best['name'],
            'thrusts' => array_values(array_unique($thrustNames)),
            'reason' => 'Matched keywords: '.implode(', ', array_slice($bestMatches, 0, 4)).'.',
            'source' => 'keyword',
            'confidence' => min(95, 45 + ($bestScore * 8)),
        ];
    }

    private function combineContent(array $payload): string
    {
        $parts = array_filter([
            trim((string) ($payload['title'] ?? '')),
            trim((string) ($payload['keywords'] ?? '')),
            trim((string) ($payload['abstract'] ?? '')),
            trim((string) ($payload['introduction'] ?? '')),
            trim((string) ($payload['methodology'] ?? '')),
            trim((string) ($payload['results'] ?? '')),
            trim((string) ($payload['conclusion'] ?? '')),
            trim((string) ($payload['recommendations'] ?? '')),
        ]);

        return strtolower(implode(' ', $parts));
    }

    private function countOccurrences(string $content, string $keyword): int
    {
        $normalizedKeyword = strtolower(trim($keyword));

        if ($normalizedKeyword === '') {
            return 0;
        }

        return substr_count($content, $normalizedKeyword);
    }

    private function normalizeThrust(string $thrust): ?string
    {
        $normalized = trim($thrust);

        if ($normalized === '') {
            return null;
        }

        foreach ($this->candidateThrusts() as $candidate) {
            if (strcasecmp($candidate['name'], $normalized) === 0) {
                return $candidate['name'];
            }
        }

        return null;
    }

    private function normalizeThrustList($thrusts): array
    {
        if (is_string($thrusts)) {
            $thrusts = [$thrusts];
        }

        if (! is_array($thrusts)) {
            return [];
        }

        $normalized = [];

        foreach ($thrusts as $thrust) {
            $name = $this->normalizeThrust((string) $thrust);

            if ($name && ! in_array($name, $normalized, true)) {
                $normalized[] = $name;
            }
        }

        return array_slice($normalized, 0, 3);
    }

    private function candidateThrusts()
    {
        return self::activeThrustRecords()->map(function ($thrust) {
            if ($thrust instanceof Thrust) {
                return [
                    'name' => $thrust->name,
                    'description' => $thrust->description,
                    'keywords' => $thrust->keywords,
                ];
            }

            return [
                'name' => $thrust['name'] ?? '',
                'description' => $thrust['description'] ?? '',
                'keywords' => $thrust['keywords'] ?? '',
            ];
        });
    }

    private function parseKeywords($keywords): array
    {
        if (is_array($keywords)) {
            return collect($keywords)
                ->map(fn ($keyword) => trim((string) $keyword))
                ->filter()
                ->values()
                ->all();
        }

        return collect(preg_split('/[\r\n,]+/', (string) $keywords))
            ->map(fn (string $keyword) => trim($keyword))
            ->filter()
            ->values()
            ->all();
    }

    private function filterThrustsByEvidence(string $content, array $thrusts): array
    {
        $content = strtolower(trim($content));

        if ($content === '' || $thrusts === []) {
            return [];
        }

        $candidateByName = $this->candidateThrusts()->keyBy('name');
        $filtered = [];

        foreach ($thrusts as $index => $name) {
            $record = $candidateByName->get($name);

            if (! is_array($record)) {
                continue;
            }

            $keywords = $this->parseKeywords($record['keywords'] ?? '');
            $evidenceHits = 0;

            foreach ($keywords as $keyword) {
                if ($this->countOccurrences($content, $keyword) > 0) {
                    $evidenceHits++;
                }
            }

            if ($evidenceHits >= 1 || $index === 0) {
                $filtered[] = $name;
            }
        }

        return array_slice(array_values(array_unique($filtered)), 0, 3);
    }

    private function thrustSignature(): string
    {
        $records = $this->candidateThrusts();

        return md5($records->map(function (array $thrust) {
            return implode('|', [
                $thrust['name'] ?? '',
                $thrust['description'] ?? '',
                $this->keywordsToString($thrust['keywords'] ?? ''),
            ]);
        })->implode('||'));
    }

    private function keywordsToString($keywords): string
    {
        if (is_array($keywords)) {
            return implode(', ', array_map(static fn ($keyword) => trim((string) $keyword), $keywords));
        }

        return trim((string) $keywords);
    }

    private function decodeJsonResponse(string $response): array
    {
        $decoded = json_decode($response, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function ollamaAvailable(): bool
    {
        return Cache::remember('research_thrust:ollama_available', now()->addMinutes(5), function () {
            $ollama = new OllamaService();

            return $ollama->isAvailable();
        });
    }
}