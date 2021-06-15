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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Tag\TagDeleterInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class DeleteAction implements ActionInterface
{
    private PrivilegeCheckerInterface $privilegeChecker;

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    private TagDeleterInterface $tagDeleter;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        TagDeleterInterface $tagDeleter
    ) {
        $this->privilegeChecker = $privilegeChecker;
        $this->modelFactory     = $modelFactory;
        $this->logger           = $logger;
        $this->configContainer  = $configContainer;
        $this->tagDeleter       = $tagDeleter;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        if (!$this->privilegeChecker->check(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)) {
            $this->logger->critical(
                $user->username . ' attempted to delete tag',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return [];
        }

        $this->logger->debug(
            'Deleting tag...',
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $tag = $this->modelFactory->createTag(
            (int) ($request->getQueryParams()['tag_id'] ?? 0)
        );

        $this->tagDeleter->delete($tag);

        header(
            sprintf(
                'Location: %s/browse.php?action=tag&type=song',
                $this->configContainer->getWebPath()
            )
        );

        return [];
    }
}
