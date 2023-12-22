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

use Ampache\Module\System\LegacyLogger;
use Ampache\Module\User\Activity\TypeHandler\ActivityTypeHandlerMapperInterface;
use Psr\Log\LoggerInterface;

final class UserActivityPoster implements UserActivityPosterInterface
{
    private ActivityTypeHandlerMapperInterface $activityTypeHandlerMapper;

    private LoggerInterface $logger;

    public function __construct(
        ActivityTypeHandlerMapperInterface $activityTypeHandlerMapper,
        LoggerInterface $logger
    ) {
        $this->activityTypeHandlerMapper = $activityTypeHandlerMapper;
        $this->logger                    = $logger;
    }

    /**
     * Registers a certain user action for certain object types
     */
    public function post(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date
    ): void {
        $this->logger->debug(
            sprintf('post_activity: %s %s by user: %d: {%d}', $action, $objectType, $userId, $objectId),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $handler = $this->activityTypeHandlerMapper->map($objectType);

        $handler->registerActivity(
            $objectId,
            $objectType,
            $action,
            $userId,
            $date
        );
    }
}
