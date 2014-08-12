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
namespace Tmdb\HttpClient\Plugin;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AdultFilterPlugin
 * @package Tmdb\HttpClient\Plugin
 */
class AdultFilterPlugin implements EventSubscriberInterface
{
    private $includeAdult;

    public function __construct($includeAdult = false)
    {
        $this->includeAdult = $includeAdult;
    }

    public static function getSubscribedEvents()
    {
        return array('request.before_send' => 'onBeforeSend');
    }

    public function onBeforeSend(Event $event)
    {
        $url = $event['request']->getUrl(true);

        $url->getQuery()->set('include_adult', $this->includeAdult === true ? 'true' : 'false');

        $event['request']->setUrl($url);
    }
}
