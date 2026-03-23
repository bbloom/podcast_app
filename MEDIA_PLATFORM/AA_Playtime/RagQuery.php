<?php 

namespace MediaPlatform\AA_Playtime;

use Illuminate\Http\JsonResponse;

use MediaPlatform\AA_Playtime\RagService;

class RagQuery {


    public function __construct(private RagService $rag) {}




    public function query(): JsonResponse
    {
        echo "<h1>You are in query()</h1>";
        
        // Hardcoded query
        $queryResult = $this->rag->query('Disrupt 2026');

        echo "result of query:";
        echo "<br><pre>";
        print_r($queryResult);
        echo "</pre>";

        die("<hr>die!");



        return response()->json([
            'query'  => $queryResult,
        ]);
    }
}