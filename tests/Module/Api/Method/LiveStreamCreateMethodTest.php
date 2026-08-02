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
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * Resolving the catalog goes through the Catalog::create_from_id() database static, so only the
 * guards ahead of it can be exercised without a database fixture.
 */
class LiveStreamCreateMethodTest extends MockeryTestCase
{
    private MockInterface|PrivilegeCheckerInterface|null $privilegeChecker;
    private ?LiveStreamCreateMethod $subject;

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

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function missingParameterProvider(): array
    {
        return [
            'no name' => [['codec' => 'mp3', 'url' => 'u', 'catalog' => '1'], 'name'],
            'no codec' => [['name' => 'n', 'url' => 'u', 'catalog' => '1'], 'codec'],
            'no url' => [['name' => 'n', 'codec' => 'mp3', 'catalog' => '1'], 'url'],
            'no catalog' => [['name' => 'n', 'codec' => 'mp3', 'url' => 'u'], 'catalog'],
        ];
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfAccessDenied(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->mockAccess($user, false);

        $this->expectException(AccessFailedException::class);
        $this->expectExceptionMessage(sprintf(T_('Require: %s'), 50));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    /**
     * @param array<string, string> $input
     */
    #[DataProvider(methodName: 'missingParameterProvider')]
    public function testHandleThrowsIfParameterIsMissing(array $input, string $missing): void
    {
        $apiVersion = 8;

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->mockAccess($user, true);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), $missing));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [...$input, 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);

        $this->subject = new LiveStreamCreateMethod(
            $this->privilegeChecker
        );
    }

    private function mockAccess(MockInterface $user, bool $granted): void
    {
        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn(42);

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, 42)
            ->once()
            ->andReturn($granted);
    }
}
