<?php

namespace MusicBrainz\Clients;

use MusicBrainz\Exception;
use Guzzle\Http\ClientInterface;

/**
 * Guzzle Client
 */
class GuzzleMbClient extends MbClient
{
    /**
     * The Guzzle client used to make cURL requests
     *
     * @var \Guzzle\Http\ClientInterface
     */
    private $client;

    /**
     * Initializes the class.
     *
     * @param \Guzzle\Http\ClientInterface $client   The Guzzle client used to make requests
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
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

        $this->client->setBaseUrl(MbClient::URL);
        $this->client->setConfig(array(
            'data' => $params
        ));

        $request = $this->client->get($path . '{?data*}');
        $request->setHeader('Accept', 'application/json');
        $request->setHeader('User-Agent', $options['user-agent']);

        if ($isAuthRequred) {
            if ($options['user'] != null && $options['password'] != null) {
                $request->setAuth($options['user'], $options['password'], CURLAUTH_DIGEST);
            } else {
                throw new Exception('Authentication is required');
            }
        }

        $request->getQuery()->useUrlEncoding(false);

        return $request->send()->json();
    }
}
