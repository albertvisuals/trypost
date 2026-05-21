<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Models\Workspace;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Temperature(0.25)]
class PostImageRegenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        public Workspace $workspace,
    ) {}

    public function instructions(): string
    {
        $language = $this->workspace->content_language ?: 'en';

        return <<<PROMPT
You are editing text that will be printed inside a social media image.

Your job:
- Apply the user's instruction to the current title/body/keywords.
- Keep the same language as the input unless instruction explicitly asks to change it.
- Fix spelling/grammar when needed.
- Keep output concise and suitable for image overlays.
- Preserve intent and topic; only change what's needed.

Return JSON only following the schema.
Language preference: {$language}
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('Updated short title for the image (max ~120 chars).')
                ->required(),
            'body' => $schema->string()
                ->description('Updated supporting text for the image (max ~240 chars).')
                ->required(),
            'keywords' => $schema->array()
                ->items($schema->string())
                ->description('3-10 short keywords for image generation context.')
                ->required(),
        ];
    }

    public function provider(): Lab
    {
        return match (config('ai.default')) {
            'openai' => Lab::OpenAI,
            'anthropic' => Lab::Anthropic,
            default => Lab::Gemini,
        };
    }

    public function model(): string
    {
        return config('ai.default_text_model');
    }
}
