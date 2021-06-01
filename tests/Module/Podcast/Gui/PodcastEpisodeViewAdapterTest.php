<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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
 */

declare(strict_types=1);

namespace Ampache\Module\Podcast\Gui;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Repository\Model\PodcastEpisodeInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;

class PodcastEpisodeViewAdapterTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface */
    private MockInterface $configContainer;

    /** @var MockInterface|MediaDeletionCheckerInterface */
    private MockInterface $mediaDeletionChecker;

    /** @var PodcastEpisodeInterface|MockInterface */
    private MockInterface $podcastEpisode;

    /** @var MockInterface|User */
    private MockInterface $user;

    private PodcastEpisodeViewAdapter $subject;

    public function setUp(): void
    {
        $this->configContainer      = $this->mock(ConfigContainerInterface::class);
        $this->mediaDeletionChecker = $this->mock(MediaDeletionCheckerInterface::class);
        $this->podcastEpisode       = $this->mock(PodcastEpisodeInterface::class);
        $this->user                 = $this->mock(User::class);

        $this->subject = new PodcastEpisodeViewAdapter(
            $this->configContainer,
            $this->mediaDeletionChecker,
            $this->podcastEpisode,
            $this->user
        );
    }

    public function testGetTitleReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcastEpisode->shouldReceive('getTitleFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getTitle()
        );
    }

    public function testGetDescriptionReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcastEpisode->shouldReceive('getDescriptionFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getDescription()
        );
    }

    public function testGetCategoryReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcastEpisode->shouldReceive('getCategoryFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getCategory()
        );
    }

    public function testGetAuthorReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcastEpisode->shouldReceive('getAuthorFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getAuthor()
        );
    }

    public function testGetPublicationDateReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcastEpisode->shouldReceive('getPublicationDateFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getPublicationDate()
        );
    }

    public function testGetStateReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcastEpisode->shouldReceive('getState')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getState()
        );
    }

    public function testGetWebsiteReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcastEpisode->shouldReceive('getWebsiteFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getWebsite()
        );
    }

    public function testGetDurationReturnsValusIfAvailable(): void
    {
        $value = 'some-value';
        $time  = 666;

        $this->podcastEpisode->shouldReceive('getTime')
            ->withNoArgs()
            ->once()
            ->andReturn($time);
        $this->podcastEpisode->shouldReceive('getDurationFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getDuration()
        );
    }

    public function testGetDurationReturnsFallbackValue(): void
    {
        $this->podcastEpisode->shouldReceive('getTime')
            ->withNoArgs()
            ->once()
            ->andReturn(0);

        $this->assertSame(
            'N/A',
            $this->subject->getDuration()
        );
    }

    public function testHasFileReturnsValue(): void
    {
        $this->podcastEpisode->shouldReceive('hasFile')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->hasFile()
        );
    }

    public function testGetFileReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcastEpisode->shouldReceive('getFile')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getFile()
        );
    }

    public function testGetSizeReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcastEpisode->shouldReceive('getSizeFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getSize()
        );
    }

    public function testGetPlayUrlReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcastEpisode->shouldReceive('play_url')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getPlayUrl()
        );
    }

    public function testGetBitrateReturnsEmptyStringIfBitrateIsMissing(): void
    {
        $this->podcastEpisode->shouldReceive('getBitrate')
            ->withNoArgs()
            ->once()
            ->andReturnNull();
        $this->podcastEpisode->shouldReceive('getMode')
            ->withNoArgs()
            ->once()
            ->andReturnNull();

        $this->assertSame(
            '',
            $this->subject->getBitrate()
        );
    }

    public function testGetBitrateReturnsFormattedValue(): void
    {
        $bitrate = 666;
        $mode    = 'some-mode';

        $this->podcastEpisode->shouldReceive('getBitrate')
            ->withNoArgs()
            ->once()
            ->andReturn($bitrate * 1000);
        $this->podcastEpisode->shouldReceive('getMode')
            ->withNoArgs()
            ->once()
            ->andReturn($mode);

        $this->assertSame(
            sprintf(
                '%d-%s',
                $bitrate,
                strtoupper($mode)
            ),
            $this->subject->getBitrate()
        );
    }

    public function testCanDeleteReturnsValue(): void
    {
        $userId = 666;

        $this->user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($this->podcastEpisode, $userId)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->canDelete()
        );
    }
}
