<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\User\Activity;

use Ampache\MockeryTestCase;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\User\Activity\TypeHandler\ActivityTypeHandlerInterface;
use Ampache\Module\User\Activity\TypeHandler\ActivityTypeHandlerMapperInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class UserActivityPosterTest extends MockeryTestCase
{
    /** @var MockInterface|ActivityTypeHandlerMapperInterface|null */
    private MockInterface $activityTypeHandlerMapper;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    private ?UserActivityPoster $subject;

    protected function setUp(): void
    {
        $this->activityTypeHandlerMapper = $this->mock(ActivityTypeHandlerMapperInterface::class);
        $this->logger                    = $this->mock(LoggerInterface::class);

        $this->subject = new UserActivityPoster(
            $this->activityTypeHandlerMapper,
            $this->logger
        );
    }

    public function testPostRegistersAction(): void
    {
        $userId     = 666;
        $action     = 'some-action';
        $objectType = 'some-object-type';
        $objectId   = 42;
        $date       = 123;

        $handler = $this->mock(ActivityTypeHandlerInterface::class);

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('post_activity: %s %s by user: %d: {%d}', $action, $objectType, $userId, $objectId),
                [LegacyLogger::CONTEXT_TYPE => UserActivityPoster::class]
            )
            ->once();

        $this->activityTypeHandlerMapper->shouldReceive('map')
            ->with($objectType)
            ->once()
            ->andReturn($handler);

        $handler->shouldReceive('registerActivity')
            ->with(
                $objectId,
                $objectType,
                $action,
                $userId,
                $date
            )
            ->once();

        $this->subject->post(
            $userId,
            $action,
            $objectType,
            $objectId,
            $date
        );
    }
}
