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

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

class Random8MethodTest extends MockeryTestCase
{
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private PodcastEpisodeRepositoryInterface|MockInterface|null $podcastEpisodeRepository;
    private ?Random8Method $subject;
    private VideoRepositoryInterface|MockInterface|null $videoRepository;

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
    public function testHandleRedirectsToRandomPodcastEpisode(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $episode    = $this->mock(Podcast_Episode::class);

        $userId    = 666;
        $episodeId = 42;
        $url       = 'http://ampache.local/play/index.php?action=play&podcast_episode=1';

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->andReturn($userId);

        $this->podcastEpisodeRepository->shouldReceive('getRandom')
            ->with($userId, 1)
            ->once()
            ->andReturn([$episodeId]);

        $this->modelFactory->shouldReceive('createPodcastEpisode')
            ->with($episodeId)
            ->once()
            ->andReturn($episode);

        $episode->shouldReceive('play_url')
            ->with('&client=api', 'api', false, $userId, $user->streamtoken)
            ->once()
            ->andReturn($url);

        $response->shouldReceive('withStatus')
            ->with(302)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Location', $url)
            ->once()
            ->andReturnSelf();

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['type' => 'podcast_episode'],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleRedirectsToRandomVideo(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $video      = $this->mock(Video::class);

        $userId  = 666;
        $videoId = 42;
        $url     = 'http://ampache.local/play/index.php?action=play&video=1';

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->andReturn($userId);

        $this->videoRepository->shouldReceive('getRandom')
            ->with($userId, 1)
            ->once()
            ->andReturn([$videoId]);

        $this->modelFactory->shouldReceive('createVideo')
            ->with($videoId)
            ->once()
            ->andReturn($video);

        $video->shouldReceive('play_url')
            ->with('&client=api', 'api', false, $userId, $user->streamtoken)
            ->once()
            ->andReturn($url);

        $response->shouldReceive('withStatus')
            ->with(302)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Location', $url)
            ->once()
            ->andReturnSelf();

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['type' => 'video'],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsExceptionIfNoRandomEpisodeIsAvailable(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $userId = 666;

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->andReturn($userId);

        $this->podcastEpisodeRepository->shouldReceive('getRandom')
            ->with($userId, 1)
            ->once()
            ->andReturn([]);

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage('podcast_episode');

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => 'podcast_episode'],
            $user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsExceptionIfPlayUrlIsEmpty(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $video      = $this->mock(Video::class);

        $userId  = 666;
        $videoId = 42;

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->andReturn($userId);

        $this->videoRepository->shouldReceive('getRandom')
            ->with($userId, 1)
            ->once()
            ->andReturn([$videoId]);

        $this->modelFactory->shouldReceive('createVideo')
            ->with($videoId)
            ->once()
            ->andReturn($video);

        $video->shouldReceive('play_url')
            ->with('&client=api', 'api', false, $userId, $user->streamtoken)
            ->once()
            ->andReturn('');

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $videoId);

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => 'video'],
            $user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsExceptionIfTypeIsInvalid(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'type'));

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => 'genre'],
            $user,
            $apiVersion
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->modelFactory             = $this->mock(ModelFactoryInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);
        $this->videoRepository          = $this->mock(VideoRepositoryInterface::class);

        $this->subject = new Random8Method(
            $this->modelFactory,
            $this->podcastEpisodeRepository,
            $this->videoRepository
        );
    }
}
