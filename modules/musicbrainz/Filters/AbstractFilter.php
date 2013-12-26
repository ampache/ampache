<?php

namespace MusicBrainz\Filters;

/**
 * This is the abstract filter which
 * contains the constructor which all
 * filters share because the only
 * difference between each filter class
 * is the valid argument types.
 *
 */
abstract class AbstractFilter
{
    protected $validArgTypes;
    protected $validArgs;

    public function __construct($args)
    {
        $this->validArgs = array();
        foreach ($args as $key => $value) {
            if (in_array($key,$this->validArgTypes)) {
                $this->validArgs[$key] = $value;
            }
        }
    }

    public function createParameters(array $params = array())
    {
        if (!empty($this->validArgs)) {
            $params = $params + array('query' => '');

            if ($params['query'] == '') {
                foreach ($this->validArgs as $key => $val) {
                    if ($params['query'] != '') {
                        $params['query'] .= '+AND+';
                    }
                    if ($key == 'arid')
                    {
                        $params['query'] .= $key . ':' . $val;
                    } else {
                        $params['query'] .= $key . ':' .  urlencode(preg_replace('/([\+\-\!\(\)\{\}\[\]\^\~\*\?\:\\\\])/', '/$1', $val));
                    }
                }
            }
        }

        return $params;
    }
}
