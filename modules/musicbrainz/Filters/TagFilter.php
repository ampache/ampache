<?php

namespace MusicBrainz\Filters;

use MusicBrainz\Tag;

/**
 * This is the tag filter and it contains
 * an array of valid argument types to be used
 * when querying the MusicBrainz web service.
 */
class TagFilter extends AbstractFilter implements FilterInterface
{
    protected $validArgTypes =
        array(
            'tag'
        );

    public function getEntity()
    {
        return 'tag';
    }

    public function parseResponse(array $response)
    {
        $tags = array();
        foreach ($response['tags'] as $tag) {
            $tags[] = new Tag($tag);
        }

        return $tags;
    }
}
