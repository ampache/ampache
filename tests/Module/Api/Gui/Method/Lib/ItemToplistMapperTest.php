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

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;

class ItemToplistMapperTest extends MockeryTestCase
{
    /** @var ArtistRepositoryInterface|MockInterface|null */
    private MockInterface $artistRepository;

    /** @var AlbumRepositoryInterface|MockInterface|null */
    private MockInterface $albumRepository;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    private ItemToplistMapper $subject;

    public function setUp(): void
    {
        $this->artistRepository = $this->mock(ArtistRepositoryInterface::class);
        $this->albumRepository  = $this->mock(AlbumRepositoryInterface::class);
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);

        $this->subject = new ItemToplistMapper(
            $this->artistRepository,
            $this->albumRepository,
            $this->configContainer
        );
    }

    public function testMapMapsRecent(): void
    {
        $user = $this->mock(User::class);

        $type   = 'some-type';
        $limit  = 666;
        $offset = 42;
        $result = ['some-result'];

        $user->shouldReceive('get_recently_played')
            ->with($type, $limit, $offset, true)
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            call_user_func(
                $this->subject->map('recent'),
                $user,
                $type,
                $limit,
                $offset
            )
        );
    }

    public function testMapMapsForgotten(): void
    {
        $user = $this->mock(User::class);

        $type   = 'some-type';
        $limit  = 666;
        $offset = 42;
        $result = ['some-result'];

        $user->shouldReceive('get_recently_played')
            ->with($type, $limit, $offset, false)
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            call_user_func(
                $this->subject->map('forgotten'),
                $user,
                $type,
                $limit,
                $offset
            )
        );
    }

    public function testMapMapsRandomArtist(): void
    {
        $user = $this->mock(User::class);

        $type   = 'artist';
        $limit  = 666;
        $offset = 42;
        $result = ['some-result'];
        $userId = 33;

        $this->artistRepository->shouldReceive('getRandom')
            ->with($userId, $limit)
            ->once()
            ->andReturn($result);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->assertSame(
            $result,
            call_user_func(
                $this->subject->map('random'),
                $user,
                $type,
                $limit,
                $offset
            )
        );
    }

    public function testMapMapsRandomAlbum(): void
    {
        $user = $this->mock(User::class);

        $type   = 'album';
        $limit  = 666;
        $offset = 42;
        $result = ['some-result'];
        $userId = 33;

        $this->albumRepository->shouldReceive('getRandom')
            ->with($userId, $limit)
            ->once()
            ->andReturn($result);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->assertSame(
            $result,
            call_user_func(
                $this->subject->map('random'),
                $user,
                $type,
                $limit,
                $offset
            )
        );
    }

    public function testMapMapsRandomWithUnsupportedType(): void
    {
        $user = $this->mock(User::class);

        $type   = 'foobar';
        $limit  = 666;
        $offset = 42;

        $this->assertSame(
            [],
            call_user_func(
                $this->subject->map('random'),
                $user,
                $type,
                $limit,
                $offset
            )
        );
    }
}
