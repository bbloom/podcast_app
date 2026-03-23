<?php 

namespace MediaPlatform\AA_Playtime;

use Gemini\Laravel\Facades\Gemini;

class PromptGemini {

    public function prompt()
    {
        $prompt = 'What is the tallest building in the world?';

        // Use 'gemini-1.5-flash' for the fastest "Hello World" response
        $result = Gemini::generativeModel(model: 'gemini-2.5-flash')
            ->generateContent($prompt);

        echo "<h1>Gemini says...</h1>";
        echo $result->text();
    }
}