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
namespace Tmdb\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tmdb\Exception\RuntimeException;
use Tmdb\HttpClient\HttpClientEventSubscriber;
use Tmdb\HttpClient\Response;

/**
 * Class RequestSubscriber
 * @package Tmdb\Event
 */
class RequestSubscriber extends HttpClientEventSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            TmdbEvents::REQUEST => 'send',
        ];
    }

    /**
     * @param RequestEvent             $event
     * @param string                   $eventName
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return string|Response
     */
    public function send(RequestEvent $event, $eventName, EventDispatcherInterface $eventDispatcher)
    {
        // Preparation of request parameters / Possibility to use for logging and caching etc.
        $eventDispatcher->dispatch(TmdbEvents::BEFORE_REQUEST, $event);

        if ($event->isPropagationStopped() && $event->hasResponse()) {
            return $event->getResponse();
        }

        $response = $this->sendRequest($event);
        $event->setResponse($response);

        // Possibility to cache the request
        $eventDispatcher->dispatch(TmdbEvents::AFTER_REQUEST, $event);

        return $response;
    }

    /**
     * Call upon the adapter to create an response object
     *
     * @param  RequestEvent $event
     * @throws \Exception
     * @return Response
     */
    public function sendRequest(RequestEvent $event)
    {
        switch ($event->getMethod()) {
            case 'GET':
                $response = $this->getHttpClient()->getAdapter()->get($event->getRequest());
                break;
            case 'HEAD':
                $response = $this->getHttpClient()->getAdapter()->head($event->getRequest());
                break;
            case 'POST':
                $response = $this->getHttpClient()->getAdapter()->post($event->getRequest());
                break;
            case 'PUT':
                $response = $this->getHttpClient()->getAdapter()->put($event->getRequest());
                break;
            case 'PATCH':
                $response = $this->getHttpClient()->getAdapter()->patch($event->getRequest());
                break;
            case 'DELETE':
                $response = $this->getHttpClient()->getAdapter()->delete($event->getRequest());
                break;
            default:
                throw new RuntimeException(sprintf('Unkown request method "%s".', $event->getMethod()));
        }

        return $response;
    }
}
