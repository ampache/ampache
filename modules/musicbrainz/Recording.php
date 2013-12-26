<?php

namespace MusicBrainz;

/**
 * Represents a MusicBrainz Recording object
 *
 */
class Recording
{
    public $id;
    public $title;
    public $releases = array();

    private $data;

    public function __construct(array $recording)
    {
        $this->data = $recording;

        $this->id    = (string) $recording['id'];
        $this->title = (string) $recording['title'];
    }
}
