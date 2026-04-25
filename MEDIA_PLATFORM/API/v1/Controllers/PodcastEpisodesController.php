<?php

namespace MediaPlatform\API\v1\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MediaPlatform\API\v1\Resources\PodcastEpisodeResource;
use MediaPlatform\API\v1\Resources\PodcastGuestResource;
use MediaPlatform\API\v1\Resources\PodcastShowResource;
use MediaPlatform\API\v1\Resources\PodcastSponsorResource;
use MediaPlatform\API\v1\Services\PodcastEpisodeApiService;

class PodcastEpisodesController extends Controller
{
    /**
     * Return the full podcast API payload for the Astro build.
     *
     * The PodcastShowSlug header identifies which show's episodes to return.
     * All logic is delegated to PodcastEpisodeApiService.
     * Resources handle the transformation of each model.
     */
    public function __invoke(Request $request, PodcastEpisodeApiService $service): JsonResponse
    {
        $podcastShowSlug = $request->header('PodcastShowSlug');

        if (! $podcastShowSlug) {
            return response()->json(['error' => 'Missing PodcastShowSlug header.'], 422);
        }

        $payload = $service->getPayload($podcastShowSlug);

        $response = [
            'show'     => $payload['show']
                ? (new PodcastShowResource($payload['show']))->resolve()
                : null,
            'episodes' => PodcastEpisodeResource::collection($payload['episodes'])->resolve(),
            'guests'   => PodcastGuestResource::collection($payload['guests'])->resolve(),
            'sponsors' => PodcastSponsorResource::collection($payload['sponsors'])->resolve(),
        ];

        if (isset($payload['bob_bloom_archive'])) {
            $response['bob_bloom_archive'] = $payload['bob_bloom_archive'];
        }

        return response()->json($response);
    }
}