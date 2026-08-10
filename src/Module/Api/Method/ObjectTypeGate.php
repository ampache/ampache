<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;

/**
 * The `index` and `list` methods accept the same object types and apply the same config gates to
 * them, so both the type list and the gate live here.
 */
final class ObjectTypeGate
{
    /** @var string[] */
    public const array INDEX_TYPES = [
        'album_artist',
        'album',
        'artist',
        'catalog',
        'live_stream',
        'playlist',
        'podcast_episode',
        'podcast',
        'share',
        'song_artist',
        'song',
        'video',
    ];

    /**
     * Types api version 8 can index on top of the shared list
     *
     * Older versions have no formatter for these, so they must never reach `INDEX_TYPES` itself --
     * that list is also read by `GetIndexes6Method`.
     *
     * @var string[]
     */
    public const array INDEX_TYPES_8 = [
        ...self::INDEX_TYPES,
        'album_disk',
    ];

    /**
     * Returns the error message of the config gate blocking this type, or null when it is allowed
     */
    public static function check(ConfigContainerInterface $configContainer, string $type): ?string
    {
        if ($type === 'video' && !$configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)) {
            return 'Enable: video';
        }

        if (
            ($type === 'podcast' || $type === 'podcast_episode')
            && !$configContainer->get(ConfigurationKeyEnum::PODCAST)
        ) {
            return 'Enable: podcast';
        }

        if ($type === 'share' && !$configContainer->get(ConfigurationKeyEnum::SHARE)) {
            return 'Enable: share';
        }

        if ($type === 'live_stream' && !$configContainer->get(ConfigurationKeyEnum::RADIO)) {
            return 'Enable: live_stream';
        }

        return null;
    }

    /**
     * The indexable types for a given api version
     *
     * @return string[]
     */
    public static function indexTypes(int $apiVersion): array
    {
        return ($apiVersion >= 8)
            ? self::INDEX_TYPES_8
            : self::INDEX_TYPES;
    }
}
