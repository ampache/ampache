<?php

declare(strict_types=0);

/*
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

namespace Ampache\Application\Api\Ajax;

use Ampache\Application\Api\Ajax\Handler\AjaxHandlerInterface;
use Ampache\Application\Api\Ajax\Handler\BrowseHandler;
use Ampache\Application\Api\Ajax\Handler\CatalogHandler;
use Ampache\Application\Api\Ajax\Handler\DefaultHandler;
use Ampache\Application\Api\Ajax\Handler\DemocraticHandler;
use Ampache\Application\Api\Ajax\Handler\IndexHandler;
use Ampache\Application\Api\Ajax\Handler\LocalplayHandler;
use Ampache\Application\Api\Ajax\Handler\PlayerHandler;
use Ampache\Application\Api\Ajax\Handler\PlaylistHandler;
use Ampache\Application\Api\Ajax\Handler\PodcastHandler;
use Ampache\Application\Api\Ajax\Handler\RandomHandler;
use Ampache\Application\Api\Ajax\Handler\SearchHandler;
use Ampache\Application\Api\Ajax\Handler\SongHandler;
use Ampache\Application\Api\Ajax\Handler\StatsHandler;
use Ampache\Application\Api\Ajax\Handler\StreamHandler;
use Ampache\Application\Api\Ajax\Handler\TagHandler;
use Ampache\Application\Api\Ajax\Handler\UserHandler;
use Ampache\Application\ApplicationInterface;
use Psr\Container\ContainerInterface;

final class AjaxApplication implements ApplicationInterface
{
    private $dic;

    public function __construct(
        ContainerInterface $dic
    ) {
        $this->dic = $dic;
    }

    public function run(): void
    {
        $handlerList = [
            'browser' => BrowseHandler::class,
            'catalog' => CatalogHandler::class,
            'democratic' => DemocraticHandler::class,
            'index' => IndexHandler::class,
            'localplay' => LocalplayHandler::class,
            'player' => PlayerHandler::class,
            'playlist' => PlaylistHandler::class,
            'podcast' => PodcastHandler::class,
            'random' => RandomHandler::class,
            'search' => SearchHandler::class,
            'song' => SongHandler::class,
            'stats' => StatsHandler::class,
            'stream' => StreamHandler::class,
            'tag' => TagHandler::class,
            'user' => UserHandler::class,
        ];

        xoutput_headers();

        $page = $_REQUEST['page'] ?? null;
        if ($page) {
            debug_event('ajax.server', 'Called for page: {' . $page . '}', 5);
        }

        $handlerClassName = $handlerList[$page] ?? DefaultHandler::class;

        /** @var AjaxHandlerInterface $handler */
        $handler = $this->dic->get($handlerClassName);

        $handler->handle();
    }
}
