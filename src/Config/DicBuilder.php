<?php

declare(strict_types=1);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

/**
 * This file creates and initializes the central DI-Container
 */
namespace Ampache\Config;

use Ampache\Config\Init\Exception\EnvironmentNotSuitableException;
use Ampache\Config\Init\Init;
use Ampache\Config\Init\InitializationHandlerConfig;
use Ampache\Config\Init\InitializationHandlerDatabaseUpdate;
use Ampache\Config\Init\InitializationHandlerEnvironment;
use Ampache\Config\Init\InitializationHandlerGetText;
use Ampache\Config\Init\InitializationHandlerAuth;
use Ampache\Config\Init\InitializationHandlerGlobals;
use Ampache\Module\Util\EnvironmentInterface;
use DI\ContainerBuilder;
use getID3;
use MusicBrainz\HttpAdapters\RequestsHttpAdapter;
use MusicBrainz\MusicBrainz;
use Psr\Container\ContainerInterface;
use SpotifyWebAPI\SpotifyWebAPI;
use function DI\autowire;
use function DI\factory;

$builder = new ContainerBuilder();
$builder->addDefinitions([
    ConfigContainerInterface::class => factory(static function (): ConfigContainerInterface {
        return new ConfigContainer(AmpConfig::get_all());
    }),
    getID3::class => autowire(getID3::class),
    MusicBrainz::class => factory(static function (): MusicBrainz {
        return new MusicBrainz(new RequestsHttpAdapter());
    }),
    SpotifyWebAPI::class => factory(static function (): SpotifyWebAPI {
        return new SpotifyWebAPI();
    }),
    Init::class => factory(static function (ContainerInterface $c): Init {
        return new Init(
            $c->get(EnvironmentInterface::class),
            [
                $c->get(InitializationHandlerConfig::class),
                $c->get(InitializationHandlerEnvironment::class),
                $c->get(InitializationHandlerDatabaseUpdate::class),
                $c->get(InitializationHandlerAuth::class),
                $c->get(InitializationHandlerGetText::class),
                $c->get(InitializationHandlerGlobals::class),
            ]
        );
    }),
]);
$builder->addDefinitions(
    require_once __DIR__ . '/../Application/service_definition.php',
    require_once __DIR__ . '/../Module/Util/service_definition.php',
    require_once __DIR__ . '/../Module/WebDav/service_definition.php',
    require_once __DIR__ . '/../Module/Authentication/service_definition.php',
    require_once __DIR__ . '/../Module/Cache/service_definition.php',
    require_once __DIR__ . '/../Module/Channel/service_definition.php',
    require_once __DIR__ . '/../Module/Song/service_definition.php',
    require_once __DIR__ . '/../Module/Playlist/service_definition.php',
    require_once __DIR__ . '/../Module/Album/service_definition.php',
    require_once __DIR__ . '/../Module/Art/service_definition.php',
    require_once __DIR__ . '/../Module/Broadcast/service_definition.php',
    require_once __DIR__ . '/../Module/Database/service_definition.php',
    require_once __DIR__ . '/../Module/Catalog/service_definition.php',
    require_once __DIR__ . '/../Module/Artist/service_definition.php',
    require_once __DIR__ . '/../Module/LastFm/service_definition.php',
    require_once __DIR__ . '/../Module/System/service_definition.php',
    require_once __DIR__ . '/../Model/service_definition.php',
);

return $builder->build();
