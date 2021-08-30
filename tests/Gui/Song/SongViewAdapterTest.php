<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Gui\Song;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Module\Application\Song\DeleteAction;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Mockery\MockInterface;

class SongViewAdapterTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var MockInterface|GuiGatekeeperInterface|null */
    private ?MockInterface $gatekeeper;

    /** @var Song|MockInterface|null */
    private MockInterface $song;

    /** @var SongViewAdapter|null */
    private SongViewAdapter $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->gatekeeper      = $this->mock(GuiGatekeeperInterface::class);
        $this->song            = $this->mock(Song::class);

        $this->subject = new SongViewAdapter(
            $this->configContainer,
            $this->modelFactory,
            $this->gatekeeper,
            $this->song
        );
    }

    public function testGetIdReturnsSongId(): void
    {
        $id = 666;

        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($id);

        $this->assertSame(
            $id,
            $this->subject->getId()
        );
    }

    public function testGetWaveformUrlReturnsUrl(): void
    {
        $songId  = 666;
        $webPath = 'some-path';

        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($songId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/waveform.php?song_id=%d',
                $webPath,
                $songId
            ),
            $this->subject->getWaveformUrl()
        );
    }

    public function testGetDisplayStatsUrl(): void
    {
        $songId  = 666;
        $webPath = 'some-path';

        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($songId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/stats.php?action=graph&object_type=song&object_id=%d',
                $webPath,
                $songId
            ),
            $this->subject->getDisplayStatsUrl()
        );
    }

    public function testGetEditButtonTitleReturnsValue(): void
    {
        $this->assertSame(
            'Song Edit',
            $this->subject->getEditButtonTitle()
        );
    }

    public function testGetAverageRatingReturnsValue(): void
    {
        $songId        = 666;
        $averageRating = '7.89';

        $rating = $this->mock(Rating::class);

        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($songId);

        $this->modelFactory->shouldReceive('createRating')
            ->with($songId, 'song')
            ->once()
            ->andReturn($rating);

        $rating->shouldReceive('get_average_rating')
            ->withNoArgs()
            ->once()
            ->andReturn($averageRating);

        $this->assertSame(
            (string) $averageRating,
            $this->subject->getAverageRating()
        );
    }

    public function testGetPostShoutUrlReturnsValue(): void
    {
        $songId  = 666;
        $webPath = 'some-path';

        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($songId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/shout.php?action=show_add_shout&type=song&id=%d',
                $webPath,
                $songId
            ),
            $this->subject->getPostShoutUrl()
        );
    }

    public function testGetDownloadUrlReturnsValue(): void
    {
        $songId  = 666;
        $webPath = 'some-path';

        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($songId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/stream.php?action=download&song_id=%d',
                $webPath,
                $songId
            ),
            $this->subject->getDownloadUrl()
        );
    }

    public function testGetDeletionUrlReturnsValue(): void
    {
        $songId  = 666;
        $webPath = 'some-path';

        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($songId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/song.php?action=%s&song_id=%d',
                $webPath,
                DeleteAction::REQUEST_KEY,
                $songId
            ),
            $this->subject->getDeletionUrl()
        );
    }

    public function testGetTrackNumberReturnsTrack(): void
    {
        $trackNumber = '666';

        $this->song->f_track = $trackNumber;

        $this->assertSame(
            $trackNumber,
            $this->subject->getTrackNumber()
        );
    }

    public function testGetSongUrlReturnsValue(): void
    {
        $value = 'some-url';

        $this->song->link = $value;

        $this->assertSame(
            $value,
            $this->subject->getSongUrl()
        );
    }

    public function testGetSongLinkReturnsValus(): void
    {
        $value = 'some-link';

        $this->song->f_link = $value;

        $this->assertSame(
            $value,
            $this->subject->getSongLink()
        );
    }

    public function testGetArtistLinkReturnsValue(): void
    {
        $value = 'some-artist-link';

        $this->song->f_artist_link = $value;

        $this->assertSame(
            $value,
            $this->subject->getArtistLink()
        );
    }

    public function testGetAlbumLinkReturnsValue(): void
    {
        $value = 'some-album-link';

        $this->song->f_album_link = $value;

        $this->assertSame(
            $value,
            $this->subject->getAlbumLink()
        );
    }

    public function testGetYearReturnsValus(): void
    {
        $value = 666;

        $this->song->year = $value;

        $this->assertSame(
            $value,
            $this->subject->getYear()
        );
    }

    public function testGenreReturnsValus(): void
    {
        $value = 'some-tags';

        $this->song->f_tags = $value;

        $this->assertSame(
            $value,
            $this->subject->getGenre()
        );
    }

    public function testPlayDurationReturnsValue(): void
    {
        $value = 'some-duration';

        $this->song->f_time = $value;

        $this->assertSame(
            $value,
            $this->subject->getPlayDuration()
        );
    }

    public function testGetLicenseLinkReturnsValue(): void
    {
        $this->song->f_license = null;

        $this->assertSame(
            '',
            $this->subject->getLicenseLink()
        );
    }

    public function testGetNumberPlayedReturnsValus(): void
    {
        $value = 42;

        $this->song->object_cnt = $value;

        $this->assertSame(
            $value,
            $this->subject->getNumberPlayed()
        );
    }

    public function testGetNumberSkipped(): void
    {
        $value = 33;

        $this->song->skip_cnt = $value;

        $this->assertSame(
            $value,
            $this->subject->getNumberSkipped()
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

    public function testCanDownloadReturnsValue(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DOWNLOAD)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->canDownload()
        );
    }

    public function testCanEditPlaylistReturnsValue(): void
    {
        $this->gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->canEditPlaylist()
        );
    }

    public function testCanBeReorderedReturnsValus(): void
    {
        $this->gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->canBeReordered()
        );
    }
}
