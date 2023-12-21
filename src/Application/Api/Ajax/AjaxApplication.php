<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Application\Api\Ajax;

use Ampache\Application\Api\Ajax\Handler\AjaxHandlerInterface;
use Ampache\Application\Api\Ajax\Handler\BrowseAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\CatalogAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\DefaultAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\DemocraticPlaybackAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\IndexAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\LocalPlayAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\PlayerAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\PlaylistAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\PodcastAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\RandomAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\SearchAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\SongAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\StatsAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\StreamAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\TagAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\UserAjaxHandler;
use Ampache\Application\ApplicationInterface;
use Psr\Container\ContainerInterface;

final class AjaxApplication implements ApplicationInterface
{
    /** @var array<string, class-string> */
    private const HANDLER_LIST = [
        'browse' => BrowseAjaxHandler::class,
        'catalog' => CatalogAjaxHandler::class,
        'democratic' => DemocraticPlaybackAjaxHandler::class,
        'index' => IndexAjaxHandler::class,
        'localplay' => LocalPlayAjaxHandler::class,
        'player' => PlayerAjaxHandler::class,
        'playlist' => PlaylistAjaxHandler::class,
        'podcast' => PodcastAjaxHandler::class,
        'random' => RandomAjaxHandler::class,
        'search' => SearchAjaxHandler::class,
        'song' => SongAjaxHandler::class,
        'stats' => StatsAjaxHandler::class,
        'stream' => StreamAjaxHandler::class,
        'tag' => TagAjaxHandler::class,
        'user' => UserAjaxHandler::class,
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

        $page = $_REQUEST['page'] ?? null;
        if ($page) {
            debug_event('ajax.server', 'Called for page: {' . $page . '}', 5);
        }

        $handlerClassName = static::HANDLER_LIST[$page] ?? DefaultAjaxHandler::class;

        /** @var AjaxHandlerInterface $handler */
        $handler = $this->dic->get($handlerClassName);

        $handler->handle();
    }
}
