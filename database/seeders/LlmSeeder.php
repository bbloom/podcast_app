<?php

namespace Database\Seeders;


use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;
use MediaPlatform\Configuration\Providers\Models\Provider;
use MediaPlatform\Configuration\UseCases\Models\UseCase;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LlmSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── Providers ──────────────────────────────────────────────────────────
        $providers = [
            ['name' => 'Anthropic',      'slug' => 'anthropic',      'website_url' => 'https://anthropic.com'],
            ['name' => 'OpenAI',         'slug' => 'openai',         'website_url' => 'https://openai.com'],
            ['name' => 'Google',         'slug' => 'google',         'website_url' => 'https://ai.google.dev'],
        ];

        foreach ($providers as $provider) {
            Provider::firstOrCreate(['slug' => $provider['slug']], array_merge($provider, ['enabled' => true]));
        }


        // ── Use Cases ──────────────────────────────────────────────────────────
        $useCases = [
            ['name' => 'Chat / Completion', 'slug' => 'chat',              'description' => 'General-purpose text generation and conversation.'],
            ['name' => 'Embedding',         'slug' => 'embedding',         'description' => 'Convert text into vector representations.'],
            ['name' => 'Vision',            'slug' => 'vision',            'description' => 'Understand and reason about images.'],
            ['name' => 'Function Calling',  'slug' => 'function-calling',  'description' => 'Invoke tools and structured outputs.'],
            ['name' => 'Audio',             'slug' => 'audio',             'description' => 'Speech-to-text or text-to-speech tasks.'],
            ['name' => 'Ad Hoc Prompt',     'slug' => 'ad-hoc-prompt',     'description' => 'Manual one-off prompts via the UI.'],
            ['name' => 'Digest Processing', 'slug' => 'digest-processing', 'description' => 'Summarise and filter content from YouTube transcripts, podcast episodes, and RSS articles. Used for AI summarisation during content processing. Only one enabled model may be assigned at a time — assigning a new model will automatically replace the current one.'],
        ];
        
        foreach ($useCases as $useCase) {
            UseCase::firstOrCreate(['slug' => $useCase['slug']], $useCase);
        }


        // ── Resolve use case IDs for pivot attachment ──────────────────────────
        $chat        = UseCase::where('slug', 'chat')->first();
        $embed       = UseCase::where('slug', 'embedding')->first();
        $vision      = UseCase::where('slug', 'vision')->first();
        $fnCall      = UseCase::where('slug', 'function-calling')->first();
        $audio       = UseCase::where('slug', 'audio')->first();
        $adHoc       = UseCase::where('slug', 'ad-hoc-prompt')->first();
        $contentSum  = UseCase::where('slug', 'digest-processing')->first();
        
        $chatVisionFn = [$chat->id, $vision->id, $fnCall->id];
        $chatFn       = [$chat->id, $fnCall->id];


        // ── Language Models ────────────────────────────────────────────────────

        $anthropic = Provider::where('slug', 'anthropic')->first();
        $openai    = Provider::where('slug', 'openai')->first();
        $google    = Provider::where('slug', 'google')->first();

        // -- Anthropic ---------------------------------------------------------
        // https://platform.claude.com/docs/en/about-claude/models/overview
        $anthropicModels = [
            [
                'name'        => 'Claude Opus 4.6',
                'slug'        => 'claude-opus-4-6',
                'description' => 'Most intelligent model for building agents and coding.',
                //'use_cases'   => $chatVisionFn,
                'use_cases'   => [],
            ],
            [
                'name'        => 'Claude Sonnet 4.6',
                'slug'        => 'claude-sonnet-4-6',
                'description' => 'Best combination of speed and intelligence.',
                //'use_cases'   => $chatVisionFn,
                'use_cases'   => [],
            ],
            [
                'name'        => 'Claude Haiku 4.5',
                'slug'        => 'claude-haiku-4-5',
                'description' => 'Fastest model with near-frontier intelligence.',
                //'use_cases'   => $chatVisionFn,
                'use_cases'   => [],
            ],
        ];

        // -- OpenAI ------------------------------------------------------------
        // https://platform.openai.com/docs/models
        $openaiModels = [
            [
                'name'        => 'GPT-4.1',
                'slug'        => 'gpt-4.1',
                'description' => 'Smartest non-reasoning model. Strong at coding and instruction following.',
                //'use_cases'   => $chatVisionFn,
                'use_cases'   => [],
            ],
            [
                'name'        => 'GPT-4.1 mini',
                'slug'        => 'gpt-4.1-mini',
                'description' => 'Smaller, faster version of GPT-4.1.',
                //'use_cases'   => $chatVisionFn,
                'use_cases'   => [],
            ],
            [
                'name'        => 'GPT-4.1 nano',
                'slug'        => 'gpt-4.1-nano',
                'description' => 'Fastest, most cost-efficient version of GPT-4.1.',
                //'use_cases'   => $chatFn,
                'use_cases'   => [],
            ],
            [
                'name'        => 'o3',
                'slug'        => 'o3',
                'description' => 'Most powerful reasoning model. Best for complex coding, math, and science.',
                //'use_cases'   => $chatVisionFn,
                'use_cases'   => [],
            ],
            [
                'name'        => 'o4-mini',
                'slug'        => 'o4-mini',
                'description' => 'Fast, cost-efficient reasoning model.',
                //'use_cases'   => $chatVisionFn,
                'use_cases'   => [],
            ],
            [
                'name'        => 'text-embedding-3-large',
                'slug'        => 'text-embedding-3-large',
                'description' => 'Most capable embedding model.',
                //'use_cases'   => [$embed->id],
                'use_cases'   => [],
            ],
            [
                'name'        => 'text-embedding-3-small',
                'slug'        => 'text-embedding-3-small',
                'description' => 'Efficient, smaller embedding model.',
                //'use_cases'   => [$embed->id],
                'use_cases'   => [],
            ],
            [
                'name'        => 'whisper-1',
                'slug'        => 'whisper-1',
                'description' => 'Speech-to-text transcription and translation.',
                //'use_cases'   => [$audio->id],
                'use_cases'   => [],
            ],
        ];

        // -- Google ------------------------------------------------------------
        // https://ai.google.dev/gemini-api/docs/models
        $googleModels = [
            [
                'name'        => 'Gemini 2.5 Pro',
                'slug'        => 'gemini-2.5-pro',
                'description' => 'Most powerful Gemini model. Excels at coding and complex reasoning. Pro burns quota fast.',
                //'use_cases'   => $chatVisionFn,
                'use_cases'   => [],
                'enabled'     => false,
            ],
            [
                'name'        => 'Gemini 2.5 Flash',
                'slug'        => 'gemini-2.5-flash',
                'description' => 'Best price-performance model for high-volume tasks with thinking support. For basic prompts, 2.5 Flash is the sweet spot — better than Flash-Lite, and much more generous rate limits than Pro.',
                //'use_cases' => [...$chatVisionFn, $adHoc->id],
                'use_cases' => [$adHoc->id],
            ],
            [
                'name'        => 'Gemini 2.5 Flash-Lite',
                'slug'        => 'gemini-2.5-flash-lite',
                'description' => 'Most cost-efficient multimodal model for high-frequency lightweight tasks.',
                //'use_cases' => [...$chatVisionFn, $adHoc->id],
                'use_cases' => [$adHoc->id],
            ],
            [
                'name'        => 'Gemini 20 Flash',
                'slug'        => 'gemini-2.0-flash',
                'description' => 'Balanced multimodal model with 1M token context window. Note that Gemini 2.0 Flash was deprecated in February 2026 and is retiring March 3, 2026.',
                //'use_cases'   => $chatVisionFn,
                'use_cases'   => [],
                'enabled'     => false,
            ],
            [
                'name'        => 'Gemini Embedding 001',
                'slug'        => 'gemini-embedding-001',
                'description' => "table text embedding model. Available to developers on both the free and paid tiers. Google Developers That's your embedding model for RAG.",
                //'use_cases'   => [$embed->id],
                'use_cases'   => [],
            ],
            [
                'name'        => 'Gemini 2.5 Flash',
                'slug'        => 'gemini-2.5-flash',
                'description' => 'Best price-performance model for high-volume tasks with thinking support. For basic prompts, 2.5 Flash is the sweet spot — better than Flash-Lite, and much more generous rate limits than Pro.',
                //'use_cases'   => [...$chatVisionFn, $adHoc->id, $contentSum->id],
                'use_cases'   => [$adHoc->id, $contentSum->id],
            ],
        ];

        // ── Seed models ────────────────────────────────────────────────────────
        $groups = [
            $anthropic->id => $anthropicModels,
            $openai->id    => $openaiModels,
            $google->id    => $googleModels,
        ];

        foreach ($groups as $providerId => $models) {
            foreach ($models as $data) {

                if (isset($data['use_cases'])) {
                    $useCaseIds = $data['use_cases'];
                    unset($data['use_cases']);

                    $model = LanguageModel::firstOrCreate(
                        ['slug' => $data['slug']],
                        array_merge($data, [
                            'provider_id' => $providerId,
                            'enabled'     => $data['enabled'] ?? true,
                        ])
                    );

                    // Sync use cases without detaching existing ones
                    $model->useCases()->syncWithoutDetaching($useCaseIds);
                }

            }
        }
    }
}