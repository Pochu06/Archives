<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl;

    private string $model;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.base_url', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'qwen2.5:7b');
        $this->timeout = (int) config('services.ollama.timeout', 120);
    }

    public function chat(string $prompt, ?string $systemPrompt = null, array $options = []): ?string
    {
        try {
            $messages = [];

            if ($systemPrompt) {
                $messages[] = ['role' => 'system', 'content' => $systemPrompt];
            }

            $messages[] = ['role' => 'user', 'content' => $prompt];

            $response = Http::timeout((int) ($options['timeout'] ?? $this->timeout))
                ->post("{$this->baseUrl}/api/chat", [
                    'model' => $options['model'] ?? $this->model,
                    'messages' => $messages,
                    'stream' => false,
                    'options' => [
                        'temperature' => $options['temperature'] ?? 0.2,
                        'num_predict' => $options['max_tokens'] ?? 160,
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('message.content');
            }

            Log::warning('Ollama API error while generating research summary', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Ollama connection failed while generating research summary', [
                'message' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    public function isAvailable(): bool
    {
        try {
            return Http::timeout(5)->get("{$this->baseUrl}/api/tags")->successful();
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
