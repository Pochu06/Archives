<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl;

    private string $model;

    private int $timeout;

    private ?string $lastFailureType = null;

    private ?string $lastFailureMessage = null;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.base_url', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'qwen2.5:7b');
        $this->timeout = (int) config('services.ollama.timeout', 120);
    }

    public function chat(string $prompt, ?string $systemPrompt = null, array $options = []): ?string
    {
        $messages = [];

        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $this->chatMessages($messages, $options);
    }

    public function chatMessages(array $messages, array $options = []): ?string
    {
        $this->lastFailureType = null;
        $this->lastFailureMessage = null;

        $messages = array_values(array_filter(array_map(function ($message) {
            if (! is_array($message)) {
                return null;
            }

            $role = trim((string) ($message['role'] ?? ''));
            $content = trim((string) ($message['content'] ?? ''));

            if ($role === '' || $content === '') {
                return null;
            }

            return [
                'role' => $role,
                'content' => $content,
            ];
        }, $messages)));

        if ($messages === []) {
            $this->lastFailureType = 'validation';
            $this->lastFailureMessage = 'No chat messages were provided.';

            return null;
        }

        $logContext = trim((string) ($options['log_context'] ?? 'chat request'));

        try {
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

            $this->lastFailureType = 'http';
            $this->lastFailureMessage = 'Ollama returned HTTP '.$response->status().'.';

            Log::warning('Ollama API error during '.$logContext, [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();

            $this->lastFailureType = str_contains($message, 'cURL error 28') ? 'timeout' : 'connection';
            $this->lastFailureMessage = $message;

            Log::error('Ollama connection failed during '.$logContext, [
                'message' => $message,
            ]);
        }

        return null;
    }

    public function getLastFailureType(): ?string
    {
        return $this->lastFailureType;
    }

    public function getLastFailureMessage(): ?string
    {
        return $this->lastFailureMessage;
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
