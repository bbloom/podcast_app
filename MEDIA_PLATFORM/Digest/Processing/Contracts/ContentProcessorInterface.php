<?php

namespace MediaPlatform\Digest\Processing\Contracts;

interface ContentProcessorInterface
{
    /**
     * Process a single list_source — fetch new content, summarise, and store results.
     *
     * @param  object  $listSource  Row from the list_sources table (stdClass from DB::table query)
     * @return array   Stats with keys: [fetched, processed, skipped, errors]
     */
    public function process(object $listSource): array;
}
