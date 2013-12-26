<?php

namespace MusicBrainz\Clients;

/**
 * MusicBrainz HTTP Client interfance
 */
abstract class MbClient
{
    const URL = 'http://musicbrainz.org/ws/2';
    
    /**
     * Perform an HTTP request on MusicBrainz
     *
     * @param  string $path
     * @param  array  $params
     * @param  string $options
     * @param  boolean $isAuthRequred
     * @return array
     */
    abstract public function call($path, array $params = array(), array $options = array(), $isAuthRequred = false);
}
