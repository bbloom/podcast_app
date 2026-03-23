<?php 

namespace MediaPlatform\AA_Playtime;

use Illuminate\Http\JsonResponse;

use MediaPlatform\AA_Playtime\RagService;

class RagIngest 
{


    public function __construct(private RagService $rag) {}


    public function ingest()
    {
        echo "<h1>You are in INGEST()</h1>";
        

        $feedUrl = 'https://techcrunch.com/category/biotech-health/feed/';
        echo "Hello!";
        $result = $this->rag->ingest($feedUrl);
        echo "Hello after ingest!!";

        echo "result of ingest:";
        echo "feed url = " . $feedUrl;
        echo "<br><pre>";
        print_r($result);
        echo "</pre>";
        
        return;
    }


    public function ingest1(): JsonResponse
    {
        echo "<h1>You are in ingest()</h1>";
        die();

        // Hardcoded ingest
        $ingestResult = $this->rag->ingest('https://your-feed-url.com/rss');

        return response()->json([
            'ingest' => $ingestResult,
        ]);
    }  
}