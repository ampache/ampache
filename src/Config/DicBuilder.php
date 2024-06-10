<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use DI\ContainerBuilder;

$builder = new ContainerBuilder();
$builder->addDefinitions(
    require_once __DIR__ . '/service_definition.php',
    require_once __DIR__ . '/../Application/service_definition.php',
    require_once __DIR__ . '/../Module/Util/service_definition.php',
    require_once __DIR__ . '/../Module/WebDav/service_definition.php',
    require_once __DIR__ . '/../Module/Authentication/service_definition.php',
    require_once __DIR__ . '/../Module/Cache/service_definition.php',
    require_once __DIR__ . '/../Module/Song/service_definition.php',
    require_once __DIR__ . '/../Module/Playlist/service_definition.php',
    require_once __DIR__ . '/../Module/Album/service_definition.php',
    require_once __DIR__ . '/../Module/Art/service_definition.php',
    require_once __DIR__ . '/../Module/Broadcast/service_definition.php',
    require_once __DIR__ . '/../Module/Database/service_definition.php',
    require_once __DIR__ . '/../Module/Catalog/service_definition.php',
    require_once __DIR__ . '/../Module/LastFm/service_definition.php',
    require_once __DIR__ . '/../Module/System/service_definition.php',
    require_once __DIR__ . '/../Module/User/service_definition.php',
    require_once __DIR__ . '/../Module/Api/service_definition.php',
    require_once __DIR__ . '/../Gui/service_definition.php',
    require_once __DIR__ . '/../Module/Application/service_definition.php',
    require_once __DIR__ . '/../Module/Authorization/service_definition.php',
    require_once __DIR__ . '/../Module/License/service_definition.php',
    require_once __DIR__ . '/../Repository/service_definition.php',
    require_once __DIR__ . '/../Module/Label/service_definition.php',
    require_once __DIR__ . '/../Module/Artist/service_definition.php',
    require_once __DIR__ . '/../Module/Wanted/service_definition.php',
    require_once __DIR__ . '/../Module/Share/service_definition.php',
    require_once __DIR__ . '/../Module/Shout/service_definition.php',
    require_once __DIR__ . '/../Module/Podcast/service_definition.php',
    require_once __DIR__ . '/../Module/Metadata/service_definition.php',
);

return $builder->build();
