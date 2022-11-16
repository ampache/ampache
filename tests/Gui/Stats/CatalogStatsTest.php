<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Gui\Stats;

use Ampache\MockeryTestCase;

class CatalogStatsTest extends MockeryTestCase
{
    /**
     * @dataProvider methodDataProvider
     */
    public function testArrayAccessorsReturnData(
        string $methodName,
        string $arrayKey,
        $testValue,
        $defaultValue
    ): void {
        $subject = new CatalogStats([$arrayKey => $testValue]);

        $this->assertSame(
            $testValue,
            call_user_func_array([$subject, $methodName], [])
        );

        // also test the default value
        $subject = new CatalogStats([]);

        $this->assertSame(
            $defaultValue,
            call_user_func_array([$subject, $methodName], [])
        );
    }

    public function methodDataProvider(): array
    {
        return [
            ['getConnectedCount', 'connected', 666, 0],
            ['getUserCount', 'user', 666, 0],
            ['getAlbumCount', 'album', 666, 0],
            ['getAlbumDiskCount', 'album_disk', 666, 0],
            ['getArtistCount', 'artist', 666, 0],
            ['getSongCount', 'song', 666, 0],
            ['getPodcastCount', 'podcast', 666, 0],
            ['getPodcastEpisodeCount', 'podcast_episode', 666, 0],
            ['getGenreCount', 'tags', 666, 0],
            ['getCatalogSize', 'formatted_size', 'some-size', ''],
            ['getPlayTime', 'time_text', 'some-time', ''],
            ['getItemCount', 'items', 666, 0],
            ['getVideoCount', 'video', 666, 0],
        ];
    }
}
