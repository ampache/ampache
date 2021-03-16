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

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Lib\ItemToplistMapperInterface;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class StatsMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ItemToplistMapperInterface|MockInterface|null */
    private MockInterface $itemToplistMapper;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var UserRepositoryInterface|MockInterface|null */
    private MockInterface $userRepository;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    private StatsMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory     = $this->mock(StreamFactoryInterface::class);
        $this->itemToplistMapper = $this->mock(ItemToplistMapperInterface::class);
        $this->modelFactory      = $this->mock(ModelFactoryInterface::class);
        $this->userRepository    = $this->mock(UserRepositoryInterface::class);
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);

        $this->subject = new StatsMethod(
            $this->streamFactory,
            $this->itemToplistMapper,
            $this->modelFactory,
            $this->userRepository,
            $this->configContainer
        );
    }

    public function testHandleThrowsExceptionIfTypeParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: type');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfTypeIsNotSupported(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => 'foobar'
            ]
        );
    }

    public function testHandleReturnsEmptyResultWithUsername(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $username   = 'some-name';
        $limit      = 666;
        $offset     = 42;
        $type       = 'song';
        $mappingKey = 'some-key';
        $result     = 'some-result';
        $userId     = 33;

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->itemToplistMapper->shouldReceive('map')
            ->with($mappingKey)
            ->once()
            ->andReturn(function (
                User $userParam,
                string $typeParam,
                int $limitParam,
                int $offsetParam
            ) use ($user, $type, $limit, $offset): array {
                $this->assertSame($userParam, $user);
                $this->assertSame($typeParam, $type);
                $this->assertSame($limitParam, $limit);
                $this->assertSame($offsetParam, $offset);

                return [];
            });

        $output->shouldReceive('emptyResult')
            ->with($type)
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'type' => $type,
                    'username' => $username,
                    'filter' => $mappingKey,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            )
        );
    }

    public function testHandleReturnsSongResultWithUserId(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $limit      = 666;
        $offset     = 42;
        $type       = 'song';
        $mappingKey = 'some-key';
        $result     = 'some-result';
        $userId     = 33;
        $songId     = 123;

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->itemToplistMapper->shouldReceive('map')
            ->with($mappingKey)
            ->once()
            ->andReturn(function (
                User $userParam,
                string $typeParam,
                int $limitParam,
                int $offsetParam
            ) use ($user, $type, $limit, $offset, $songId): array {
                $this->assertSame($userParam, $user);
                $this->assertSame($typeParam, $type);
                $this->assertSame($limitParam, $limit);
                $this->assertSame($offsetParam, $offset);

                return [$songId];
            });

        $output->shouldReceive('songs')
            ->with([$songId], $userId)
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'type' => $type,
                    'user_id' => (string) $userId,
                    'filter' => $mappingKey,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            )
        );
    }

    public function testHandleReturnsArtistResultWithApiUser(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $limit      = 666;
        $offset     = 42;
        $type       = 'artist';
        $mappingKey = 'some-key';
        $result     = 'some-result';
        $userId     = 33;
        $artistId   = 123;

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->itemToplistMapper->shouldReceive('map')
            ->with($mappingKey)
            ->once()
            ->andReturn(function (
                User $userParam,
                string $typeParam,
                int $limitParam,
                int $offsetParam
            ) use ($user, $type, $limit, $offset, $artistId): array {
                $this->assertSame($userParam, $user);
                $this->assertSame($typeParam, $type);
                $this->assertSame($limitParam, $limit);
                $this->assertSame($offsetParam, $offset);

                return [$artistId];
            });

        $output->shouldReceive('artists')
            ->with([$artistId], [], $userId)
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'type' => $type,
                    'filter' => $mappingKey,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            )
        );
    }

    public function testHandleReturnsAlbumResultWithApiUser(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $limit      = 666;
        $type       = 'album';
        $mappingKey = 'some-key';
        $result     = 'some-result';
        $userId     = 33;
        $albumId    = 123;

        $this->configContainer->shouldReceive('getPopularThreshold')
            ->with(10)
            ->once()
            ->andReturn($limit);

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->itemToplistMapper->shouldReceive('map')
            ->with($mappingKey)
            ->once()
            ->andReturn(function (
                User $userParam,
                string $typeParam,
                int $limitParam,
                int $offsetParam
            ) use ($user, $type, $limit, $albumId): array {
                $this->assertSame($userParam, $user);
                $this->assertSame($typeParam, $type);
                $this->assertSame($limitParam, $limit);

                return [$albumId];
            });

        $output->shouldReceive('albums')
            ->with([$albumId], [], $userId)
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'type' => $type,
                    'filter' => $mappingKey
                ]
            )
        );
    }
}
