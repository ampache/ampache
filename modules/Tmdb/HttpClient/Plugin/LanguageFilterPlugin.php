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
 * Class LanguageFilterPlugin
 * @package Tmdb\HttpClient\Plugin
 */
class LanguageFilterPlugin implements EventSubscriberInterface
{
    private $language;

    public function __construct($language = 'en')
    {
        $this->language = $language;
    }

    public static function getSubscribedEvents()
    {
        return array('request.before_send' => 'onBeforeSend');
    }

    public function onBeforeSend(Event $event)
    {
        $url = $event['request']->getUrl(true);

        $url->getQuery()->set('language', $this->language);

        $event['request']->setUrl($url);
    }
}
