<?php
declare(strict_types=1);

namespace System\Utilities;

use System\Config;
use RuntimeException;

class YantraAIClient
{
    protected string $apiKey;
    protected string $model;
    protected string $endpoint = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $ai = Config::read('App.ai') ?? [];

        $this->apiKey = $ai['api_key'] ?? '';
        $this->model  = $ai['model'] ?? 'gpt-4.1-mini';

        if ($this->apiKey === '') {
            throw new RuntimeException('Yantra AI API key is missing (YANTRA_AI_KEY).');
        }
    }

    /**
     * Chat with Yantra AI
     *
     * @param array<int,array{role:string,content:string}> $messages
     */
    public function chat(array $messages): string
    {
        $payload = [
            'model'    => $this->model,
            'messages' => array_merge(
                [
                    [
                        'role'    => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                ],
                $messages
            ),
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            throw new RuntimeException('Yantra AI request failed: ' . curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($raw, true);

        if ($status >= 400) {
            $msg = $data['error']['message'] ?? 'Unknown error';
            throw new RuntimeException("Yantra AI HTTP error {$status}: {$msg}");
        }

        return $data['choices'][0]['message']['content'] ?? '';
    }

    protected function systemPrompt(): string
    {
        return <<<PROMPT
You are **Yantra AI**, an assistant that helps write and refactor code for the Yantra PHP framework.

Key points:
- Yantra uses PSR-4, namespaces like System\\*, Controllers\\admin, Models\\*.
- Controllers validate input, call Models/Services, and return System\\Response.
- Themes live under /Themes and use WebPage + Theme classes for rendering.
- Follow strict, modern PHP (types, null checks, exceptions).
- Prefer small, self-contained helpers and utilities under System\\Utilities.
- When user pastes Yantra code, keep the same style (DocBlocks, strict_types, etc).

When answering:
- Show **working code** snippets.
- Explain briefly, then show code.
- If something is ambiguous, make a reasonable assumption and mention it.
PROMPT;
    }
}