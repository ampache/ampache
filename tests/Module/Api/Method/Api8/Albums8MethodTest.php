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

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class Albums8MethodTest extends MockeryTestCase
{
    private MockInterface|ModelFactoryInterface|null $modelFactory;
    private MockInterface|StreamFactoryInterface|null $streamFactory;
    private Albums8Method $subject;

    public static function albumSortConfigProvider(): array
    {
        return [
            'name_asc' => ['name_asc', false, 'name', 'ASC'],
            'name_desc' => ['name_desc', false, 'name', 'DESC'],
            'year_asc uses year when original year is off' => ['year_asc', false, 'year', 'ASC'],
            'year_desc uses original_year when configured' => ['year_desc', true, 'original_year', 'DESC'],
            'unrecognised value falls back to name_year default' => ['some_unknown_value', false, 'name_year', 'ASC'],
            'default falls back with original year enabled' => ['default', true, 'name_original_year', 'ASC'],
        ];
    }

    public function testHandleCondIsForwardedToSetConditions(): void
    {
        ob_start();

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result  = 'some-result';
        $include = [];

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
            ->with('', ['name_year', 'ASC'])
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('exact_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('catalog,1')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('setOffset')
            ->with(0)
            ->once();
        $output->shouldReceive('setLimit')
            ->with(0)
            ->once();
        $output->shouldReceive('setCount')
            ->with(0)
            ->once();
        $output->shouldReceive('albums')
            ->with(
                [],
                $include,
                $user,
                'stringauth'
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'include' => $include,
                    'exact' => true,
                    'api_format' => 'json',
                    'auth' => 'stringauth',
                    'cond' => 'catalog,1',
                ],
                $user
            )
        );
    }

    #[DataProvider(methodName: 'albumSortConfigProvider')]
    public function testHandleDerivesDefaultSortFromAlbumSortConfig(
        string $albumSortConfig,
        bool $useOriginalYear,
        string $expectedSortField,
        string $expectedSortOrder,
    ): void {
        ob_start();

        AmpConfig::set('album_sort', $albumSortConfig, true);
        AmpConfig::set('use_original_year', $useOriginalYear, true);

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result  = 'some-result';
        $include = [];

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
        $browse->shouldReceive('set_api_filter')
            ->with('exact_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('setOffset')
            ->with(0)
            ->once();
        $output->shouldReceive('setLimit')
            ->with(0)
            ->once();
        $output->shouldReceive('setCount')
            ->with(0)
            ->once();
        $output->shouldReceive('albums')
            ->with(
                [],
                $include,
                $user,
                'stringauth'
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'include' => $include,
                    'exact' => true,
                    'api_format' => 'json',
                    'auth' => 'stringauth',
                ],
                $user
            )
        );
    }

    public function testHandleEmptyListReturnsResponse(): void
    {
        ob_start();

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $user->catalogs['music'] = [1];

        $result  = '';
        $include = [];
        $limit   = 0;
        $offset  = 0;

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
            ->with('', ['name_year', 'ASC'])
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('exact_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('setOffset')
            ->with($offset)
            ->once();

        $output->shouldReceive('setLimit')
            ->with($limit)
            ->once();

        $output->shouldReceive('setCount')
            ->with(0)
            ->once();

        $output->shouldReceive('albums')
            ->with(
                [],
                $include,
                $user,
                'stringauth'
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'include' => $include,
                    'exact' => true,
                    'api_format' => 'json',
                    'auth' => 'stringauth',
                ],
                $user
            )
        );
    }

    public function testHandleLimitReturnsResponse(): void
    {
        ob_start();

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result  = 'some-result';
        $include = [];

        // Create 5 album mocks to simulate a larger result set
        $albums = [];
        for ($i = 0; $i < 5; $i++) {
            $albums[] = $this->mock(Album::class);
        }

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
            ->with('', ['name_year', 'ASC'])
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('exact_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn($albums);

        $output->shouldReceive('setOffset')
            ->with(0)
            ->once();

        // Expect the method to set the requested limit of 1
        $output->shouldReceive('setLimit')
            ->with(1)
            ->once();

        // Now that a limit of 1 was requested, the returned payload should contain only 1 album
        $output->shouldReceive('setCount')
            ->with(5)
            ->once();

        $output->shouldReceive('albums')
            ->with(
                $albums,
                $include,
                $user,
                'stringauth',
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'include' => $include,
                    'exact' => true,
                    'api_format' => 'json',
                    'auth' => 'stringauth',
                    'limit' => 1,
                ],
                $user
            )
        );
    }

    // NOTE: the tests below only verify sort/limit/offset/cond input are translated into calls on Browse

    public function testHandleOffsetIsForwardedToOutput(): void
    {
        ob_start();

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result  = 'some-result';
        $include = [];

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
            ->with('', ['name_year', 'ASC'])
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('exact_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        // The requested offset must reach the output formatter unchanged
        $output->shouldReceive('setOffset')
            ->with(3)
            ->once();
        $output->shouldReceive('setLimit')
            ->with(0)
            ->once();
        $output->shouldReceive('setCount')
            ->with(0)
            ->once();
        $output->shouldReceive('albums')
            ->with(
                [],
                $include,
                $user,
                'stringauth'
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'include' => $include,
                    'exact' => true,
                    'api_format' => 'json',
                    'auth' => 'stringauth',
                    'offset' => 3,
                ],
                $user
            )
        );
    }

    public function testHandleReturnsResponse(): void
    {
        ob_start();

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $album      = $this->mock(Album::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result  = 'some-result';
        $include = [];

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
            ->with('', ['name_year', 'ASC'])
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('exact_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([$album]);

        $output->shouldReceive('setOffset')
            ->with(0)
            ->once();

        $output->shouldReceive('setLimit')
            ->with(0)
            ->once();

        $output->shouldReceive('setCount')
            ->with(1)
            ->once();

        $output->shouldReceive('albums')
            ->with(
                [$album],
                $include,
                $user,
                'stringauth',
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'include' => $include,
                    'exact' => true,
                    'api_format' => 'json',
                    'auth' => 'stringauth',
                ],
                $user
            )
        );
    }

    public function testHandleSortInputIsForwardedVerbatimToSetSortOrder(): void
    {
        ob_start();

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result  = 'some-result';
        $include = [];

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

        // The raw 'sort' input is forwarded verbatim as the requested sort,
        // alongside the config-derived default ['name_year', 'ASC'] --
        // Browse::set_sort_order() itself decides which one wins.
        $browse->shouldReceive('set_sort_order')
            ->with('name,DESC', ['name_year', 'ASC'])
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('exact_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('setOffset')
            ->with(0)
            ->once();
        $output->shouldReceive('setLimit')
            ->with(0)
            ->once();
        $output->shouldReceive('setCount')
            ->with(0)
            ->once();
        $output->shouldReceive('albums')
            ->with(
                [],
                $include,
                $user,
                'stringauth'
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'include' => $include,
                    'exact' => true,
                    'api_format' => 'json',
                    'auth' => 'stringauth',
                    'sort' => 'name,DESC',
                ],
                $user
            )
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);
        $this->modelFactory  = $this->mock(ModelFactoryInterface::class);

        $this->subject = new Albums8Method(
            $this->streamFactory,
            $this->modelFactory
        );

        // Reset to a known baseline so tests aren't order-dependent on
        // AmpConfig state left behind by testHandleDerivesDefaultSortFromAlbumSortConfig
        AmpConfig::set('album_sort', '', true);
        AmpConfig::set('use_original_year', false, true);
    }
}
