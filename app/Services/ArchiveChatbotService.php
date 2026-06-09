<?php

namespace App\Services;

use App\Models\Research;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ArchiveChatbotService
{
    private const MAX_CONTEXT_ITEMS = 4;

    private const MAX_HISTORY_MESSAGES = 8;

    private const OUT_OF_SCOPE_REPLY = 'I can only help with ARCHIVES system use, archived research, research discovery, and basic research-writing questions. Please ask something related to the archive or your research work.';

    private OllamaService $ollama;

    private array $stopWords = [
        'about', 'after', 'archive', 'archived', 'assistant', 'between', 'could', 'does', 'find', 'from', 'have',
        'into', 'need', 'paper', 'papers', 'please', 'question', 'research', 'show', 'tell', 'that',
        'their', 'them', 'there', 'these', 'they', 'this', 'thesis', 'what', 'which', 'with', 'would',
        'your',
    ];

    private array $archiveIntentKeywords = [
        'abstract', 'adviser', 'adviser', 'ai assistant', 'approval', 'approved', 'archive', 'archived', 'author',
        'capstone', 'category', 'chatbot', 'citation', 'college', 'dashboard', 'download', 'filter', 'full text',
        'introduction', 'keyword', 'literature', 'method', 'methodology', 'paper', 'preview', 'proposal', 'recommendation',
        'references', 'related research', 'research', 'results', 'revision', 'rde', 'study', 'submission', 'system',
        'thesis', 'topic', 'upload',
    ];

    public function __construct(?OllamaService $ollama = null)
    {
        $this->ollama = $ollama ?? new OllamaService();
    }

    public function respond(array $history, string $message): array
    {
        $message = trim($message);
        $references = $this->findRelevantResearch($message);

        if (! $this->isArchiveScopedQuestion($message, $references)) {
            return [
                'reply' => self::OUT_OF_SCOPE_REPLY,
                'references' => [],
                'status' => 'out_of_scope',
            ];
        }

        if (! $this->isAvailable()) {
            return [
                'reply' => 'The AI assistant is currently unavailable because the Ollama service is offline. Start Ollama and try again.',
                'references' => $this->formatReferences($references),
                'status' => 'unavailable',
            ];
        }

        $reply = $this->ollama->chatMessages(array_merge(
            [
                ['role' => 'system', 'content' => $this->buildSystemPrompt($references)],
            ],
            $this->normalizeHistory($history),
            [
                ['role' => 'user', 'content' => $message],
            ]
        ), [
            'temperature' => 0.25,
            'max_tokens' => 320,
            'timeout' => max((int) config('services.ollama.timeout', 120), 60),
            'log_context' => 'archive chatbot response',
        ]);

        if (! $reply) {
            $failureType = $this->ollama->getLastFailureType();

            return [
                'reply' => match ($failureType) {
                    'timeout' => 'Ollama took too long to respond. Try a shorter question, or wait a moment and send it again.',
                    'connection' => 'The AI assistant could not reach the Ollama service. Make sure Ollama is still running, then try again.',
                    'http' => 'Ollama returned an error while generating the response. Please try again in a moment.',
                    default => 'I could not generate a response right now. Please try again in a moment.',
                },
                'references' => $this->formatReferences($references),
                'status' => 'error',
            ];
        }

        return [
            'reply' => $this->sanitizeReply($reply),
            'references' => $this->formatReferences($references),
            'status' => 'ok',
        ];
    }

    public function isAvailable(): bool
    {
        return config('services.ollama.enabled', false)
            && $this->ollama->isAvailable();
    }

    private function buildSystemPrompt(Collection $references): string
    {
        $context = $references->isEmpty()
            ? 'Relevant approved archive records: none found for this question.'
            : "Relevant approved archive records:\n".$references->map(function (Research $research) {
                $abstract = preg_replace('/\s+/', ' ', strip_tags((string) $research->abstract)) ?? '';

                return '- '.$research->title
                    .' (' . ($research->publication_year ?: 'N/A') . ')'
                    .' | Category: '.($research->category?->name ?? 'Uncategorized')
                    .' | College: '.($research->college?->code ?? 'N/A')
                    .' | Keywords: '.trim((string) $research->keywords)
                    .' | Abstract excerpt: '.Str::limit($abstract, 220);
            })->implode("\n");

        return implode("\n\n", [
            'You are the ARCHIVES AI assistant for a university research archive.',
            'Help users find approved archived research, explain archive results in plain language, suggest directions for research topics, and answer basic research-writing questions.',
            'Use the supplied archive context when it is relevant to the question.',
            'When relevant archive records are provided, mention the exact paper titles first before giving any extra guidance.',
            'Return plain text only. Do not use markdown, asterisks for bold, italic markers, code formatting, or heading markers.',
            'Do not claim that no archived research exists if records are provided in the context.',
            'Do not invent archived papers, authors, years, categories, or findings that are not present in the provided context.',
            'If the context is insufficient, say that clearly and then give general guidance.',
            'Refuse any request that is not about the ARCHIVES system, archived research, research discovery, or basic research-writing support.',
            'Keep answers concise, practical, and student-friendly.',
            $context,
        ]);
    }

    private function isArchiveScopedQuestion(string $message, Collection $references): bool
    {
        if ($references->isNotEmpty()) {
            return true;
        }

        $normalized = Str::lower(trim($message));

        if ($normalized === '') {
            return false;
        }

        foreach ($this->archiveIntentKeywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        if (preg_match('/\b(how|where|can|could|why|when|which|find|search|submit|upload|download|preview|request|revise|approve|use|open|filter)\b/', $normalized) === 1
            && preg_match('/\b(archive|system|research|paper|thesis|topic|download|approval|rde|submission|pdf|abstract|methodology|reference|college|category|keyword)\b/', $normalized) === 1) {
            return true;
        }

        return false;
    }

    private function normalizeHistory(array $history): array
    {
        $messages = array_values(array_filter(array_map(function ($message) {
            if (! is_array($message)) {
                return null;
            }

            $role = trim((string) ($message['role'] ?? ''));
            $content = trim((string) ($message['content'] ?? ''));

            if (! in_array($role, ['user', 'assistant'], true) || $content === '') {
                return null;
            }

            return [
                'role' => $role,
                'content' => $content,
            ];
        }, $history)));

        return array_slice($messages, -self::MAX_HISTORY_MESSAGES);
    }

    private function sanitizeReply(string $reply): string
    {
        $reply = trim($reply);
        $reply = preg_replace('/\*\*(.*?)\*\*/s', '$1', $reply) ?? $reply;
        $reply = preg_replace('/__(.*?)__/s', '$1', $reply) ?? $reply;
        $reply = preg_replace('/(?<!\*)\*(?!\s)(.*?)(?<!\s)\*(?!\*)/s', '$1', $reply) ?? $reply;
        $reply = preg_replace('/(?<!_)_(?!\s)(.*?)(?<!\s)_(?!_)/s', '$1', $reply) ?? $reply;
        $reply = preg_replace('/`([^`]+)`/', '$1', $reply) ?? $reply;
        $reply = preg_replace('/\r\n?/', "\n", $reply) ?? $reply;

        return trim($reply);
    }

    private function findRelevantResearch(string $message): Collection
    {
        $terms = $this->extractSearchTerms($message);

        if ($terms === []) {
            return collect();
        }

        return Research::with(['college', 'category'])
            ->approved()
            ->where(function ($builder) use ($terms) {
                foreach (array_slice($terms, 0, 8) as $term) {
                    $builder->orWhere('title', 'like', '%'.$term.'%')
                        ->orWhere('keywords', 'like', '%'.$term.'%')
                        ->orWhere('abstract', 'like', '%'.$term.'%')
                        ->orWhereHas('college', function ($collegeQuery) use ($term) {
                            $collegeQuery->where('name', 'like', '%'.$term.'%')
                                ->orWhere('code', 'like', '%'.$term.'%')
                                ->orWhere('description', 'like', '%'.$term.'%');
                        })
                        ->orWhereHas('category', function ($categoryQuery) use ($term) {
                            $categoryQuery->where('name', 'like', '%'.$term.'%')
                                ->orWhere('description', 'like', '%'.$term.'%');
                        });
                }
            })
            ->limit(20)
            ->get()
            ->map(function (Research $research) use ($terms) {
                $score = 0;
                $title = Str::lower((string) $research->title);
                $keywords = Str::lower((string) $research->keywords);
                $abstract = Str::lower((string) $research->abstract);
                $metadata = Str::lower(implode(' ', [
                    (string) $research->college?->name,
                    (string) $research->college?->code,
                    (string) $research->college?->description,
                    (string) $research->category?->name,
                    (string) $research->category?->description,
                ]));

                foreach ($terms as $term) {
                    $score += $this->scoreMatch($title, $term, 5);
                    $score += $this->scoreMatch($keywords, $term, 4);
                    $score += $this->scoreMatch($abstract, $term, 2);
                    $score += $this->scoreMatch($metadata, $term, 1);
                }

                $research->chatbot_match_score = $score;

                return $research;
            })
            ->filter(fn (Research $research) => $research->chatbot_match_score > 0)
            ->sortByDesc('chatbot_match_score')
            ->take(self::MAX_CONTEXT_ITEMS)
            ->values();
    }

    private function extractSearchTerms(string $message): array
    {
        $normalized = preg_replace('/[^a-z0-9\s-]/i', ' ', Str::lower($message));

        if (! is_string($normalized) || trim($normalized) === '') {
            return [];
        }

        $terms = collect(preg_split('/\s+/', trim($normalized)) ?: [])
            ->map(fn (string $term) => $this->normalizePhrase($term))
            ->filter()
            ->reject(fn (string $term) => in_array($term, $this->stopWords, true))
            ->unique()
            ->values()
            ->all();

        return collect($terms)
            ->flatMap(fn (string $term) => array_merge([$term], $this->generateWordVariants($term)))
            ->unique()
            ->values()
            ->all();
    }

    private function scoreMatch(string $haystack, string $term, int $weight): int
    {
        if ($haystack === '' || $term === '' || ! str_contains($haystack, $term)) {
            return 0;
        }

        return $weight + (substr_count($haystack, $term) * $weight);
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

        return array_values(array_unique(array_filter(array_map(function (string $variant) {
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

    private function formatReferences(Collection $references): array
    {
        return $references->map(function (Research $research) {
            $abstract = preg_replace('/\s+/', ' ', strip_tags((string) $research->abstract)) ?? '';

            return [
                'id' => $research->id,
                'title' => $research->title,
                'publication_year' => $research->publication_year,
                'category' => $research->category?->name,
                'college_code' => $research->college?->code,
                'abstract_excerpt' => Str::limit($abstract, 160),
            ];
        })->values()->all();
    }
}