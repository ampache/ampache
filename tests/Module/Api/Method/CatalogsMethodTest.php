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
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class CatalogsMethodTest extends MockeryTestCase
{
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?CatalogsMethod $subject;

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
     * Every video-ish catalog type is stored as a single `video` gather type
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function videoTypeProvider(): array
    {
        return [
            'clip' => ['clip', 'video'],
            'tvshow' => ['tvshow', 'video'],
            'movie' => ['movie', 'video'],
            'personal_video' => ['personal_video', 'video'],
            'video' => ['video', 'video'],
            'music stays music' => ['music', 'music'],
            'podcast stays podcast' => ['podcast', 'podcast'],
        ];
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleIgnoresUnknownCatalogFilter(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $userId = 666;

        $user->shouldReceive('getId')->withNoArgs()->andReturn($userId);

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')->with($user)->once();
        $browse->shouldReceive('set_type')->with('catalog')->once();
        $browse->shouldReceive('set_filter')->with('user', $userId)->once();

        // an unknown filter must not reach the browse as a gather type
        $browse->shouldReceive('set_filter')->with('gather_type', Mockery::any())->never();
        $browse->shouldReceive('set_conditions')->with('')->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('writeEmpty')
            ->with($apiVersion, 'catalog')
            ->once()
            ->andReturn('empty-result');

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')->with('empty-result')->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => 'not-a-catalog-type', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'videoTypeProvider')]
    public function testHandleMapsCatalogFilterToGatherType(string $filter, string $gatherType): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $userId  = 666;
        $results = [1];

        $user->shouldReceive('getId')->withNoArgs()->andReturn($userId);

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')->with($user)->once();
        $browse->shouldReceive('set_type')->with('catalog')->once();
        $browse->shouldReceive('set_filter')->with('user', $userId)->once();
        $browse->shouldReceive('set_filter')->with('gather_type', $gatherType)->once();
        $browse->shouldReceive('set_conditions')->with('')->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn($results);

        $output->shouldReceive('setOffset')->with(8, 0)->once();
        $output->shouldReceive('setLimit')->with(8, 0)->once();
        $output->shouldReceive('catalogs')
            ->with(8, $results, $user)
            ->once()
            ->andReturn('some-result');

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')->with('some-result')->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => $filter, 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                8
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsCatalogs(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $userId  = 666;
        $results = [1, 2, 3];
        $result  = 'some-result';

        $user->shouldReceive('getId')->withNoArgs()->andReturn($userId);

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')->with($user)->once();
        $browse->shouldReceive('set_type')->with('catalog')->once();
        $browse->shouldReceive('set_filter')->with('user', $userId)->once();
        $browse->shouldReceive('set_conditions')->with('')->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn($results);

        $output->shouldReceive('setOffset')->with($apiVersion, 0)->once();
        $output->shouldReceive('setLimit')->with($apiVersion, 0)->once();
        $output->shouldReceive('catalogs')
            ->with($apiVersion, $results, $user)
            ->once()
            ->andReturn($result);

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')->with($result)->once();

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
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new CatalogsMethod(
            $this->modelFactory
        );
    }
}
