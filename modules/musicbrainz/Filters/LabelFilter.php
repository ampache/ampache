<?php

namespace MusicBrainz\Filters;

use MusicBrainz\Label;

/**
 * This is the label filter and it contains
 * an array of valid argument types to be used
 * when querying the MusicBrainz web service.
 */
class LabelFilter extends AbstractFilter implements FilterInterface
{
    protected $validArgTypes =
        array(
            'aliaas',
            'begin',
            'code',
            'comment',
            'country',
            'end',
            'ended',
            'ipi',
            'label',
            'labelaccent',
            'laid',
            'sortname',
            'tag',
            'type'
        );

    public function getEntity()
    {
        return 'label';
    }

    public function parseResponse(array $response)
    {
        $labels = array();

        foreach ($response['labels'] as $label) {
            $labels[] = new Label($label);
        }

        return $labels;
    }
}
