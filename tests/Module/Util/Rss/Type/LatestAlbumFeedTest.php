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

namespace Ampache\Module\Util\Rss\Type;

use Ampache\Repository\Model\Album;
use PHPUnit\Framework\TestCase;

class LatestAlbumFeedTest extends TestCase
{
    public function testArtSourcesDropsAnArtistlessAlbum(): void
    {
        $album     = $this->createMock(Album::class);
        $album->id = 11237;

        self::assertSame(
            [['album', 11237]],
            LatestAlbumFeed::artSources($album)
        );
    }

    public function testArtSourcesReadsTheArtistAfterTheAlbum(): void
    {
        $album               = $this->createMock(Album::class);
        $album->id           = 11237;
        $album->album_artist = 42;

        self::assertSame(
            [['album', 11237], ['artist', 42]],
            LatestAlbumFeed::artSources($album)
        );
    }
}
