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

namespace Ampache\Gui;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Album\AlbumDiskSectionView;
use Ampache\Gui\Album\AlbumPageView;
use Ampache\Gui\Artist\ArtistPageView;
use Ampache\MockeryTestCase;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;

/**
 * Direct play only streams; adding to the temporary playlist is what stores per-user state.
 *
 * The two were coupled, so an instance serving anonymous visitors lost the Play action on the album,
 * album disk and artist pages while browse rows kept it (CollectionRowView carries no access check).
 * These cases pin the two apart: showDirectPlay ignores $mayUse, showAdd still honours it, and the
 * direct_play_limit guard keeps working for both.
 */
class GuestDirectPlayTest extends MockeryTestCase
{
    public function testShowAddStillRequiresAccessOnEveryView(): void
    {
        AmpConfig::set('directplay', true, true);
        AmpConfig::set('direct_play_limit', 0, true);

        self::assertFalse($this->albumView(false, 3)->showAdd());
        self::assertFalse($this->diskView(false, 3)->showAdd());
        self::assertFalse($this->artistView(false, 3)->showAdd());
    }

    public function testShowDirectPlayHonoursDirectPlayLimitForGuests(): void
    {
        AmpConfig::set('directplay', true, true);
        AmpConfig::set('direct_play_limit', 2, true);

        self::assertFalse($this->albumView(false, 3)->showDirectPlay());
        self::assertFalse($this->diskView(false, 3)->showDirectPlay());
        self::assertFalse($this->artistView(false, 3)->showDirectPlay());
    }

    public function testShowDirectPlayIsFalseWhenDirectPlayIsDisabled(): void
    {
        AmpConfig::set('directplay', false, true);
        AmpConfig::set('direct_play_limit', 0, true);

        self::assertFalse($this->albumView(true, 3)->showDirectPlay());
        self::assertFalse($this->diskView(true, 3)->showDirectPlay());
        self::assertFalse($this->artistView(true, 3)->showDirectPlay());
    }

    public function testShowDirectPlayIsOfferedToGuests(): void
    {
        AmpConfig::set('directplay', true, true);
        AmpConfig::set('direct_play_limit', 0, true);

        self::assertTrue($this->albumView(false, 3)->showDirectPlay());
        self::assertTrue($this->diskView(false, 3)->showDirectPlay());
        self::assertTrue($this->artistView(false, 3)->showDirectPlay());
    }

    private function albumView(bool $mayUse, int $songCount): AlbumPageView
    {
        $album             = $this->mock(Album::class);
        $album->song_count = $songCount;

        return new AlbumPageView(
            $album,
            $this->mock(BrowseFactoryInterface::class),
            null,
            '',
            false,
            false,
            $mayUse,
            false
        );
    }

    private function artistView(bool $mayUse, int $songCount): ArtistPageView
    {
        $artist             = $this->mock(Artist::class);
        $artist->song_count = $songCount;

        return new ArtistPageView(
            $artist,
            [],
            'artist',
            $this->mock(BrowseFactoryInterface::class),
            null,
            '',
            false,
            false,
            $mayUse,
            false
        );
    }

    private function diskView(bool $mayUse, int $songCount): AlbumDiskSectionView
    {
        $disk             = $this->mock(AlbumDisk::class);
        $disk->song_count = $songCount;

        return new AlbumDiskSectionView(
            $disk,
            $this->mock(BrowseFactoryInterface::class),
            '',
            [],
            false,
            false,
            $mayUse
        );
    }
}
