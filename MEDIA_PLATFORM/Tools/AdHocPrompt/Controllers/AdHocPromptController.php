<?php

namespace MediaPlatform\Tools\AdHocPrompt\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;
use MediaPlatform\Configuration\UseCases\Models\UseCase;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdHocPromptController extends Controller
{
    public function index(): View
    {
        $models = $this->adHocModels();

        return view('media_platform.tools.ad_hoc_prompt.adhocprompt', compact('models'));
    }

    public function prompt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:4000',
            'model'  => 'required|string|exists:language_models,slug',
        ]);

        $result = Gemini::generativeModel(model: $validated['model'])
            ->generateContent($validated['prompt']);

        return response()->json([
            'answer' => $result->text(),
        ]);
    }

    private function adHocModels()
    {
        $useCase = UseCase::where('slug', 'ad-hoc-prompt')->first();

        if (! $useCase) {
            return LanguageModel::where('enabled', true)
                ->orderBy('name')
                ->get();
        }

        return $useCase->languageModels()
            ->where('enabled', true)
            ->orderBy('name')
            ->get();
    }
}