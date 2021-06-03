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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method\Lib;

use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Api;
use Ampache\Module\Preference\UserPreferenceRetrieverInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Mockery\MockInterface;

class ServerDetailsRetrieverTest extends MockeryTestCase
{
    /** @var CatalogRepositoryInterface|MockInterface|null */
    private MockInterface $catalogRepository;

    /** @var UpdateInfoRepositoryInterface|MockInterface|null */
    private MockInterface $updateInfoRepository;

    private MockInterface $userPreferenceRetriever;

    private ServerDetailsRetriever $subject;

    public function setUp(): void
    {
        $this->catalogRepository       = $this->mock(CatalogRepositoryInterface::class);
        $this->updateInfoRepository    = $this->mock(UpdateInfoRepositoryInterface::class);
        $this->userPreferenceRetriever = $this->mock(UserPreferenceRetrieverInterface::class);

        $this->subject = new ServerDetailsRetriever(
            $this->catalogRepository,
            $this->updateInfoRepository,
            $this->userPreferenceRetriever
        );
    }

    public function testRetrieveReturnsData(): void
    {
        $user = $this->mock(User::class);

        $lastUpdate = 11111;
        $lastAdd    = 22222;
        $lastClean  = 33333;
        $userId     = 666;

        $token = 'some-token';

        $songCount            = 11;
        $albumCount           = 22;
        $artistCount          = 33;
        $tagCount             = 44;
        $playlistCount        = 55;
        $searchCount          = 77;
        $userCount            = 88;
        $catalogCount         = 99;
        $videoCount           = 111;
        $podcastCount         = 222;
        $podcastEpiscodeCount = 333;
        $shareCount           = 444;
        $licenseCount         = 555;
        $liveStreamCount      = 666;
        $labelCount           = 777;

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->catalogRepository->shouldReceive('getLastActionDates')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'update' => $lastUpdate,
                'add' => $lastAdd,
                'clean' => $lastClean
            ]);

        $this->userPreferenceRetriever->shouldReceive('retrieve')
            ->with($userId, ConfigurationKeyEnum::ALBUM_GROUP)
            ->once()
            ->andReturn('');

        $this->updateInfoRepository->shouldReceive('getServerCounts')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'song' => $songCount,
                'album' => $albumCount,
                'artist' => $artistCount,
                'tag' => $tagCount,
                'playlist' => $playlistCount,
                'search' => $searchCount,
                'user' => $userCount,
                'catalog' => $catalogCount,
                'video' => $videoCount,
                'podcast' => $podcastCount,
                'podcast_episode' => $podcastEpiscodeCount,
                'share' => $shareCount,
                'license' => $licenseCount,
                'live_stream' => $liveStreamCount,
                'label' => $labelCount,
            ]);

        $this->assertSame(
            [
                'auth' => $token,
                'api' => Api::$version,
                'update' => date('c', $lastUpdate),
                'add' => date('c', $lastAdd),
                'clean' => date('c', $lastClean),
                'songs' => $songCount,
                'albums' => $albumCount,
                'artists' => $artistCount,
                'genres' => $tagCount,
                'playlists' => $playlistCount + $searchCount,
                'users' => $userCount,
                'catalogs' => $catalogCount,
                'videos' => $videoCount,
                'podcasts' => $podcastCount,
                'podcast_episodes' => $podcastEpiscodeCount,
                'shares' => $shareCount,
                'licenses' => $licenseCount,
                'live_streams' => $liveStreamCount,
                'labels' => $labelCount,
            ],
            $this->subject->retrieve($user, $token)
        );
    }

    public function testRetrieveReturnsDataForGroupedMode(): void
    {
        $user = $this->mock(User::class);

        $lastUpdate = 11111;
        $lastAdd    = 22222;
        $lastClean  = 33333;
        $userId     = 666;

        $token = 'some-token';

        $songCount            = 11;
        $albumCount           = 22;
        $artistCount          = 33;
        $tagCount             = 44;
        $playlistCount        = 55;
        $searchCount          = 77;
        $userCount            = 88;
        $catalogCount         = 99;
        $videoCount           = 111;
        $podcastCount         = 222;
        $podcastEpiscodeCount = 333;
        $shareCount           = 444;
        $licenseCount         = 555;
        $liveStreamCount      = 666;
        $labelCount           = 777;

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->catalogRepository->shouldReceive('getLastActionDates')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'update' => $lastUpdate,
                'add' => $lastAdd,
                'clean' => $lastClean
            ]);

        $this->userPreferenceRetriever->shouldReceive('retrieve')
            ->with($userId, ConfigurationKeyEnum::ALBUM_GROUP)
            ->once()
            ->andReturn(1);

        $this->updateInfoRepository->shouldReceive('getServerCounts')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'song' => $songCount,
                'album_group' => $albumCount,
                'artist' => $artistCount,
                'tag' => $tagCount,
                'playlist' => $playlistCount,
                'search' => $searchCount,
                'user' => $userCount,
                'catalog' => $catalogCount,
                'video' => $videoCount,
                'podcast' => $podcastCount,
                'podcast_episode' => $podcastEpiscodeCount,
                'share' => $shareCount,
                'license' => $licenseCount,
                'live_stream' => $liveStreamCount,
                'label' => $labelCount,
            ]);

        $this->assertSame(
            [
                'auth' => $token,
                'api' => Api::$version,
                'update' => date('c', $lastUpdate),
                'add' => date('c', $lastAdd),
                'clean' => date('c', $lastClean),
                'songs' => $songCount,
                'albums' => $albumCount,
                'artists' => $artistCount,
                'genres' => $tagCount,
                'playlists' => $playlistCount + $searchCount,
                'users' => $userCount,
                'catalogs' => $catalogCount,
                'videos' => $videoCount,
                'podcasts' => $podcastCount,
                'podcast_episodes' => $podcastEpiscodeCount,
                'shares' => $shareCount,
                'licenses' => $licenseCount,
                'live_streams' => $liveStreamCount,
                'labels' => $labelCount,
            ],
            $this->subject->retrieve($user, $token)
        );
    }
}
