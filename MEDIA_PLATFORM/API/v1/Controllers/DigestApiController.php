<?php

namespace MediaPlatform\API\v1\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MediaPlatform\API\v1\Services\DigestApiService;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Enums\OutputType;

/**
 * DigestApiController — serves published digest data to static site generators.
 *
 * Single-action controller invoked by Astro (or any static site generator)
 * during its build process. Returns all retained published digests for a
 * given list as structured JSON.
 *
 * The list is identified by the X-Digest-List request header, which must
 * match the list's `name` field exactly.
 *
 * Authentication is handled by the existing API middleware stack:
 *   - CheckApiEnabled    → 503 if the API is switched off
 *   - AuthenticateApiClient → validates bearer token + domain header
 *
 * Full URL: GET /api/v1/digests
 */
class DigestApiController extends Controller
{
    public function __invoke(Request $request, DigestApiService $service): JsonResponse
    {
        $listName = $request->header('X-Digest-List');

        if (! $listName) {
            return response()->json([
                'error' => 'Missing X-Digest-List header.',
            ], 400);
        }

        $list = ListModel::where('name', $listName)
            ->where('output_type', OutputType::StaticSite->value)
            ->first();

        if (! $list) {
            return response()->json([
                'error' => 'List not found or not configured for static site output.',
            ], 404);
        }

        $data = $service->getDigestsForList($list);

        return response()->json($data, 200);
    }
}