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

namespace Ampache\Module\Api\Method;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class UserMethodTest extends MockeryTestCase
{
    private ?UserMethod $subject;
    private MockInterface|UserRepositoryInterface|null $userRepository;

    /**
     * The same method serves both api versions; the version is only handed to the output
     *
     * @return array<string, array{0: int}>
     */
    public static function apiVersionProvider(): array
    {
        return [
            'api6' => [6],
            'api8' => [8],
        ];
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsErrorIfUserNotFound(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'error-result';

        $this->userRepository->shouldReceive('findByUsername')
            ->with('nobody')
            ->once()
            ->andReturnNull();

        $output->shouldReceive('error')
            ->with(
                $apiVersion,
                ErrorCodeEnum::NOT_FOUND,
                'Not Found: nobody',
                UserMethod::ACTION,
                'username'
            )
            ->once()
            ->andReturn($result);

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')
            ->with($result)
            ->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => 'nobody', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    /**
     * Looking up another user by name yields the public (non full) info
     */
    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsOtherUserWithoutFullInfo(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $checkUser  = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'some-result';

        $this->userRepository->shouldReceive('findByUsername')
            ->with('someone')
            ->once()
            ->andReturn($checkUser);
        $this->userRepository->shouldReceive('getValid')
            ->with(true)
            ->once()
            ->andReturn([42]);

        $checkUser->shouldReceive('getId')
            ->withNoArgs()
            ->andReturn(42);
        $user->shouldReceive('getId')
            ->withNoArgs()
            ->andReturn(1);
        $user->access = 25;

        $output->shouldReceive('user')
            ->with($apiVersion, $checkUser, false, 'some-auth', false)
            ->once()
            ->andReturn($result);

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')
            ->with($result)
            ->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => 'someone', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    /**
     * Omitting the username returns the callers own data, with full info
     */
    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsOwnUserWithFullInfo(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'some-result';

        // the resolved api version must reach the output untouched
        $output->shouldReceive('user')
            ->with($apiVersion, $user, true, 'some-auth', false)
            ->once()
            ->andReturn($result);

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')
            ->with($result)
            ->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->userRepository = $this->mock(UserRepositoryInterface::class);

        $this->subject = new UserMethod(
            $this->userRepository
        );
    }
}
