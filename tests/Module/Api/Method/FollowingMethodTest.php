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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserFollowerRepositoryInterface;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Resolving the leader goes through the User::get_from_id()/User::get_from_username() database
 * statics, so only the config gate can be exercised without a database fixture.
 */
class FollowingMethodTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private ?FollowingMethod $subject;
    private MockInterface|UserFollowerRepositoryInterface|null $userFollowerRepository;

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
    public function testHandleReturnsErrorIfDisabled(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'error-result';

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnFalse();

        $output->shouldReceive('error')
            ->with(
                $apiVersion,
                ErrorCodeEnum::ACCESS_DENIED,
                'Enable: sociable',
                FollowingMethod::ACTION,
                'system'
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
                ['api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer        = $this->mock(ConfigContainerInterface::class);
        $this->userFollowerRepository = $this->mock(UserFollowerRepositoryInterface::class);

        $this->subject = new FollowingMethod(
            $this->configContainer,
            $this->userFollowerRepository
        );
    }
}
