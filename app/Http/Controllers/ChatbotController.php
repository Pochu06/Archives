<?php

namespace App\Http\Controllers;

use App\Services\ArchiveChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotController extends Controller
{
    private const SESSION_KEY = 'chatbot.messages';

    public function index(ArchiveChatbotService $chatbotService): View
    {
        return view('chatbot.index', [
            'messages' => $this->messages(),
            'chatbotAvailable' => $chatbotService->isAvailable(),
        ]);
    }

    public function store(Request $request, ArchiveChatbotService $chatbotService): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $messages = $this->messages();
        $userMessage = trim($validated['message']);
        $response = $chatbotService->respond($messages, $userMessage);

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];
        $messages[] = [
            'role' => 'assistant',
            'content' => $response['reply'],
            'references' => $response['references'],
            'status' => $response['status'],
        ];

        session([self::SESSION_KEY => array_slice($messages, -12)]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'user' => [
                    'role' => 'user',
                    'content' => $userMessage,
                ],
                'assistant' => end($messages),
                'chatbot_available' => $chatbotService->isAvailable(),
            ]);
        }

        return redirect()->route('chatbot.index');
    }

    public function reset(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
            ]);
        }

        return redirect()->route('chatbot.index');
    }

    private function messages(): array
    {
        $messages = session(self::SESSION_KEY, []);

        if (! is_array($messages)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($message) {
            if (! is_array($message)) {
                return null;
            }

            $role = trim((string) ($message['role'] ?? ''));
            $content = trim((string) ($message['content'] ?? ''));

            if ($role === 'assistant') {
                $content = preg_replace('/\*\*(.*?)\*\*/s', '$1', $content) ?? $content;
                $content = preg_replace('/__(.*?)__/s', '$1', $content) ?? $content;
                $content = preg_replace('/(?<!\*)\*(?!\s)(.*?)(?<!\s)\*(?!\*)/s', '$1', $content) ?? $content;
                $content = preg_replace('/(?<!_)_(?!\s)(.*?)(?<!\s)_(?!_)/s', '$1', $content) ?? $content;
                $content = preg_replace('/`([^`]+)`/', '$1', $content) ?? $content;
                $content = trim($content);
            }

            if (! in_array($role, ['user', 'assistant'], true) || $content === '') {
                return null;
            }

            return [
                'role' => $role,
                'content' => $content,
                'references' => is_array($message['references'] ?? null) ? array_values($message['references']) : [],
                'status' => trim((string) ($message['status'] ?? 'ok')),
            ];
        }, $messages)));
    }
}