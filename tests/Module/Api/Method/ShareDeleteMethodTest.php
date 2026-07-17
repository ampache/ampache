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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShareRepositoryInterface;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * The successful deletion updates the counts through the Catalog::count_table() database static,
 * so only the guards can be exercised without a database fixture.
 */
class ShareDeleteMethodTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private MockInterface|ShareRepositoryInterface|null $shareRepository;
    private ?ShareDeleteMethod $subject;

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
    public function testHandleThrowsIfDisabled(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnFalse();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(T_('Enable: share'));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => '666', 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfFilterIsMissing(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'filter'));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfShareIsNotAccessible(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $share      = $this->mock(Share::class);

        $objectId = 666;

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->shareRepository->shouldReceive('findById')
            ->with($objectId)
            ->once()
            ->andReturn($share);

        $share->shouldReceive('isAccessible')
            ->with($user)
            ->once()
            ->andReturnFalse();

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $objectId);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId, 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfShareIsNotFound(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $objectId = 666;

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->shareRepository->shouldReceive('findById')
            ->with($objectId)
            ->once()
            ->andReturnNull();

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $objectId);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId, 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->shareRepository = $this->mock(ShareRepositoryInterface::class);

        $this->subject = new ShareDeleteMethod(
            $this->configContainer,
            $this->shareRepository
        );
    }
}
