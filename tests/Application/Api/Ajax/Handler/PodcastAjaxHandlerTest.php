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
 */

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PodcastAjaxHandlerTest extends TestCase
{
    private RequestParserInterface&MockObject $requestParser;

    private PodcastSyncerInterface&MockObject $podcastSyncer;

    private PodcastRepositoryInterface&MockObject $podcastRepository;

    private PodcastEpisodeRepositoryInterface&MockObject $podcastEpisodeRepository;

    private PrivilegeCheckerInterface&MockObject $privilegeChecker;

    private LoggerInterface&MockObject $logger;

    private PodcastAjaxHandler $subject;

    private User&MockObject $user;

    protected function setUp(): void
    {
        $this->requestParser            = $this->createMock(RequestParserInterface::class);
        $this->podcastSyncer            = $this->createMock(PodcastSyncerInterface::class);
        $this->podcastRepository        = $this->createMock(PodcastRepositoryInterface::class);
        $this->podcastEpisodeRepository = $this->createMock(PodcastEpisodeRepositoryInterface::class);
        $this->privilegeChecker         = $this->createMock(PrivilegeCheckerInterface::class);
        $this->logger                   = $this->createMock(LoggerInterface::class);

        $this->subject = new PodcastAjaxHandler(
            $this->requestParser,
            $this->podcastSyncer,
            $this->podcastRepository,
            $this->podcastEpisodeRepository,
            $this->privilegeChecker,
            $this->logger,
        );

        $this->user = $this->createMock(User::class);
    }

    public function testHandleFailsIfAccessIsDenied(): void
    {
        $username = 'some-name';

        $this->privilegeChecker->expects(static::once())
            ->method('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(false);

        $this->user->expects(static::once())
            ->method('getUsername')
            ->willReturn($username);

        $this->logger->expects(static::once())
            ->method('warning')
            ->with(
                sprintf('User `%s` attempted to sync podcast', $username),
                [LegacyLogger::CONTEXT_TYPE => $this->subject::class]
            );

        $this->subject->handle($this->user);
    }
}
