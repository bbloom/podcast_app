<?php

namespace MediaPlatform\Videos\Enums;

/**
 * Publication status for a video.
 */
enum VideoStatus: string
{
    /** Video is not yet published to YouTube. */
    case not_published_to_youtube = 'not-published-to-youtube';

    /** Video is published to YouTube. */
    case published_to_youtube = 'published-to-youtube';
}