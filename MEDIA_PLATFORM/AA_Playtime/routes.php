<?php

use Illuminate\Support\Facades\Route;

use MediaPlatform\AA_Playtime\AskGemini;
use MediaPlatform\AA_Playtime\PromptGemini;
use MediaPlatform\AA_Playtime\GetListGeminiModels;
use MediaPlatform\AA_Playtime\SummarizeYoutubeVideo;
use MediaPlatform\AA_Playtime\RagIngest;
use MediaPlatform\AA_Playtime\RagQuery;




Route::get('/play/ask',     [AskGemini::class, 'ask'])->middleware(['auth']);

Route::get('/play/prompt',  [PromptGemini::class, 'prompt'])->middleware(['auth']);

Route::get('/play/getlist', [GetListGeminiModels::class, 'getList'])->middleware(['auth']);

Route::get('/play/youtube', [SummarizeYoutubeVideo::class, 'summarize'])->middleware(['auth']);

Route::get('/play/ingest',  [RagIngest::class, 'ingest'])->middleware(['auth']);

Route::get('/play/query',   [RagQuery::class, 'query'])->middleware(['auth']);