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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class RecordPlay8MethodTest extends MockeryTestCase
{
    private MockInterface|ModelFactoryInterface|null $modelFactory;
    private MockInterface|PrivilegeCheckerInterface|null $privilegeChecker;
    private ?RecordPlay8Method $subject;
    private MockInterface|UserRepositoryInterface|null $userRepository;

    /**
     * The catalog-access guard must be consulted before the play is recorded; asserting getCatalogId()
     * is called once fails if the guard is dropped, even though has_access() itself is a static.
     */
    public function testHandleChecksCatalogAccessBeforeRecording(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);

        $user->username = 'tester';
        $user->shouldReceive('getId')->andReturn(42);

        $this->userRepository->shouldReceive('getValid')
            ->withNoArgs()
            ->andReturn([42]);

        $this->modelFactory->shouldReceive('createSong')
            ->with(123)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')->andReturn(false);
        $song->shouldReceive('getCatalogId')
            ->withNoArgs()
            ->once()
            ->andReturn(0);
        $song->shouldReceive('getId')->andReturn(123);
        $song->shouldReceive('set_played')
            ->with(42, 'api', [], Mockery::type('int'))
            ->once()
            ->andReturn(false);

        $output->shouldReceive('success')
            ->with(8, 'successfully recorded play: 123 for: tester')
            ->once()
            ->andReturn('ok-result');
        $response->shouldReceive('getBody')->andReturn($stream);
        $stream->shouldReceive('write')->with('ok-result')->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => '123', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                8
            )
        );
    }

    public function testHandleThrowsWhenSongIsUnknown(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);

        $user->shouldReceive('getId')->andReturn(42);

        $this->userRepository->shouldReceive('getValid')
            ->withNoArgs()
            ->andReturn([42]);

        $this->modelFactory->shouldReceive('createSong')
            ->with(123)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')->andReturn(true);

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage('123');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => '123', 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            8
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->modelFactory     = $this->mock(ModelFactoryInterface::class);
        $this->privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $this->userRepository   = $this->mock(UserRepositoryInterface::class);

        $this->subject = new RecordPlay8Method(
            $this->modelFactory,
            $this->privilegeChecker,
            $this->userRepository
        );
    }
}
