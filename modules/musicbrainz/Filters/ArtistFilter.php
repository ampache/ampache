<?php

namespace MusicBrainz\Filters;

use MusicBrainz\Artist;

/**
 * This is the artist filter and it contains
 * an array of valid argument types to be used
 * when querying the MusicBrainz web service.
 */
class ArtistFilter extends AbstractFilter implements FilterInterface
{
    protected $validArgTypes =
        array(
            'arid',
            'artist',
            'artistaccent',
            'alias',
            'begin',
            'comment',
            'country',
            'end',
            'ended',
            'gender',
            'ipi',
            'sortname',
            'tag',
            'type'
        );

    public function getEntity()
    {
        return 'artist';
    }

    public function parseResponse(array $response)
    {
        $artists = array();
        if (isset($response['artist'])) {
            foreach ($response['artist'] as $artist) {
                $artists[] = new Artist($artist);
            }
        } elseif (isset($response['artists'])) {
            foreach ($response['artists'] as $artist) {
                $artists[] = new Artist($artist);
            }
        }

        return $artists;
    }
}
