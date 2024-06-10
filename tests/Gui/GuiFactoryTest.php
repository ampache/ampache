<?php

declare(strict_types=1);

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

namespace Ampache\Gui;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Album\AlbumViewAdapter;
use Ampache\Gui\Catalog\CatalogDetails;
use Ampache\Gui\Playlist\NewPlaylistDialogAdapter;
use Ampache\Gui\Playlist\PlaylistViewAdapter;
use Ampache\Gui\Song\SongViewAdapter;
use Ampache\Gui\Stats\CatalogStats;
use Ampache\Gui\Stats\StatsViewAdapter;
use Ampache\Gui\System\ConfigViewAdapter;
use Ampache\Gui\System\UpdateViewAdapter;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\System\Update\UpdateHelperInterface;
use Ampache\Module\System\Update\UpdaterInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use PHPUnit\Framework\TestCase;

class GuiFactoryTest extends TestCase
{
    private GuiFactory $subject;

    protected function setUp(): void
    {

        $this->subject = new GuiFactory(
            $this->createMock(ConfigContainerInterface::class),
            $this->createMock(ModelFactoryInterface::class),
            $this->createMock(ZipHandlerInterface::class),
            $this->createMock(FunctionCheckerInterface::class),
            $this->createMock(AjaxUriRetrieverInterface::class),
            $this->createMock(PlaylistLoaderInterface::class),
            $this->createMock(VideoRepositoryInterface::class),
            $this->createMock(UpdateInfoRepositoryInterface::class),
            $this->createMock(UpdateHelperInterface::class),
            $this->createMock(UpdaterInterface::class)
        );
    }

    public function testCreateSongViewAdapterReturnsInstance(): void
    {
        $this->assertInstanceOf(
            SongViewAdapter::class,
            $this->subject->createSongViewAdapter(
                $this->createMock(GuiGatekeeperInterface::class),
                $this->createMock(Song::class)
            )
        );
    }

    public function testCreateAlbumViewAdapterReturnsInstance(): void
    {
        $this->assertInstanceOf(
            AlbumViewAdapter::class,
            $this->subject->createAlbumViewAdapter(
                $this->createMock(GuiGatekeeperInterface::class),
                $this->createMock(Browse::class),
                $this->createMock(Album::class)
            )
        );
    }

    public function testCreatePlaylistViewAdapterReturnsInstance(): void
    {
        $this->assertInstanceOf(
            PlaylistViewAdapter::class,
            $this->subject->createPlaylistViewAdapter(
                $this->createMock(GuiGatekeeperInterface::class),
                $this->createMock(Playlist::class)
            )
        );
    }

    public function testCreateConfigViewAdapterReturnsInstance(): void
    {
        $this->assertInstanceOf(
            ConfigViewAdapter::class,
            $this->subject->createConfigViewAdapter()
        );
    }

    public function testCreateStatsViewAdapterReturnsInstance(): void
    {
        $this->assertInstanceOf(
            StatsViewAdapter::class,
            $this->subject->createStatsViewAdapter()
        );
    }

    public function testCreateCatalogDetailsReturnsInstance(): void
    {
        $this->assertInstanceOf(
            CatalogDetails::class,
            $this->subject->createCatalogDetails(
                $this->createMock(Catalog::class)
            )
        );
    }

    public function testCreateCatalogStatsReturnsInstance(): void
    {
        $this->assertInstanceOf(
            CatalogStats::class,
            $this->subject->createCatalogStats([])
        );
    }

    public function testCreateUpdateViewAdapterReturnsInstance(): void
    {
        $this->assertInstanceOf(
            UpdateViewAdapter::class,
            $this->subject->createUpdateViewAdapter()
        );
    }

    public function testCreateNewPlaylistDialogAdapterReturnsInstance(): void
    {
        $this->assertInstanceOf(
            NewPlaylistDialogAdapter::class,
            $this->subject->createNewPlaylistDialogAdapter(
                $this->createMock(GuiGatekeeperInterface::class),
                'some-type',
                '666'
            )
        );
    }
}
