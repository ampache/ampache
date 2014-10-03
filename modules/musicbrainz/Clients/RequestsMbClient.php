<?php

namespace MusicBrainz\Clients;

use MusicBrainz\Exception;
use Requests;

/**
 * Requests Client
 */
class RequestsMbClient extends MbClient
{
    /**
     * Initializes the class.
     */
    public function __construct()
    {
    }

    /**
     * Perform an HTTP request on MusicBrainz
     *
     * @param  string $path
     * @param  array  $params
     * @param  string $options
     * @param  boolean $isAuthRequred
     * @return array
     */
    public function call($path, array $params = array(), array $options = array(), $isAuthRequred = false)
    {
        if ($options['user-agent'] == '') {
            throw new Exception('You must set a valid User Agent before accessing the MusicBrainz API');
        }
        
        $url = MbClient::URL . '/' . $path;
        $i = 0;
        foreach ($params as $name => $value)
        {
            if ($i == 0) $url .= '?';
            else $url .= '&';
            
            $url .= urlencode($name) . '=' . urlencode($value);
            ++$i;
        }
        $headers = array();
        $headers['Accept'] = 'application/json';
        $headers['User-Agent'] = $options['user-agent'];
        $reqopt = array();
        if ($isAuthRequred) {
            if ($options['user'] != null && $options['password'] != null) {
                $reqopt['auth'] = array($options['user'], $options['password']);
            } else {
                throw new Exception('Authentication is required');
            }
        }
        $request = Requests::get($url, $headers, $reqopt);

        return json_decode($request->body, true);
    }
}
