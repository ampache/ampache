<?php

namespace MusicBrainz\Filters;

use MusicBrainz\Recording;

/**
 * This is the recording filter and it contains
 * an array of valid argument types to be used
 * when querying the MusicBrainz web service.
 */
class RecordingFilter extends AbstractFilter implements FilterInterface
{
    public $validArgTypes =
        array(
            'arid',
            'artist',
            'artistname',
            'creditname',
            'comment',
            'country',
            'date',
            'dur',
            'format',
            'isrc',
            'number',
            'position',
            'primarytype',
            'puid',
            'qdur',
            'recording',
            'recordingaccent',
            'reid',
            'release',
            'rgid',
            'rid',
            'secondarytype',
            'status',
            'tnum',
            'tracks',
            'tracksrelease',
            'tag',
            'type'
        );

    public function getEntity()
    {
        return 'recording';
    }

    public function parseResponse(array $response)
    {
        $recordings = array();
        if (isset($response['recording'])) {
            foreach ($response['recording'] as $recording) {
                $recordings[] = new Recording($recording);
            }
        } elseif (isset($response['recordings'])) {
            foreach ($response['recordings'] as $recording) {
                $recordings[] = new Recording($recording);
            }
        }

        return $recordings;
    }

}
