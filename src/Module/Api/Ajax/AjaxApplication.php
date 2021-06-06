<?php
/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax;

use Ampache\Application\ApplicationInterface;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Api\Ajax\Handler\AjaxHandlerInterface;
use Ampache\Module\Api\Ajax\Handler\BrowseAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\CatalogAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\DefaultAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\DemocraticPlaybackAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\IndexAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\LocalPlayAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\PlayerAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\PlaylistAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\RandomAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\SearchAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\SongAjaxHandler;
use Ampache\Module\System\Core;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;
use function debug_event;
use function xoutput_headers;

final class AjaxApplication implements ApplicationInterface
{
    private const HANDLER_LIST = [
        'browse' => BrowseAjaxHandler::class,
        'catalog' => CatalogAjaxHandler::class,
        'democratic' => DemocraticPlaybackAjaxHandler::class,
        'index' => IndexAjaxHandler::class,
        'localplay' => LocalPlayAjaxHandler::class,
        'player' => PlayerAjaxHandler::class,
        'playlist' => PlaylistAjaxHandler::class,
        'podcast' => [
            'sync' => Handler\Podcast\SyncAction::class,
        ],
        'random' => RandomAjaxHandler::class,
        'search' => SearchAjaxHandler::class,
        'song' => SongAjaxHandler::class,
        'stats' => [
            'geolocation' => Handler\Stats\GeolocationAction::class,
        ],
        'stream' => [
            'set_play_type' => Handler\Stream\SetPlayTypeAction::class,
            'directplay' => Handler\Stream\DirectplayAction::class,
            'basket' => Handler\Stream\BasketAction::class,
        ],
        'tag' => [
            'get_tag_map' => Handler\Tag\GetTagMapAction::class,
            'get_labels' => Handler\Tag\GetLabelsAction::class,
            'add_filter' => Handler\Tag\AddFilterAction::class,
            'browse_type' => Handler\Tag\BrowseTypeAction::class,
            'add_tag_by_name' => Handler\Tag\AddTageByNameAction::class,
            'delete' => Handler\Tag\DeleteAction::class,
            'add_tag' => Handler\Tag\AddTagAction::class,
            'remove_tag_map' => Handler\Tag\RemoveTagMap::class,
        ],
        'user' => [
            'flip_follow' => Handler\User\FlipFollowAction::class
        ],
    ];

    private ContainerInterface $dic;

    public function __construct(
        ContainerInterface $dic
    ) {
        $this->dic = $dic;
    }

    public function run(): void
    {
        xoutput_headers();

        $request = $this->dic->get(ServerRequestCreatorInterface::class)->fromGlobals();

        $queryParams = $request->getQueryParams();

        $page = $queryParams['page'] ?? null;
        if ($page) {
            debug_event('ajax.server', 'Called for page: {' . $page . '}', 5);
        }

        $action = $queryParams['action'] ?? null;

        $handlerClassName = static::HANDLER_LIST[$page] ?? DefaultAjaxHandler::class;

        if (is_array($handlerClassName)) {
            if (array_key_exists($action, $handlerClassName)) {
                /** @var ActionInterface $handler */
                $handler = $this->dic->get($handlerClassName[$action]);

                $result = $handler->handle(
                    $request,
                    $this->dic->get(Psr17Factory::class)->createResponse(),
                    Core::get_global('user')
                );
            } else {
                $result = ['rfc3514' => '0x1'];
            }

            // We always do this
            echo xoutput_from_array($result);
        } else {
            /** @var AjaxHandlerInterface $handler */
            $handler = $this->dic->get($handlerClassName);

            $handler->handle(
                $request,
                $this->dic->get(Psr17Factory::class)->createResponse()
            );
        }
    }
}
