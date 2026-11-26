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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\database_object;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionProperty;

class Stats5MethodTest extends MockeryTestCase
{
    private AlbumRepositoryInterface|MockInterface|null $albumRepository;
    private ArtistRepositoryInterface|MockInterface|null $artistRepository;
    private BrowseFactoryInterface|MockInterface|null $browseFactory;
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private StreamFactoryInterface|MockInterface|null $streamFactory;
    private ?Stats5Method $subject;

    /**
     * GHSA-vfj7-w766-538w: a random-playlist stats lookup must filter playlist_open by the caller, not the target
     */
    public function testHandleFiltersPlaylistVisibilityByTheViewerNotTheTarget(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $viewer     = $this->mock(User::class);
        $target     = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $results = [1, 2];
        $result  = 'some-result';

        AmpConfig::set('memory_cache', true, true);
        new ReflectionProperty(database_object::class, '_enabled')->setValue(null, null);
        Preference::add_to_cache('get_by_user-allow_personal_info_recent', 7, [1]);
        Preference::add_to_cache('get_by_user-api_hidden_playlists', 1, ['']);

        $viewer->id          = 1;
        $viewer->streamtoken = 'viewer-token';
        $target->id          = 7;
        $target->shouldReceive('isNew')->andReturn(false);

        $this->modelFactory->shouldReceive('createUser')
            ->with(7)
            ->once()
            ->andReturn($target);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::ALLOW_VIDEO)
            ->once()
            ->andReturn(true);
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturn(true);
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::POPULAR_THRESHOLD)
            ->once()
            ->andReturn(10);

        $this->browseFactory->shouldReceive('create')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')->once();
        $browse->shouldReceive('set_type')->with('playlist_search')->once();
        $browse->shouldReceive('set_sort')->with('rand', null, false)->once();
        $browse->shouldReceive('set_filter')->with('playlist_open', 1)->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn($results);

        $output->shouldReceive('setOffset')->with(5, 0)->once();
        $output->shouldReceive('setLimit')->with(5, 10)->once();
        $output->shouldReceive('playlists')
            ->with(5, $results, Mockery::any(), 'some-auth')
            ->once()
            ->andReturn($result);

        $response->shouldReceive('withBody')
            ->once()
            ->andReturn($response);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['type' => 'playlist', 'user_id' => 7, 'api_format' => 'json', 'auth' => 'some-auth'],
                $viewer,
                5
            )
        );
    }

    public function testHandleRendersNothingForAUserWhoKeepsRecentPrivate(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $viewer     = $this->mock(User::class);
        $target     = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'empty-result';

        // filter=recent means a missing guard would call get_recently_played on the target; it is deliberately
        // left unstubbed so that path fails the test. the guard must short-circuit to writeEmpty before it.
        AmpConfig::set('memory_cache', true, true);
        new ReflectionProperty(database_object::class, '_enabled')->setValue(null, null);
        Preference::add_to_cache('get_by_user-allow_personal_info_recent', 7, [0]);

        $viewer->id = 1;
        $target->id = 7;
        $target->shouldReceive('isNew')->andReturn(false);

        $this->modelFactory->shouldReceive('createUser')
            ->with(7)
            ->once()
            ->andReturn($target);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::ALLOW_VIDEO)
            ->once()
            ->andReturn(true);
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturn(true);
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::POPULAR_THRESHOLD)
            ->once()
            ->andReturn(10);

        $output->shouldReceive('writeEmpty')
            ->with(5, 'song')
            ->once()
            ->andReturn($result);

        $response->shouldReceive('withBody')
            ->once()
            ->andReturn($response);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['type' => 'song', 'user_id' => 7, 'filter' => 'recent', 'api_format' => 'json', 'auth' => 'some-auth'],
                $viewer,
                5
            )
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->albumRepository  = $this->mock(AlbumRepositoryInterface::class);
        $this->artistRepository = $this->mock(ArtistRepositoryInterface::class);
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory     = $this->mock(ModelFactoryInterface::class);
        $this->browseFactory    = $this->mock(BrowseFactoryInterface::class);
        $this->streamFactory    = $this->mock(StreamFactoryInterface::class);

        $this->subject = new Stats5Method(
            $this->albumRepository,
            $this->artistRepository,
            $this->configContainer,
            $this->modelFactory,
            $this->browseFactory,
            $this->streamFactory
        );
    }
}
