<?php 

namespace MediaPlatform\AA_Playtime;

use Gemini\Laravel\Facades\Gemini;

class AskGemini
{
    public function ask() 
    {

        // Simulate: a transcript that doesn't mention "AI" in title or description,
        // but the content is actually about machine learning
        $searchTerms = "death";

        $transcript = <<<TEXT
BOULDER, Colo. -- Colorado players told stories Monday, like how Dominiq Ponder once jumped a 10-foot fence to let a rehabbing teammate into the hot tub. They chatted about his work ethic, too, and how he was in the quarterback room before sunrise.

It was a chance to reflect on their teammate who died early Sunday morning in a single-car crash. He was 23.

The players were given the option by coach Deion Sanders to skip the first day of spring practice Monday. But in an emergency meeting the night before -- to grieve together and comfort one another -- everyone agreed that taking the field was the best option.

Because that's what Ponder would've wanted. He was on their minds at practice as they broke the huddle with the chant of "Dom."

"Almost like a boost of energy, like he was there with us," running back DeKalon Taylor said. "That's what it felt like."

Players found out about Ponder's death throughout the day. Some found out the news Sunday after church. Some later in the day. Offensive coordinator Brennan Marion said he received a phone call from Ponder's father while Marion was playing with his own son.
TEXT;

        $prompt = "You are a content relevance filter. A user is searching for content about: {$searchTerms}\n\n" .
            "Below is a transcript of a YouTube video. If this content is relevant to the search terms, " .
            "provide a 2-3 sentence overview followed by bullet points of the key takeaways in HTML format. " .
            "If the content is NOT relevant to the search terms, respond with exactly: NOT_RELEVANT\n\n" .
            "Transcript:\n" . $transcript;

        $response = Gemini::generativeModel(model: 'gemini-2.5-flash')
            ->generateContent($prompt);

        $result = $response->text();

        if (trim($result) === 'NOT_RELEVANT') {
            echo "Skipped — not relevant.\n";
        } else {
            echo "RELEVANT — Summary:\n";
            echo $result;
        }
    }
}