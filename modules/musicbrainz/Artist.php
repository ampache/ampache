<?php

namespace MusicBrainz;

/**
 * Represents a MusicBrainz artist object
 *
 */
class Artist
{
    public $id;
    public $name;

    private $type;
    private $sortName;
    private $gender;
    private $country;
    private $beginDate;
    private $endDate;
    private $data;
    private $releases = array();

    public function __construct(array $artist)
    {
        $this->data = $artist;

        $this->id        = isset($artist['id']) ? (string) $artist['id'] : '';
        $this->type      = isset($artist['type']) ? (string) $artist['type'] : '';
        $this->name      = isset($artist['name']) ? (string) $artist['name'] : '';
        $this->sortName  = isset($artist['sort-name']) ? (string) $artist['sort-name'] : '';
        $this->gender    = isset($artist['gender']) ? (string) $artist['gender'] : '';
        $this->country   = isset($artist['country']) ? (string) $artist['country'] : '';
        $this->beginDate = isset($artist['life-span']['begin']) ? (string) $artist['life-span']['begin'] : '';
        $this->endDate   = isset($artist['life-span']['ended']) ? (string) $artist['life-span']['ended'] : '';
    }
}
