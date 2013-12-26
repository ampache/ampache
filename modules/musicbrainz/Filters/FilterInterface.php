<?php

namespace MusicBrainz\Filters;

interface FilterInterface
{
    public function getEntity();

    public function parseResponse(array $response);

}
