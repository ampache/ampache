<?php

namespace MusicBrainz;

/**
 * Represents a MusicBrainz collection object
 *
 */
class Collection
{
    public $id;

    private $data;

    public function __construct(array $collection)
    {
        $this->data = $collection;

        $this->id = isset($collection['id']) ? (string) $collection['id'] : '';
    }
}
