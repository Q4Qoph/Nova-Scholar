<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use LogicException;

class GroqChatService
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function complete(array $messages): string
    {
        $apiKey = config('ai.groq.api_key');

        if ($apiKey === null || $apiKey === '') {
            throw new LogicException('Groq is not configured. Add GROQ_API_KEY to the local environment.');
        }

        $response = Http::baseUrl(config('ai.groq.base_url'))
            ->acceptJson()
            ->withToken($apiKey)
            ->connectTimeout(5)
            ->timeout(config('ai.groq.timeout_seconds'))
            ->post('/chat/completions', [
                'model' => config('ai.groq.model'),
                'messages' => $messages,
                'temperature' => 0.4,
            ])
            ->throw();

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new LogicException('Groq returned no assistant content.');
        }

        return $content;
    }
}
