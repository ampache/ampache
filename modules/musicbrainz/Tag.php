<?php

namespace MusicBrainz;

/**
 * Represents a MusicBrainz tag object
 *
 */
class Tag
{
    public $name;
    public $score;

    private $data;

    public function __construct(array $tag)
    {
        $this->data = $tag;

        $this->name  = isset($tag['name']) ? (string) $tag['name'] : '';
        $this->score = isset($tag['score']) ? (string) $tag['score'] : '';
    }
}
