<?php

namespace MediaPlatform\AA_Playtime;

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RagService
{
    private int $chunkSize    = 500;
    private int $chunkOverlap = 100;

    // -------------------------------------------------------------------------
    // PUBLIC ENTRY POINTS
    // -------------------------------------------------------------------------

    public function ingest(string $feedUrl): array
{
    $articles = $this->fetchRssFeed($feedUrl);
    echo "<hr>Articles found: " . count($articles);

    $stored = 0;

    foreach ($articles as $article) {
        echo "<hr>Processing: " . $article['title'];

        $exists = DB::table('article_chunks')
            ->where('article_url', $article['url'])
            ->exists();

        if ($exists) {
            echo "<hr>SKIPPING - already exists: " . $article['url'];
            continue;
        }

        $chunks = $this->chunkText($article['content']);
        echo "<hr>Chunks created: " . count($chunks);

        foreach ($chunks as $index => $chunk) {
            echo "<hr>Embedding chunk $index...";

            $embedding = $this->getEmbedding($chunk);

            if (!$embedding) {
                echo "<hr>EMBEDDING FAILED for chunk $index";
                continue;
            }

            echo "<hr>Embedding OK - inserting chunk $index";

            DB::table('article_chunks')->insert([
                'article_url'   => $article['url'],
                'article_title' => $article['title'],
                'chunk_text'    => $chunk,
                'chunk_index'   => $index,
                'embedding'     => $this->vectorToString($embedding),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $stored++;
            echo "<hr>Inserted chunk $index OK";
        }
    }

    return [
        'articles_found' => count($articles),
        'chunks_stored'  => $stored,
    ];
}

    public function query(string $userQuery, int $topK = 5): array
    {
        $queryEmbedding = $this->getEmbedding($userQuery);

        if (!$queryEmbedding) {
            return ['error' => 'Failed to embed query.'];
        }

        $vectorStr = $this->vectorToString($queryEmbedding);

        $chunks = DB::select("
            SELECT article_title, chunk_text,
                1 - (embedding <=> ?::halfvec) AS similarity
            FROM article_chunks
            ORDER BY embedding <=> ?::halfvec
            LIMIT ?
        ", [$vectorStr, $vectorStr, $topK]);

        if (empty($chunks)) {
            return ['error' => 'No chunks found. Have you run ingest yet?'];
        }

        $context = collect($chunks)
            ->map(fn($c) => "### {$c->article_title}\n{$c->chunk_text}")
            ->join("\n\n---\n\n");

        $answer = $this->generate($userQuery, $context);

        return [
            'query'          => $userQuery,
            'chunks_used'    => count($chunks),
            'top_similarity' => round($chunks[0]->similarity, 4),
            'answer'         => $answer,
            'source_titles'  => collect($chunks)->pluck('article_title')->unique()->values(),
        ];
    }

    // -------------------------------------------------------------------------
    // RSS FETCHING
    // -------------------------------------------------------------------------

    private function fetchRssFeed(string $feedUrl): array
    {
        $response = Http::timeout(15)->get($feedUrl);
        // dd($response->body());

        if (!$response->ok()) {
            Log::error('RSS fetch failed: ' . $response->status());
            return [];
        }

        $xml      = simplexml_load_string($response->body());
        $articles = [];

        foreach ($xml->channel->item as $item) {
            $namespaces = $item->getNamespaces(true);
            $content    = isset($namespaces['content'])
                ? (string) $item->children($namespaces['content'])->encoded
                : (string) $item->description;

            $content = strip_tags($content);
            $content = trim(preg_replace('/\s+/', ' ', $content));

            if (strlen($content) < 50) {
                continue;
            }

            $articles[] = [
                'url'         => (string) $item->link,
                'title'       => (string) $item->title,
                'description' => strip_tags(trim((string) $item->description)),
                'content'     => $content,
            ];
        }

        // FOR TESTING... JUST GRAB THE FIRST THREE ARTICLES
        $articles = array_slice($articles, 0, 3);

        echo "<hr>this is in fetchRssFeed(), line 145";
        echo "<pre>";
        print_r($articles);
        echo "<pre><hr>";
        //die();


        return $articles;
    }

    // -------------------------------------------------------------------------
    // TEXT CHUNKING
    // -------------------------------------------------------------------------

    private function chunkText(string $text): array
    {
        $chunks = [];
        $len    = strlen($text);
        $start  = 0;

        while ($start < $len) {
            $end      = min($start + $this->chunkSize, $len);
            $chunks[] = trim(substr($text, $start, $end - $start));

            // we've reached the end of the text
            if ($end === $len) break; 

            $start    = $end - $this->chunkOverlap;
        }

        return array_filter($chunks, fn($c) => strlen($c) > 20);
    }

    // -------------------------------------------------------------------------
    // GEMINI — EMBEDDING
    // -------------------------------------------------------------------------

    private function getEmbedding(string $text): ?array
    {
        try {
            $response = Gemini::embeddingModel('gemini-embedding-001')
                ->embedContent($text);

            return $response->embedding->values;
        } catch (\Throwable $e) {
            echo "<hr>EMBEDDING EXCEPTION: " . $e->getMessage();
            echo "<hr>EXCEPTION CLASS: " . get_class($e);
            Log::error('Gemini embedding error: ' . $e->getMessage());
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // GEMINI — GENERATION
    // -------------------------------------------------------------------------

    private function generate(string $query, string $context): string
    {
        $prompt = <<<PROMPT
You are a helpful assistant. Using ONLY the context provided below, answer the user's question concisely.
If the context does not contain enough information to answer, say so clearly.

CONTEXT:
{$context}

USER QUESTION:
{$query}
PROMPT;

        try {
            $response = Gemini::generativeModel('gemini-2.5-flash')
                ->generateContent($prompt);

            return $response->text();
        } catch (\Throwable $e) {
            echo "<hr>GENERATION EXCEPTION: " . $e->getMessage();
            Log::error('Gemini generation error: ' . $e->getMessage());
            return 'Generation failed. Check logs.';
        }
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    private function vectorToString(array $vector): string
    {
        return '[' . implode(',', $vector) . ']';
    }
}