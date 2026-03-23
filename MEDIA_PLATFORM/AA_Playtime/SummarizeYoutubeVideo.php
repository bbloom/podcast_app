<?php 

namespace MediaPlatform\AA_Playtime;

use Illuminate\Support\Facades\Process;
use Gemini\Laravel\Facades\Gemini;

class SummarizeYoutubeVideo {
   
    public function summarize()
    {
        // Summarize YouTube Video

        // 1. Extract Video ID from URL
        // $videoId = 'dQw4w9WgXcQ';
        $YouTube_url = 'https://www.youtube.com/watch?v=AZUp5nY7BWU';
        //$YouTube_url = 'https://www.youtube.com/watch?v=UcFmAKxgA0E'; // Eden's interview
        $videoId = $this->getVideoId($YouTube_url);


        // 2. Call Python script via Laravel Process
        // base_path() ensures we find the /scripts folder regardless of where the app is running
        $scriptPath = base_path('scripts/get_transcript.py');
        $result = Process::run("/usr/bin/python3 {$scriptPath} {$videoId}");

        if ($result->failed()) {
            echo "<pre>" . $result->errorOutput() . "</pre>"; // This reveals the real error
        }

        // Laravel's Process::run() returns a ProcessResult object, not an array directly. You 
        // need to call ->output() on it to get the JSON string first:
        $data = json_decode($result->output(), true);

        $title       = $data['title'];
        $channel     = $data['channel'];
        $publishedAt = \Carbon\Carbon::createFromFormat('Ymd', $data['published_at'])->toDateString();
        $transcript  = $data['transcript'];

        /*
        echo "<hr>";
        echo "title = ". $title;
        echo "<br>channel = " . $channel; 
        echo "<br>published at = " . $publishedAt;
        echo "<hr>transcript:<br>" . $transcript;
        */


        if ($result->failed()) {
            echo "<pre>" . $result->errorOutput() . "</pre>"; // This reveals the real error
            // return;
        }

        $transcriptText = $result->output();

        // Check if Python returned an "ERROR:" string from your try/catch
        if (str_contains($transcriptText, 'ERROR:')) {
            return "Python Error: " . $transcriptText;
        }

        /*
        echo "<hr>";
        echo "<h1>this is the transcript:</h1>";
        echo $transcriptText;
        echo "<hr>";
        return;
        */

        // 3. Prompt Gemini for the summary
        /*
        Output format — Tell it exactly how you want the response structured (e.g. a brief intro paragraph followed by bullet points, or pure prose, or sections with headers).

        Length guidance — "Concise" is vague. Say something like "in no more than 200 words" or "in 3-5 bullet points."
        
        Audience/tone — Should it read like a professional summary or a casual overview?
        
        What to exclude — Transcripts often have filler, ads, and repetition. Tell it to ignore those.
        
        Plain text vs. Markdown (and HTML) — If you're rendering in a browser, Markdown is great. If you're storing raw text, ask for plain text.

        */
        // $prompt = "Below is a transcript of a YouTube video. Please provide a concise summary of the main points:\n\n" . 
        $prompt = "Below is a transcript of a YouTube video. Summarize the key points for a general audience. " .
          "Format your response as a brief 2-3 sentence overview followed by a bullet point list of the main takeaways. " .
          "Ignore any sponsor messages, ads, or repetitive filler. Use HTML formatting.\n\n" .
          "Transcript:\n" . $transcriptText;
        

        $response = Gemini::generativeModel(model: 'gemini-2.5-flash')
            ->generateContent($prompt);

        echo "<hr>title = ". $title;
        echo "<br>channel = " . $channel; 
        echo "<br>published at = " . $publishedAt;            
        echo "<br>" . $response->text();

        return;
    }


    public function getVideoId(string $url): ?string
    {
        // Matches standard, shortened, and shorts URLs
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([^"&?\/\s]{11})/i';
        
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}