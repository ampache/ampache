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

namespace Ampache\Module\Art\Collector;

final class ArtCollectorTypeEnum
{
    public const GOOGLE      = 'google';
    public const MUSICBRAINZ = 'musicbrainz';
    public const LASTFM      = 'lastfm';
    public const SPOTIFY     = 'spotify';
    public const DB          = 'db';
    public const FOLDER      = 'folder';
    public const META_TAGS   = 'tags';

    /** @var array<string, class-string<CollectorModuleInterface>> */
    public const TYPE_CLASS_MAP = [
        self::GOOGLE => GoogleCollectorModule::class,
        self::MUSICBRAINZ => MusicbrainzCollectorModule::class,
        self::LASTFM => LastFmCollectorModule::class,
        self::SPOTIFY => SpotifyCollectorModule::class,
        self::DB => DbCollectorModule::class,
        self::FOLDER => FolderCollectorModule::class,
        self::META_TAGS => MetaTagCollectorModule::class,
    ];
}
