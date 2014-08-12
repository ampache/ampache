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
use Tmdb\ApiToken;

/**
 * Class ApiTokenPlugin
 * @package Tmdb\HttpClient\Plugin
 */
class ApiTokenPlugin implements EventSubscriberInterface
{
    /**
     * @var \Tmdb\ApiToken
     */
    private $token;

    public function __construct(ApiToken $token)
    {
        $this->token = $token;
    }

    public static function getSubscribedEvents()
    {
        return array('request.before_send' => 'onBeforeSend');
    }

    public function onBeforeSend(Event $event)
    {
        $url = $event['request']->getUrl(true);

        $url->getQuery()->set('api_key', $this->token->getToken());

        $event['request']->setUrl($url);
    }
}
