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

namespace Ampache\Gui\Sidebar;

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\MoodRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;

class HomeSidebarViewTest extends MockeryTestCase
{
    /** @var array<string, mixed> */
    private array $config = [];

    /**
     * the search sidebar mirrors the browse sidebar's folder entry: same gate, same position above `song`
     */
    public function testFolderSearchAppearsBeforeSongWhenFoldersExist(): void
    {
        AmpConfig::set('show_folder', true, true);

        $items = $this->subject(folderCount: 3)->getSearchItems();

        $this->assertSame('sb_home_search_folder', $items[0]['id']);
        $this->assertSame('/search.php?type=folder', $items[0]['url']);
        $this->assertSame('Folders', $items[0]['label']);
        $this->assertSame('sb_home_search_song', $items[1]['id']);
    }

    /**
     * no catalog has been scanned into folders yet, so the link would only ever answer an empty search
     */
    public function testFolderSearchIsHiddenWhenNoFoldersExist(): void
    {
        AmpConfig::set('show_folder', true, true);

        $items = $this->subject(folderCount: 0)->getSearchItems();

        $this->assertSame('sb_home_search_song', $items[0]['id']);
    }

    public function testFolderSearchIsHiddenWhenThePreferenceIsOff(): void
    {
        AmpConfig::set('show_folder', false, true);

        $items = $this->subject(folderCount: 3)->getSearchItems();

        $this->assertSame('sb_home_search_song', $items[0]['id']);
    }

    protected function setUp(): void
    {
        $this->config['show_folder'] = AmpConfig::get('show_folder');
    }

    protected function tearDown(): void
    {
        foreach ($this->config as $key => $value) {
            AmpConfig::set($key, $value, true);
        }
    }

    private function subject(int $folderCount): HomeSidebarView
    {
        $videoRepository = $this->mock(VideoRepositoryInterface::class);
        $videoRepository->shouldReceive('getItemCount')->andReturn(0);

        $folderRepository = $this->mock(FolderRepositoryInterface::class);
        $folderRepository->shouldReceive('getItemCount')->andReturn($folderCount);

        $moodRepository = $this->mock(MoodRepositoryInterface::class);

        return new HomeSidebarView(
            '',
            'album',
            $videoRepository,
            $folderRepository,
            $moodRepository,
            false,
            false,
            false,
            false,
        );
    }
}
