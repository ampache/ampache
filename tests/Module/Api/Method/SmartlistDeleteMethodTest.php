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
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * The successful deletion updates the counts through the Catalog::count_table() database static,
 * so only the guards can be exercised without a database fixture.
 */
class SmartlistDeleteMethodTest extends MockeryTestCase
{
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?SmartlistDeleteMethod $subject;

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
     * The api accepts both the raw id and the `smart_` prefixed form
     *
     * @return array<string, array{0: string, 1: int}>
     */
    public static function filterProvider(): array
    {
        return [
            'raw id' => ['666', 666],
            'smart prefixed' => ['smart_666', 666],
        ];
    }

    #[DataProvider(methodName: 'filterProvider')]
    public function testHandleStripsTheSmartPrefixFromTheFilter(string $filter, int $objectId): void
    {
        $apiVersion = 8;

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $smartlist  = $this->mock(Smartlist::class);

        $this->modelFactory->shouldReceive('createSmartlist')
            ->with($objectId, $user)
            ->once()
            ->andReturn($smartlist);

        $smartlist->shouldReceive('has_access')
            ->with($user)
            ->once()
            ->andReturnFalse();

        $this->expectException(AccessFailedException::class);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => $filter, 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfAccessDenied(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $smartlist  = $this->mock(Smartlist::class);

        $objectId = 666;

        $this->modelFactory->shouldReceive('createSmartlist')
            ->with($objectId, $user)
            ->once()
            ->andReturn($smartlist);

        $smartlist->shouldReceive('has_access')
            ->with($user)
            ->once()
            ->andReturnFalse();

        $this->expectException(AccessFailedException::class);
        $this->expectExceptionMessage(sprintf(T_('Require: %s'), 100));

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
    public function testHandleThrowsIfFilterIsMissing(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

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

    #[Override]
    protected function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new SmartlistDeleteMethod(
            $this->modelFactory
        );
    }
}
