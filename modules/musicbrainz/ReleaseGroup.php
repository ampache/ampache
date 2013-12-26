<?php

namespace MusicBrainz;

/**
 * Represents a MusicBrainz release group
 *
 */
class ReleaseGroup
{
    public $id;

    private $data;

    public function __construct(array $releaseGroup)
    {
        $this->data = $releaseGroup;

        $this->id = isset($releaseGroup['id']) ? (string) $releaseGroup['id'] : '';
    }
}
