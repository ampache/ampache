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

namespace Ampache\Module\Playlist\Folder;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Mockery\MockInterface;

class PlaylistFolderRowIdsTest extends MockeryTestCase
{
    private PlaylistFolderItemsLoaderInterface&MockInterface $itemsLoader;
    private PlaylistFolderRepositoryInterface&MockInterface $playlistFolderRepository;
    private PlaylistFolderRowIds $subject;

    public function testGetRowIdsInterleavesSubfoldersAndItemsBySortOrder(): void
    {
        $user      = $this->mock(User::class);
        $subfolder = PlaylistFolder::fromRow(['id' => 3, 'user' => 9, 'parent' => 0, 'name' => 'Live', 'sort_order' => 2]);

        $this->playlistFolderRepository->shouldReceive('getChildren')->with($user, 0)->once()->andReturn([$subfolder]);
        $this->itemsLoader->shouldReceive('getItems')->with($user, null)->once()->andReturn([
            ['object_id' => 10, 'object_type' => 'playlist', 'sort_order' => 1],
            ['object_id' => 20, 'object_type' => 'search', 'sort_order' => 3],
        ]);

        // A dragged playlist (sort_order 1) now sits above the folder (2), which sits above the search (3) --
        // proof the two sources interleave by their shared `sort_order` rather than rendering folders as a block
        self::assertSame(
            ['playlist-10', 'playlist_folder-3', 'search-20'],
            $this->subject->getRowIds($user, null)
        );
    }

    public function testGetRowIdsKeepsEachSourcesOwnOrderWhenSortOrderTies(): void
    {
        $user       = $this->mock(User::class);
        $subfolder1 = PlaylistFolder::fromRow(['id' => 3, 'user' => 9, 'parent' => 0, 'name' => 'Live', 'sort_order' => 0]);
        $subfolder2 = PlaylistFolder::fromRow(['id' => 7, 'user' => 9, 'parent' => 0, 'name' => 'Rock', 'sort_order' => 0]);

        $this->playlistFolderRepository->shouldReceive('getChildren')->with($user, 0)->once()->andReturn([$subfolder1, $subfolder2]);
        $this->itemsLoader->shouldReceive('getItems')->with($user, null)->once()->andReturn([
            ['object_id' => 10, 'object_type' => 'playlist', 'sort_order' => 0],
            ['object_id' => 20, 'object_type' => 'search', 'sort_order' => 0],
        ]);

        // Never explicitly dragged, so every row still defaults to `sort_order` 0 -- a stable sort keeps
        // subfolders (already ordered by `getChildren()`) ahead of items (already ordered by `getItems()`)
        self::assertSame(
            ['playlist_folder-3', 'playlist_folder-7', 'playlist-10', 'search-20'],
            $this->subject->getRowIds($user, null)
        );
    }

    public function testGetRowIdsSkipsCollections(): void
    {
        $user = $this->mock(User::class);

        $this->playlistFolderRepository->shouldReceive('getChildren')->with($user, 0)->once()->andReturn([]);
        $this->itemsLoader->shouldReceive('getItems')->with($user, null)->once()->andReturn([
            ['object_id' => 4, 'object_type' => 'collection', 'sort_order' => 0],
            ['object_id' => 10, 'object_type' => 'playlist', 'sort_order' => 1],
        ]);

        self::assertSame(['playlist-10'], $this->subject->getRowIds($user, null));
    }

    protected function setUp(): void
    {
        $this->itemsLoader              = $this->mock(PlaylistFolderItemsLoaderInterface::class);
        $this->playlistFolderRepository = $this->mock(PlaylistFolderRepositoryInterface::class);

        $this->subject = new PlaylistFolderRowIds(
            $this->itemsLoader,
            $this->playlistFolderRepository,
        );
    }
}
