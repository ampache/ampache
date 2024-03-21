<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Stats;

use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\User\Activity\UserActivityRendererInterface;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserFollowerRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowUserAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_user';

    public function __construct(
        private UiInterface $ui,
        private LoggerInterface $logger,
        private ModelFactoryInterface $modelFactory,
        private UserActivityRepositoryInterface $useractivityRepository,
        private UserActivityRendererInterface $userActivityRenderer,
        private UserFollowerRepositoryInterface $userFollowerRepository,
        private UserFollowStateRendererInterface $userFollowStateRenderer,
        private LibraryItemLoaderInterface $libraryItemLoader,
    ) {
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        define('TABLE_RENDERED', 1);

        // Temporary workaround to avoid sorting on custom base requests
        define('NO_BROWSE_SORTING', true);

        $userId = (int)($request->getQueryParams()['user_id'] ?? 0);
        $client = $this->modelFactory->createUser($userId);
        if ($client->isNew()) {
            $this->logger->warning(
                'Requested a user that does not exist',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            $this->ui->show(
                'show_user.inc.php',
                [
                    'client' => $client,
                    'activities' => $this->useractivityRepository->getActivities($userId),
                    'followers' => $this->userFollowerRepository->getFollowers($client),
                    'following' => $this->userFollowerRepository->getFollowing($client),
                    'userFollowStateRenderer' => $this->userFollowStateRenderer,
                    'userActivityRenderer' => $this->userActivityRenderer,
                    'libraryItemLoader' => $this->libraryItemLoader,
                ]
            );
            show_table_render(false, true);
        }
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
