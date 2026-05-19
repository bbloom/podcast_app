<?php

namespace MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Exceptions;

/**
 * Thrown by UploadRecordingService when an S3 operation fails.
 * Caught by UploadRecordingController to return a graceful error response
 * rather than an unhandled exception page.
 */
class UploadRecordingException extends \RuntimeException
{
    //
}