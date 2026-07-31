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
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class BrowseMethodTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?BrowseMethod $subject;

    /**
     * `album_disk` is absent because api version 6 rejects that type before it reads the catalog
     *
     * @return array<string, array{0: string}>
     */
    public static function apiVersion6ParentTypeProvider(): array
    {
        $types = self::parentTypeProvider();
        unset($types['album_disk']);

        return $types;
    }

    /**
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
     * Every type the browse resolves through a parent object, so none of them addresses a catalog itself
     *
     * @return array<string, array{0: string}>
     */
    public static function parentTypeProvider(): array
    {
        return [
            'album' => ['album'],
            'album_disk' => ['album_disk'],
            'album_artist' => ['album_artist'],
            'artist' => ['artist'],
        ];
    }

    #[DataProvider(methodName: 'parentTypeProvider')]
    public function testHandleDoesNotRequireACatalogOnApiVersion8(string $objectType): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'type-error';

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);

        // a zero object id stops the browse before it loads the parent, so the request reaches the type
        // check without a database and only the catalog requirement can turn it into a missing-parameter error
        $output->shouldReceive('error')
            ->with(8, ErrorCodeEnum::BAD_REQUEST, sprintf('Bad Request: %s', $objectType), 'browse', 'type')
            ->once()
            ->andReturn($result);

        $stream->shouldReceive('write')
            ->with($result)
            ->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['type' => $objectType, 'filter' => '0', 'api_format' => 'json', 'auth' => 'auth-token'],
                $user,
                8
            )
        );
    }

    #[DataProvider(methodName: 'apiVersion6ParentTypeProvider')]
    public function testHandleThrowsWithoutACatalogOnApiVersion6(string $objectType): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: catalog');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => $objectType, 'filter' => '0', 'api_format' => 'json', 'auth' => 'auth-token'],
            $user,
            6
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsWithoutAFilter(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: filter');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => 'album', 'catalog' => 1, 'api_format' => 'json', 'auth' => 'auth-token'],
            $user,
            $apiVersion
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);

        $this->subject = new BrowseMethod(
            $this->configContainer,
            $this->modelFactory,
        );
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->configContainer = null;
        $this->modelFactory    = null;
        $this->subject         = null;
    }
}
