<?php

namespace MediaPlatform\Digest\Processing\Exceptions;

use RuntimeException;

class LlmException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $providerSlug = '',
        public readonly string $modelSlug = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
