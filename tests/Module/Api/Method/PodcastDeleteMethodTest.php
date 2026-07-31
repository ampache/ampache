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
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PodcastDeleteMethodTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private GatekeeperInterface&MockObject $gatekeeper;
    private ApiOutputInterface&MockObject $output;
    private PodcastDeleterInterface&MockObject $podcastDeleter;
    private PodcastRepositoryInterface&MockObject $podcastRepository;
    private PrivilegeCheckerInterface $privilegeChecker;
    private ResponseInterface&MockObject $response;
    private PodcastDeleteMethod $subject;
    private User&MockObject $user;

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
    public function testHandleDeletes(int $apiVersion): void
    {
        $userId    = 666;
        $podcastId = 42;
        $result    = 'some-result';

        $podcast = $this->createMock(Podcast::class);
        $stream  = $this->createMock(StreamInterface::class);

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn($podcast);

        $this->privilegeChecker->expects(static::once())
            ->method('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $userId)
            ->willReturn(true);

        $this->podcastDeleter->expects(static::once())
            ->method('delete')
            ->with($podcast);

        $this->response->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $stream->expects(static::once())
            ->method('write')
            ->with($result);

        $this->output->expects(static::once())
            ->method('success')
            ->with($apiVersion, sprintf('podcast %d deleted', $podcastId))
            ->willReturn($result);

        /** @noinspection PhpMissingArrayKeyInspection */
        self::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                ['filter' => (string) $podcastId],
                $this->user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfAccessIsDenied(int $apiVersion): void
    {
        // a failed access-level check reports 4742/'account', not the 4703/'system' of a config gate
        static::expectException(AccessFailedException::class);
        static::expectExceptionMessage(sprintf('Require: %s', AccessLevelEnum::MANAGER->value));

        $userId = 666;

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->privilegeChecker->expects(static::once())
            ->method('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $userId)
            ->willReturn(false);

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            [],
            $this->user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfFilterIsMissing(int $apiVersion): void
    {
        static::expectException(RequestParamMissingException::class);
        static::expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'filter'));

        $userId = 666;

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->privilegeChecker->expects(static::once())
            ->method('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $userId)
            ->willReturn(true);

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            [],
            $this->user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfPodcastsNotEnabled(int $apiVersion): void
    {
        static::expectException(AccessDeniedException::class);
        static::expectExceptionMessage('Enable: podcast');

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('');

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            [],
            $this->user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfPodcastWasNotFound(int $apiVersion): void
    {
        $userId    = 666;
        $podcastId = 42;

        static::expectException(ResultEmptyException::class);
        static::expectExceptionMessage((string) $podcastId);

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn(null);

        $this->privilegeChecker->expects(static::once())
            ->method('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $userId)
            ->willReturn(true);

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            ['filter' => (string) $podcastId],
            $this->user,
            $apiVersion
        );
    }

    protected function setUp(): void
    {
        $this->podcastDeleter    = $this->createMock(PodcastDeleterInterface::class);
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);
        $this->privilegeChecker  = $this->createMock(PrivilegeCheckerInterface::class);
        $this->podcastRepository = $this->createMock(PodcastRepositoryInterface::class);

        $this->subject = new PodcastDeleteMethod(
            $this->podcastDeleter,
            $this->configContainer,
            $this->privilegeChecker,
            $this->podcastRepository,
        );

        $this->gatekeeper = $this->createMock(GatekeeperInterface::class);
        $this->response   = $this->createMock(ResponseInterface::class);
        $this->output     = $this->createMock(ApiOutputInterface::class);
        $this->user       = $this->createMock(User::class);
    }
}
