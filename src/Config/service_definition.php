<?php
/*
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

declare(strict_types=1);

namespace Ampache\Config;

use Ampache\Config\Init\Init;
use Ampache\Config\Init\InitializationHandlerAuth;
use Ampache\Config\Init\InitializationHandlerConfig;
use Ampache\Config\Init\InitializationHandlerDatabaseUpdate;
use Ampache\Config\Init\InitializationHandlerEnvironment;
use Ampache\Config\Init\InitializationHandlerGetText;
use Ampache\Config\Init\InitializationHandlerGlobals;
use Ampache\Module\Util\EnvironmentInterface;
use getID3;
use MusicBrainz\HttpAdapters\RequestsHttpAdapter;
use MusicBrainz\MusicBrainz;
use Narrowspark\HttpEmitter\AbstractSapiEmitter;
use Narrowspark\HttpEmitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use PhpTal\PHPTAL;
use PhpTal\PhpTalInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use SpotifyWebAPI\SpotifyWebAPI;

use function DI\autowire;
use function DI\factory;

/**
 * These list contains the crucial services for init as well as all external ones
 */
return [
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
    Psr17Factory::class => autowire(),
    ResponseFactoryInterface::class => autowire(Psr17Factory::class),
    StreamFactoryInterface::class => autowire(Psr17Factory::class),
    UriFactoryInterface::class => autowire(Psr17Factory::class),
    UploadedFileFactoryInterface::class => autowire(Psr17Factory::class),
    AbstractSapiEmitter::class => autowire(SapiEmitter::class),
    ServerRequestCreatorInterface::class => autowire(ServerRequestCreator::class),
    ServerRequestFactoryInterface::class => autowire(Psr17Factory::class),
    PhpTalInterface::class => autowire(PHPTAL::class),
    SapiEmitter::class => autowire(SapiEmitter::class),
];
