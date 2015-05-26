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
use Tmdb\Common\ObjectHydrator;
use Tmdb\HttpClient\HttpClientEventSubscriber;

/**
 * Class RequestSubscriber
 * @package Tmdb\Event
 */
class HydrationSubscriber extends HttpClientEventSubscriber
{
    /**
     * Get subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            TmdbEvents::HYDRATE => 'hydrate',
        ];
    }

    /**
     * Hydrate the subject with data
     *
     * @param HydrationEvent           $event
     * @param string                   $eventName
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return \Tmdb\Model\AbstractModel
     */
    public function hydrate(HydrationEvent $event, $eventName, $eventDispatcher)
    {
        // Possibility to load serialized cache
        $eventDispatcher->dispatch(TmdbEvents::BEFORE_HYDRATION, $event);

        if ($event->isPropagationStopped()) {
            return $event->getSubject();
        }

        $subject = $this->hydrateSubject($event);
        $event->setSubject($subject);

        // Possibility to cache the data
        $eventDispatcher->dispatch(TmdbEvents::AFTER_HYDRATION, $event);

        return $event->getSubject();
    }

    /**
     * Hydrate the subject
     *
     * @param  HydrationEvent            $event
     * @return \Tmdb\Model\AbstractModel
     */
    public function hydrateSubject(HydrationEvent $event)
    {
        $objectHydrator = new ObjectHydrator();

        return $objectHydrator->hydrate($event->getSubject(), $event->getData());
    }
}
