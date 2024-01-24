<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Gui\Playlist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\Rating;
use Mockery\MockInterface;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Util\ZipHandlerInterface;

class PlaylistViewAdapterTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var ZipHandlerInterface|MockInterface|null */
    private MockInterface $zipHandler;

    /** @var FunctionCheckerInterface|MockInterface|null */
    private MockInterface $functionChecker;

    /** @var GuiGatekeeperInterface|MockInterface|null */
    private ?MockInterface $gatekeeper;

    /** @var Playlist|MockInterface|null */
    private MockInterface $playlist;

    private PlaylistViewAdapter $subject;

    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->zipHandler      = $this->mock(ZipHandlerInterface::class);
        $this->functionChecker = $this->mock(FunctionCheckerInterface::class);
        $this->gatekeeper      = $this->mock(GuiGatekeeperInterface::class);
        $this->playlist        = $this->mock(Playlist::class);

        $this->subject = new PlaylistViewAdapter(
            $this->configContainer,
            $this->modelFactory,
            $this->zipHandler,
            $this->functionChecker,
            $this->gatekeeper,
            $this->playlist,
        );
    }

    public function testGetIdReturnsPlaylistId(): void
    {
        $AlbumId = 666;

        $this->playlist->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($AlbumId);

        $this->assertSame(
            $AlbumId,
            $this->subject->getId()
        );
    }

    public function testGetAverageRatingReturnsValue(): void
    {
        $playlistId    = 666;
        $averageRating = '7.89';

        $rating = $this->mock(Rating::class);

        $this->playlist->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($playlistId);

        $this->modelFactory->shouldReceive('createRating')
            ->with($playlistId, 'playlist')
            ->once()
            ->andReturn($rating);

        $rating->shouldReceive('get_average_rating')
            ->withNoArgs()
            ->once()
            ->andReturn($averageRating);

        $this->assertSame(
            $averageRating,
            $this->subject->getAverageRating()
        );
    }

    public function testGetEditButtonTitleReturnsValue(): void
    {
        $this->assertSame(
            'Playlist Edit',
            $this->subject->getEditButtonTitle()
        );
    }

    public function testGetBatchDownloadUrlReturnsValue(): void
    {
        $playlistId = 666;
        $webPath    = 'some-path';

        $this->playlist->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($playlistId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/batch.php?action=playlist&id=%d',
                $webPath,
                $playlistId
            ),
            $this->subject->getBatchDownloadUrl()
        );
    }

    public function testGetDeletionUrlReturnsValue(): void
    {
        $playlistId = 666;

        $this->playlist->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($playlistId);

        $this->assertSame(
            sprintf(
                '?page=browse&action=delete_object&type=playlist&id=%d',
                $playlistId
            ),
            $this->subject->getDeletionUrl()
        );
    }

    public function testCanBeRefreshedReturnsTrueIfConditionsAreMet(): void
    {
        $searchId = 1;
        $userId   = 1;

        $this->playlist->shouldReceive('has_access')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->playlist->user = $userId;

        $this->playlist->shouldReceive('has_search')
            ->with($this->playlist->user)
            ->once()
            ->andReturn($searchId);

        $this->assertGreaterThan(
            0,
            $searchId
        );

        $this->assertTrue(
            $this->subject->canBeRefreshed()
        );
    }

    public function testCanBeRefreshedReturnsFalseIfNotAccessible(): void
    {
        $searchId = 1;
        $userId   = 1;

        $this->playlist->shouldReceive('has_access')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->playlist->user = $userId;

        $this->playlist->shouldReceive('has_search')
            ->with($this->playlist->user)
            ->once()
            ->andReturn($searchId);

        $this->assertGreaterThan(
            0,
            $searchId
        );

        $this->assertFalse(
            $this->subject->canBeRefreshed()
        );
    }

    public function testCanBeRefreshedReturnsFalseIfHasNoSearch(): void
    {
        $searchId = 0;
        $userId   = 1;

        $this->playlist->shouldReceive('has_access')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->playlist->user = $userId;

        $this->playlist->shouldReceive('has_search')
            ->with($this->playlist->user)
            ->once()
            ->andReturn($searchId);

        $this->assertEquals(
            0,
            $searchId
        );

        $this->assertFalse(
            $this->subject->canBeRefreshed()
        );
    }

    public function testGetRefreshUrlReturnsValue(): void
    {
        $playlistId = 666;
        $webPath    = 'some-path';
        $userId     = 1;
        $searchId   = 333;

        $this->playlist->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($playlistId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->playlist->user = $userId;

        $this->playlist->shouldReceive('has_search')
            ->with($userId)
            ->once()
            ->andReturn($searchId);

        $this->assertSame(
            sprintf(
                '%s/playlist.php?action=refresh_playlist&type=playlist&user_id=%d&playlist_id=%d&search_id=%d',
                $webPath,
                $userId,
                $playlistId,
                $searchId
            ),
            $this->subject->getRefreshUrl()
        );
    }

    public function testGetPlaylistUrlReturnsValue(): void
    {
        $value = 'some-url';

        $this->playlist->link = $value;

        $this->playlist->shouldReceive('get_link')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getPlaylistUrl()
        );
    }

    public function testGetPlaylistLinkReturnsValue(): void
    {
        $value = 'some-album-link';

        $this->playlist->shouldReceive('get_f_link')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getPlaylistLink()
        );
    }

    public function testCanShareReturnsFalseIfNotAccessible(): void
    {
        $this->gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnFalse();

        $this->assertFalse(
            $this->subject->canShare()
        );
    }

    public function testCanShareReturnsFalseIfFeatureIsDeactivated(): void
    {
        $this->gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnFalse();

        $this->assertFalse(
            $this->subject->canShare()
        );
    }

    public function testCanShareReturnsTrueIfConditionsAreMet(): void
    {
        $this->gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->canShare()
        );
    }

    public function testCanBatchDownloadReturnsValue(): void
    {
        $this->functionChecker->shouldReceive('check')
            ->with(AccessLevelEnum::FUNCTION_BATCH_DOWNLOAD)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD)
            ->once()
            ->andReturnTrue();

        $this->zipHandler->shouldReceive('isZipable')
            ->with('playlist')
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->canBatchDownload()
        );
    }
}
