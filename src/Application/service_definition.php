<?php

declare(strict_types=1);

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

namespace Ampache\Application;

use Ampache\Application\Api\Ajax\AjaxApplication;
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
use Ampache\Application\Api\Upnp\CmControlReplyApplication;
use Ampache\Application\Api\Upnp\ControlReplyApplication;
use Ampache\Application\Api\Upnp\EventReplyApplication;
use Ampache\Application\Api\Upnp\MediaServiceDescriptionApplication;
use Ampache\Application\Api\Upnp\PlayStatusApplication;
use Ampache\Application\Api\Upnp\UpnpApplication;
use function DI\autowire;

/**
 * Provides the definition of all services
 */
return [
    UpnpApplication::class => autowire(UpnpApplication::class),
    PlayStatusApplication::class => autowire(PlayStatusApplication::class),
    CmControlReplyApplication::class => autowire(CmControlReplyApplication::class),
    ControlReplyApplication::class => autowire(ControlReplyApplication::class),
    EventReplyApplication::class => autowire(EventReplyApplication::class),
    MediaServiceDescriptionApplication::class => autowire(MediaServiceDescriptionApplication::class),
    AjaxApplication::class => autowire(AjaxApplication::class),
    BrowseAjaxHandler::class => autowire(BrowseAjaxHandler::class),
    DefaultAjaxHandler::class => autowire(DefaultAjaxHandler::class),
    CatalogAjaxHandler::class => autowire(CatalogAjaxHandler::class),
    DemocraticPlaybackAjaxHandler::class => autowire(DemocraticPlaybackAjaxHandler::class),
    IndexAjaxHandler::class => autowire(IndexAjaxHandler::class),
    LocalPlayAjaxHandler::class => autowire(LocalPlayAjaxHandler::class),
    PlayerAjaxHandler::class => autowire(PlayerAjaxHandler::class),
    PlaylistAjaxHandler::class => autowire(PlaylistAjaxHandler::class),
    PodcastAjaxHandler::class => autowire(PodcastAjaxHandler::class),
    RandomAjaxHandler::class => autowire(RandomAjaxHandler::class),
    SearchAjaxHandler::class => autowire(SearchAjaxHandler::class),
    SongAjaxHandler::class => autowire(SongAjaxHandler::class),
    StatsAjaxHandler::class => autowire(StatsAjaxHandler::class),
    StreamAjaxHandler::class => autowire(StreamAjaxHandler::class),
    TagAjaxHandler::class => autowire(TagAjaxHandler::class),
    UserAjaxHandler::class => autowire(UserAjaxHandler::class),
];
