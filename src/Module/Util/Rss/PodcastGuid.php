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

namespace Ampache\Module\Util\Rss;

/**
 * podcast:guid generator
 * https://podcasting2.org/docs/podcast-namespace/tags/guid
 * UUIDv5 in the podcastindex namespace, name = protocol-less feed url
 */
final class PodcastGuid
{
    /** Namespace UUID defined by the podcast namespace spec */
    private const string PODCAST_NAMESPACE = 'ead4c236-bf58-58c6-a2c6-a6b28d128cb6';

    public static function fromFeedUrl(string $feedUrl): string
    {
        $name = rtrim((string) preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $feedUrl), '/');

        return self::_uuidV5(self::PODCAST_NAMESPACE, $name);
    }

    private static function _uuidV5(string $namespace, string $name): string
    {
        $nsBinary = (string) hex2bin(str_replace('-', '', $namespace));

        $hash = sha1($nsBinary . $name);

        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            substr($hash, 20, 12)
        );
    }
}
