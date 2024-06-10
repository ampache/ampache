<?php

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

namespace Ampache\Module\Authorization;

/**
 * Contains all known access levels and access types
 */
final class AccessLevelEnum
{
    public const TYPE_INTERFACE = 'interface';
    public const TYPE_LOCALPLAY = 'localplay';
    public const TYPE_API       = 'rpc';
    public const TYPE_NETWORK   = 'network';
    public const TYPE_STREAM    = 'stream';

    public const CONFIGURABLE_TYPE_LIST = [
        self::TYPE_API,
        self::TYPE_INTERFACE,
        self::TYPE_NETWORK,
        self::TYPE_STREAM,
    ];

    public const LEVEL_DEFAULT         = 0;
    public const LEVEL_GUEST           = 5;
    public const LEVEL_USER            = 25;
    public const LEVEL_CONTENT_MANAGER = 50;
    public const LEVEL_MANAGER         = 75;
    public const LEVEL_ADMIN           = 100;

    public const FUNCTION_DOWNLOAD       = 'download';
    public const FUNCTION_BATCH_DOWNLOAD = 'batch_download';
}
