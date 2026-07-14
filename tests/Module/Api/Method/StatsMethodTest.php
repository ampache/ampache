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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Every result path runs through the Stats/Rating/Userflag/Random database statics, so only the
 * guards can be exercised without a database fixture.
 */
class StatsMethodTest extends MockeryTestCase
{
    private AlbumRepositoryInterface|MockInterface|null $albumRepository;
    private ArtistRepositoryInterface|MockInterface|null $artistRepository;
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?StatsMethod $subject;

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
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function gatedTypeProvider(): array
    {
        return [
            'video' => ['video', ConfigurationKeyEnum::ALLOW_VIDEO, 'Enable: video'],
            'podcast' => ['podcast', ConfigurationKeyEnum::PODCAST, 'Enable: podcast'],
            'podcast_episode' => ['podcast_episode', ConfigurationKeyEnum::PODCAST, 'Enable: podcast'],
        ];
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsErrorIfTypeIsNotSupported(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'error-result';

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::POPULAR_THRESHOLD)
            ->once()
            ->andReturn(10);

        $output->shouldReceive('error')
            ->with(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                'Bad Request: bogus',
                StatsMethod::ACTION,
                'type'
            )
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
                ['type' => 'bogus', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'gatedTypeProvider')]
    public function testHandleThrowsIfTypeIsDisabled(string $type, string $configKey, string $message): void
    {
        $apiVersion = 8;

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::POPULAR_THRESHOLD)
            ->once()
            ->andReturn(10);
        $this->configContainer->shouldReceive('get')
            ->with($configKey)
            ->once()
            ->andReturnFalse();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage($message);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => $type, 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfTypeIsMissing(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'type'));

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
        $this->albumRepository  = $this->mock(AlbumRepositoryInterface::class);
        $this->artistRepository = $this->mock(ArtistRepositoryInterface::class);
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory     = $this->mock(ModelFactoryInterface::class);

        $this->subject = new StatsMethod(
            $this->albumRepository,
            $this->artistRepository,
            $this->configContainer,
            $this->modelFactory
        );
    }
}
