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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

/**
 * Encodes/decodes Jellyfin-facing item ids as deterministic, RFC 4122-shaped UUIDs — a pure function (no
 * storage) packing a type ordinal and the Ampache row id into the bytes a real UUID parser expects.
 */
final class JellyfinId
{
    /**
     * Byte indexes (of the 16-byte buffer) that carry the Ampache id, in order. Bytes 6 and 8 are excluded —
     * they hold the fixed RFC 4122 version/variant nibbles instead, so payload never touches them.
     *
     * @var list<int>
     */
    private const array ID_BYTE_INDEXES = [2, 3, 4, 5, 7, 9, 10, 11, 12, 13, 14, 15];
    private const int MAGIC             = 0xAF;

    /**
     * Ampache type string -> [ordinal, debug prefix]. The prefix is never sent on the wire — it exists only
     * for toDebugString() and to keep the same mental model as Subsonic_Api's SUBID_* scheme.
     *
     * @var array<string, array{ordinal: int, prefix: string}>
     */
    private const array TYPES = [
        'song' => ['ordinal' => 0,  'prefix' => 'so-'],
        'album' => ['ordinal' => 1,  'prefix' => 'al-'],
        'album_disk' => ['ordinal' => 2,  'prefix' => 'ad-'],   // reserved, not emitted in v1
        'artist' => ['ordinal' => 3,  'prefix' => 'ar-'],
        'catalog' => ['ordinal' => 4,  'prefix' => 'ca-'],
        'folder' => ['ordinal' => 5,  'prefix' => 'fo-'],
        'genre' => ['ordinal' => 6,  'prefix' => 'ta-'],   // Ampache tag
        'live_stream' => ['ordinal' => 7,  'prefix' => 'li-'],
        'playlist' => ['ordinal' => 8,  'prefix' => 'pl-'],
        'podcast' => ['ordinal' => 9,  'prefix' => 'po-'],   // reserved, not emitted in v1
        'podcast_episode' => ['ordinal' => 10, 'prefix' => 'pe-'],   // reserved, not emitted in v1
        'search' => ['ordinal' => 11, 'prefix' => 'sp-'],   // Ampache 'search' (smartlist)
        'user' => ['ordinal' => 12, 'prefix' => 'us-'],
        'video' => ['ordinal' => 13, 'prefix' => 'vi-'],   // reserved, never emitted
        'view' => ['ordinal' => 14, 'prefix' => 'vw-'],   // synthetic UserView root
        'label' => ['ordinal' => 15, 'prefix' => 'lb-'],
        'year' => ['ordinal' => 16, 'prefix' => 'yr-'],   // synthetic
    ];
    private const int VARIANT_BYTE  = 8;
    private const int VARIANT_VALUE = 0x80;

    private const int VERSION_BYTE  = 6;
    private const int VERSION_VALUE = 0x40;

    public static function decodeId(string $jfId): ?int
    {
        $bytes = self::parseBytes($jfId);
        if ($bytes === null) {
            return null;
        }

        $id = 0;
        foreach (self::ID_BYTE_INDEXES as $byteIndex) {
            $id = ($id << 8) | $bytes[$byteIndex];
        }

        return $id;
    }

    public static function decodeObject(string $jfId): ?object
    {
        $type = self::decodeType($jfId);
        $id   = self::decodeId($jfId);
        if ($type === null || $id === null) {
            return null;
        }

        return match ($type) {
            'song' => new Song($id),
            'album' => new Album($id),
            'album_disk' => new AlbumDisk($id),
            'artist' => new Artist($id),
            'catalog' => Catalog::create_from_id($id),
            'folder' => new Folder($id),
            'genre' => new Tag($id),
            'live_stream' => new Live_Stream($id),
            'playlist' => new Playlist($id),
            'podcast' => new Podcast($id),
            'podcast_episode' => new Podcast_Episode($id),
            'search' => new Smartlist($id),
            'user' => new User($id),
            'video' => new Video($id),
            'label' => new Label($id),
            default => null,
        };
    }

    public static function decodeType(string $jfId): ?string
    {
        $bytes = self::parseBytes($jfId);
        if ($bytes === null) {
            return null;
        }

        $ordinal = $bytes[1];
        foreach (self::TYPES as $type => $meta) {
            if ($meta['ordinal'] === $ordinal) {
                return $type;
            }
        }

        return null;
    }

    public static function encode(string $ampacheType, int|string $ampacheId): ?string
    {
        if (!isset(self::TYPES[$ampacheType])) {
            return null;
        }

        $id = (int) $ampacheId;
        if ($id < 0) {
            return null;
        }

        $bytes    = array_fill(0, 16, 0);
        $bytes[0] = self::MAGIC;
        $bytes[1] = self::TYPES[$ampacheType]['ordinal'];

        $idBytes = array_fill(0, count(self::ID_BYTE_INDEXES), 0);
        for ($i = count($idBytes) - 1; $i >= 0 && $id > 0; $i--) {
            $idBytes[$i] = $id & 0xFF;
            $id >>= 8;
        }
        foreach (self::ID_BYTE_INDEXES as $position => $byteIndex) {
            $bytes[$byteIndex] = $idBytes[$position];
        }

        $bytes[self::VERSION_BYTE] = self::VERSION_VALUE;
        $bytes[self::VARIANT_BYTE] = self::VARIANT_VALUE;

        return self::formatBytes($bytes);
    }

    public static function isType(string $jfId, string $ampacheType): bool
    {
        return self::decodeType($jfId) === $ampacheType;
    }

    /** Decodes back to a Subsonic-style debug label (e.g. 'so-1234') for log lines — never sent on the wire. */
    public static function toDebugString(string $jfId): string
    {
        $type = self::decodeType($jfId);
        $id   = self::decodeId($jfId);
        if ($type === null || $id === null) {
            return $jfId;
        }

        return self::TYPES[$type]['prefix'] . $id;
    }

    /** @param list<int> $bytes */
    private static function formatBytes(array $bytes): string
    {
        $hex = '';
        foreach ($bytes as $byte) {
            $hex .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
        }

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    /** @return list<int>|null */
    private static function parseBytes(string $jfId): ?array
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $jfId) !== 1) {
            return null;
        }

        $hex   = str_replace('-', '', strtolower($jfId));
        $bytes = array_map(static fn(string $pair): int => (int) hexdec($pair), str_split($hex, 2));

        if (($bytes[0] ?? null) !== self::MAGIC) {
            return null;
        }
        if (($bytes[self::VERSION_BYTE] ?? null) !== self::VERSION_VALUE) {
            return null;
        }
        if (($bytes[self::VARIANT_BYTE] ?? null) !== self::VARIANT_VALUE) {
            return null;
        }

        return $bytes;
    }
}
