<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=1);

namespace Ampache\Gui\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Module\Application\Album\DeleteAction;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\Rating;
use Mockery\MockInterface;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Util\ZipHandlerInterface;

class AlbumViewAdapterTest extends MockeryTestCase
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

    /** @var Browse|MockInterface|null */
    private MockInterface $browse;

    /** @var Album|MockInterface|null */
    private MockInterface $album;

    private AlbumViewAdapter $subject;

    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->zipHandler      = $this->mock(ZipHandlerInterface::class);
        $this->functionChecker = $this->mock(FunctionCheckerInterface::class);
        $this->gatekeeper      = $this->mock(GuiGatekeeperInterface::class);
        $this->browse          = $this->mock(Browse::class);
        $this->album           = $this->mock(Album::class);

        $this->subject = new AlbumViewAdapter(
            $this->configContainer,
            $this->modelFactory,
            $this->zipHandler,
            $this->functionChecker,
            $this->gatekeeper,
            $this->browse,
            $this->album,
        );
    }

    public function testGetIdReturnsAlbumId(): void
    {
        $AlbumId = 666;

        $this->album->shouldReceive('getId')
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
        $albumId       = 666;
        $averageRating = '7.89';

        $rating = $this->mock(Rating::class);

        $this->album->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($albumId);

        $this->modelFactory->shouldReceive('createRating')
            ->with($albumId, 'album')
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
            'Album Edit',
            $this->subject->getEditButtonTitle()
        );
    }

    public function testGetPostShoutUrlReturnsValue(): void
    {
        $albumId = 666;
        $webPath = 'some-path';

        $this->album->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($albumId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/shout.php?action=show_add_shout&type=album&id=%d',
                $webPath,
                $albumId
            ),
            $this->subject->getPostShoutUrl()
        );
    }

    public function testGetBatchDownloadUrlReturnsValue(): void
    {
        $albumId = 666;
        $webPath = 'some-path';

        $this->album->id = $albumId;

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/batch.php?action=album&id=%s',
                $webPath,
                $this->album->id
            ),
            $this->subject->getBatchDownloadUrl()
        );
    }

    public function testGetDeletionUrlReturnsValue(): void
    {
        $albumId = 666;
        $webPath = 'some-path';

        $this->album->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($albumId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/albums.php?action=%s&album_id=%d',
                $webPath,
                DeleteAction::REQUEST_KEY,
                $albumId
            ),
            $this->subject->getDeletionUrl()
        );
    }

    public function testGetPlayedTimesReturnsValue(): void
    {
        $value = 666;

        $this->album->total_count = $value;

        $this->assertEquals(
            $value,
            $this->subject->getPlayedTimes()
        );
    }

    public function testGetAlbumUrlReturnsValue(): void
    {
        $value = 'some-url';

        $this->album->link = $value;

        $this->album->shouldReceive('get_link')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getAlbumUrl()
        );
    }

    public function testGetArtistLinkReturnsValue(): void
    {
        $value = 'some-artist-link';

        $this->album->f_artist_link = $value;

        $this->album->shouldReceive('get_f_artist_link')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getArtistLink()
        );
    }

    public function testGetAlbumLinkReturnsValue(): void
    {
        $value = 'some-album-link';

        $this->album->shouldReceive('get_f_link')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getAlbumLink()
        );
    }

    public function testGetYearIfUseOriginalYearIsDeactivated(): void
    {
        $year         = 666;
        $originalYear = 555;

        $this->album->year          = $year;
        $this->album->original_year = $originalYear;

        $this->configContainer->shouldReceive('get')
            ->with('use_original_year')
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            $year,
            $this->subject->getDisplayYear()
        );
    }

    public function testGetYearIfUseOriginalYearIsActivated(): void
    {
        $year         = 666;
        $originalYear = 555;

        $this->album->year          = $year;
        $this->album->original_year = $originalYear;

        $this->configContainer->shouldReceive('get')
            ->with('use_original_year')
            ->once()
            ->andReturnTrue();

        $this->assertSame(
            $originalYear,
            $this->subject->getDisplayYear()
        );
    }

    public function testGetYearIfUseOriginalYearIsActivatedButMissing(): void
    {
        $year         = 666;
        $originalYear = null;

        $this->album->year          = $year;
        $this->album->original_year = $originalYear;

        $this->configContainer->shouldReceive('get')
            ->with('use_original_year')
            ->once()
            ->andReturnTrue();

        $this->assertSame(
            $year,
            $this->subject->getDisplayYear()
        );
    }

    public function testGenreReturnsValues(): void
    {
        $value = 'some-tags';

        $this->album->f_tags = $value;

        $this->assertSame(
            $value,
            $this->subject->getGenre()
        );
    }

    public function testCanPostShoutReturnsFalseIfSocialIsNotEnabled(): void
    {
        $this->configContainer->shouldReceive('isAuthenticationEnabled')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnFalse();

        $this->assertFalse(
            $this->subject->canPostShout()
        );
    }

    public function testCanPostShoutReturnsFalseIfNotAccessible(): void
    {
        $this->configContainer->shouldReceive('isAuthenticationEnabled')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnFalse();

        $this->assertFalse(
            $this->subject->canPostShout()
        );
    }

    public function testCanPostShoutReturnsFalseIfAllConditionsAreMet(): void
    {
        $this->configContainer->shouldReceive('isAuthenticationEnabled')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->canPostShout()
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
            ->with('album')
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->canBatchDownload()
        );
    }
}
