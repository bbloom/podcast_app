<?php

namespace MediaPlatform\API\v1\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use MediaPlatform\API\v1\Resources\PodcastEpisodeResource;
use MediaPlatform\API\v1\Resources\PodcastGuestResource;
use MediaPlatform\API\v1\Resources\PodcastSponsorResource;
use MediaPlatform\API\v1\Services\PodcastEpisodeApiService;

class PodcastEpisodesController extends Controller
{
    /**
     * Return the full podcast API payload for the Astro build.
     *
     * All logic is delegated to PodcastEpisodeApiService.
     * Resources handle the transformation of each model.
     */
    public function __invoke(PodcastEpisodeApiService $service): JsonResponse
    {
        $payload = $service->getPayload();

        return response()->json([
            'episodes' => PodcastEpisodeResource::collection($payload['episodes'])->resolve(),
            'guests'   => PodcastGuestResource::collection($payload['guests'])->resolve(),
            'sponsors' => PodcastSponsorResource::collection($payload['sponsors'])->resolve(),
        ]);
    }
}