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

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ArtistAlbumsMethodTest extends MockeryTestCase
{
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?ArtistAlbumsMethod $subject;

    /**
     * @return array<string, array{0: string, 1: bool, 2: string, 3: string}>
     */
    public static function albumSortConfigProvider(): array
    {
        return [
            'name_asc' => ['name_asc', false, 'name', 'ASC'],
            'name_desc' => ['name_desc', false, 'name', 'DESC'],
            'year_asc uses year when original year is off' => ['year_asc', false, 'year', 'ASC'],
            'year_desc uses original_year when configured' => ['year_desc', true, 'original_year', 'DESC'],
            'unrecognised value falls back to the name_year default' => ['some_unknown_value', false, 'name_year', 'ASC'],
        ];
    }

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

    #[DataProvider(methodName: 'albumSortConfigProvider')]
    public function testHandleDerivesDefaultSortFromAlbumSortConfig(
        string $albumSortConfig,
        bool $useOriginalYear,
        string $expectedSortField,
        string $expectedSortOrder,
    ): void {
        AmpConfig::set('album_sort', $albumSortConfig, true);
        AmpConfig::set('use_original_year', $useOriginalYear, true);

        $apiVersion = 8;

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);
        $artist     = $this->mock(Artist::class);

        $objectId = 666;
        $results  = [1, 2, 3];
        $result   = 'some-result';

        $this->modelFactory->shouldReceive('createArtist')
            ->with($objectId)
            ->once()
            ->andReturn($artist);
        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();
        $browse->shouldReceive('set_type')
            ->with('album')
            ->once();
        $browse->shouldReceive('set_sort_order')
            ->with('', [$expectedSortField, $expectedSortOrder])
            ->once();
        $browse->shouldReceive('set_filter')
            ->with('artist', $objectId)
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn($results);

        $output->shouldReceive('setOffset')
            ->with($apiVersion, 0)
            ->once();
        $output->shouldReceive('setLimit')
            ->with($apiVersion, 0)
            ->once();
        $output->shouldReceive('albums')
            ->with($apiVersion, $results, [], $user, 'some-auth')
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
                ['filter' => (string) $objectId, 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    /**
     * The album_artist flag swaps which browse filter is applied
     */
    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleFiltersByAlbumArtistWhenRequested(int $apiVersion): void
    {
        AmpConfig::set('album_sort', 'name_asc', true);
        AmpConfig::set('use_original_year', false, true);

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);
        $artist     = $this->mock(Artist::class);

        $objectId = 666;
        $results  = [1];
        $result   = 'some-result';

        $this->modelFactory->shouldReceive('createArtist')
            ->with($objectId)
            ->once()
            ->andReturn($artist);
        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();
        $browse->shouldReceive('set_type')
            ->with('album')
            ->once();
        $browse->shouldReceive('set_sort_order')
            ->with('', ['name', 'ASC'])
            ->once();
        $browse->shouldReceive('set_filter')
            ->with('album_artist', $objectId)
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn($results);

        $output->shouldReceive('setOffset')
            ->with($apiVersion, 0)
            ->once();
        $output->shouldReceive('setLimit')
            ->with($apiVersion, 0)
            ->once();
        $output->shouldReceive('albums')
            ->with($apiVersion, $results, [], $user, 'some-auth')
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
                [
                    'filter' => (string) $objectId,
                    'album_artist' => 1,
                    'api_format' => 'json',
                    'auth' => 'some-auth',
                ],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfArtistIsNotFound(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $artist     = $this->mock(Artist::class);

        $objectId = 666;

        $this->modelFactory->shouldReceive('createArtist')
            ->with($objectId)
            ->once()
            ->andReturn($artist);
        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

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

        $this->subject = new ArtistAlbumsMethod(
            $this->modelFactory
        );
    }

    #[Override]
    protected function tearDown(): void
    {
        AmpConfig::set('album_sort', '', true);
        AmpConfig::set('use_original_year', false, true);
    }
}
