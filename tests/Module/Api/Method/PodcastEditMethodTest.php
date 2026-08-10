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
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PodcastEditMethodTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private MockInterface|PodcastRepositoryInterface|null $podcastRepository;
    private MockInterface|PrivilegeCheckerInterface|null $privilegeChecker;
    private ?PodcastEditMethod $subject;

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
    public function testHandleKeepsTheExistingValuesIfNothingIsProvided(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $podcast    = $this->mock(Podcast::class);
        $stream     = $this->mock(StreamInterface::class);

        $podcastId = 666;
        $result    = 'some-result';

        $this->mockPodcastEnabled();
        $this->mockAccess($user, true);

        $this->podcastRepository->shouldReceive('findById')
            ->with($podcastId)
            ->once()
            ->andReturn($podcast);

        $podcast->shouldReceive('getFeedUrl')->withNoArgs()->once()->andReturn('some-feed');
        $podcast->shouldReceive('getTitle')->withNoArgs()->once()->andReturn('some-title');
        $podcast->shouldReceive('getWebsite')->withNoArgs()->once()->andReturn('some-website');
        $podcast->shouldReceive('get_description')->withNoArgs()->once()->andReturn('some-description');
        $podcast->shouldReceive('getGenerator')->withNoArgs()->once()->andReturn('some-generator');
        $podcast->shouldReceive('getCopyright')->withNoArgs()->once()->andReturn('some-copyright');

        $podcast->shouldReceive('setFeedUrl')->with('some-feed')->once()->andReturnSelf();
        $podcast->shouldReceive('setTitle')->with('some-title')->once()->andReturnSelf();
        $podcast->shouldReceive('setWebsite')->with('some-website')->once()->andReturnSelf();
        $podcast->shouldReceive('setDescription')->with('some-description')->once()->andReturnSelf();
        $podcast->shouldReceive('setGenerator')->with('some-generator')->once()->andReturnSelf();
        $podcast->shouldReceive('setCopyright')->with('some-copyright')->once()->andReturnSelf();
        $podcast->shouldReceive('save')->withNoArgs()->once();

        // the resolved api version must reach the output untouched
        $output->shouldReceive('success')
            ->with($apiVersion, 'podcast ' . $podcastId . ' updated')
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
                ['filter' => (string) $podcastId, 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfAccessDenied(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->mockPodcastEnabled();
        $this->mockAccess($user, false);

        $this->expectException(AccessFailedException::class);
        $this->expectExceptionMessage(sprintf(T_('Require: %s'), 50));

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
    public function testHandleThrowsIfDisabled(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnFalse();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(T_('Enable: podcast'));

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

        $this->mockPodcastEnabled();
        $this->mockAccess($user, true);

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
    public function testHandleThrowsIfPodcastIsNotFound(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $podcastId = 666;

        $this->mockPodcastEnabled();
        $this->mockAccess($user, true);

        $this->podcastRepository->shouldReceive('findById')
            ->with($podcastId)
            ->once()
            ->andReturnNull();

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $podcastId);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $podcastId, 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->podcastRepository = $this->mock(PodcastRepositoryInterface::class);
        $this->privilegeChecker  = $this->mock(PrivilegeCheckerInterface::class);

        $this->subject = new PodcastEditMethod(
            $this->configContainer,
            $this->podcastRepository,
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

    private function mockPodcastEnabled(): void
    {
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();
    }
}
