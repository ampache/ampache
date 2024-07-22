<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PodcastDeleteMethodTest extends TestCase
{
    private PodcastDeleterInterface&MockObject $podcastDeleter;

    private ConfigContainerInterface&MockObject $configContainer;

    private PrivilegeCheckerInterface $privilegeChecker;

    private PodcastRepositoryInterface&MockObject $podcastRepository;

    private PodcastDeleteMethod $subject;

    private GatekeeperInterface&MockObject $gatekeeper;

    private ResponseInterface&MockObject $response;

    private ApiOutputInterface&MockObject $output;

    private User&MockObject $user;

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

    public function testHandleThrowsIfPodcastsNotEnabled(): void
    {
        static::expectException(AccessDeniedException::class);
        static::expectExceptionMessage('Enable: podcast');

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('');

        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            [],
            $this->user
        );
    }

    public function testHandleThrowsIfAccessIsDenied(): void
    {
        static::expectException(AccessDeniedException::class);
        static::expectExceptionMessage('Access denied');

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
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER, $userId)
            ->willReturn(false);

        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            [],
            $this->user
        );
    }

    public function testHandleThrowsIfFilterIsMissing(): void
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
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER, $userId)
            ->willReturn(true);

        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            [],
            $this->user
        );
    }

    public function testHandleThrowsIfPodcastWasNotFound(): void
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
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER, $userId)
            ->willReturn(true);

        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            ['filter' => (string) $podcastId],
            $this->user
        );
    }

    public function testHandleDeletes(): void
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
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER, $userId)
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
            ->with(sprintf('podcast %d deleted', $podcastId))
            ->willReturn($result);

        static::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                ['filter' => (string) $podcastId],
                $this->user
            )
        );
    }
}
