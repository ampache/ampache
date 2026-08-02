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
use Ampache\Module\Database\Query\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ArtistsMethodTest extends MockeryTestCase
{
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?ArtistsMethod $subject;

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
    public function testHandleBrowsesAlbumArtistsAndExpandsInclude(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $results = [1];
        $result  = 'some-result';

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')->with($user)->once();

        // `album_artist=1` must browse album artists rather than all artists
        $browse->shouldReceive('set_type')->with('album_artist')->once();
        $browse->shouldReceive('set_sort_order')->with('', ['name', 'ASC'])->once();
        $browse->shouldReceive('set_api_filter')->with('exact_match', 'some-artist')->once();
        $browse->shouldReceive('set_api_filter')->with('add', '')->once();
        $browse->shouldReceive('set_api_filter')->with('update', '')->once();
        $browse->shouldReceive('set_conditions')->with('')->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn($results);

        $output->shouldReceive('setOffset')->with($apiVersion, 0)->once();
        $output->shouldReceive('setLimit')->with($apiVersion, 0)->once();

        // a comma separated `include` must be expanded into a list
        $output->shouldReceive('artists')
            ->with($apiVersion, $results, ['songs', 'albums'], $user, 'some-auth')
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
                [
                    'filter' => 'some-artist',
                    'exact' => 1,
                    'album_artist' => 1,
                    'include' => 'songs,albums',
                    'api_format' => 'json',
                    'auth' => 'some-auth',
                ],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsArtists(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $results = [1, 2, 3];
        $result  = 'some-result';

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')->with($user)->once();
        $browse->shouldReceive('set_type')->with('artist')->once();
        $browse->shouldReceive('set_sort_order')->with('', ['name', 'ASC'])->once();
        $browse->shouldReceive('set_api_filter')->with('alpha_match', '')->once();
        $browse->shouldReceive('set_api_filter')->with('add', '')->once();
        $browse->shouldReceive('set_api_filter')->with('update', '')->once();
        $browse->shouldReceive('set_conditions')->with('')->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn($results);

        $output->shouldReceive('setOffset')->with($apiVersion, 0)->once();
        $output->shouldReceive('setLimit')->with($apiVersion, 0)->once();
        $output->shouldReceive('artists')
            ->with($apiVersion, $results, [], $user, 'some-auth')
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

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsEmptyResult(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'empty-result';

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')->with($user)->once();
        $browse->shouldReceive('set_type')->with('artist')->once();
        $browse->shouldReceive('set_sort_order')->with('', ['name', 'ASC'])->once();
        $browse->shouldReceive('set_api_filter')->with('alpha_match', '')->once();
        $browse->shouldReceive('set_api_filter')->with('add', '')->once();
        $browse->shouldReceive('set_api_filter')->with('update', '')->once();
        $browse->shouldReceive('set_conditions')->with('')->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('writeEmpty')
            ->with($apiVersion, 'artist')
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

        $this->subject = new ArtistsMethod(
            $this->modelFactory
        );
    }
}
