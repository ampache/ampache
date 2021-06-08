<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Tag;

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Tag\TagCreatorInteface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class AddTageByNameAction implements ActionInterface
{
    private TagCreatorInteface $tagCreator;

    private PrivilegeCheckerInterface $privilegeChecker;

    private LoggerInterface $logger;

    public function __construct(
        TagCreatorInteface $tagCreator,
        PrivilegeCheckerInterface $privilegeChecker,
        LoggerInterface $logger
    ) {
        $this->tagCreator       = $tagCreator;
        $this->privilegeChecker = $privilegeChecker;
        $this->logger           = $logger;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        if (!$this->privilegeChecker->check(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)) {
            $this->logger->critical(
                $user->username . ' attempted to add new tag',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return [];
        }

        $this->logger->debug(
            'Adding new tag by name...',
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $queryParams = $request->getQueryParams();

        $this->tagCreator->add(
            $queryParams['type'] ?? '',
            (int) ($queryParams['object_id'] ?? 0),
            $queryParams['tag_name'] ?? ''
        );

        return [];
    }
}
