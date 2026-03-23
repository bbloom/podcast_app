<?php

namespace MediaPlatform\AA_Playtime;

use MediaPlatform\AA_Playtime\RagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// class RagController extends Controller
class RagController 
{
    public function __construct(private RagService $rag) {}

    /**
     * Fetches the RSS feed, generates embeddings, and stores chunks in Postgres.
     * GET /rag/ingest
     */
    public function ingest(): JsonResponse
    {
        $feedUrl = 'https://your-feed-url-here.com/rss';

        $result = $this->rag->ingest($feedUrl);

        return response()->json($result);
    }

    /**
     * Embeds the query, retrieves relevant chunks, and generates an answer.
     * GET /rag/query?q=your+question+here
     */
    public function query(Request $request): JsonResponse
    {
        $question = $request->string('q')->trim()->toString();

        if (!$question) {
            return response()->json([
                'error' => 'Please provide a question via ?q=your+question'
            ], 422);
        }

        $result = $this->rag->query($question);

        return response()->json($result, 200, [], JSON_PRETTY_PRINT);
    }
}