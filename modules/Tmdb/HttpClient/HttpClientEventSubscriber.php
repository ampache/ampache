<?php
/**
 * This file is part of the Tmdb PHP API created by Michael Roterman.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Tmdb
 * @author Michael Roterman <michael@wtfz.net>
 * @copyright (c) 2013, Michael Roterman
 * @version 0.0.1
 */
namespace Tmdb\HttpClient;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class HttpClientEventSubscriber
 * @package Tmdb\HttpClient
 */
abstract class HttpClientEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @param HttpClient $httpClient
     */
    public function attachHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }
}
