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
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Application\Stats;

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

final class ShowUserAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_user';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private UserActivityRepositoryInterface $useractivityRepository;

    private UserActivityRendererInterface $userActivityRenderer;

    private UserFollowerRepositoryInterface $userFollowerRepository;

    private UserFollowStateRendererInterface $userFollowStateRenderer;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        UserActivityRepositoryInterface $useractivityRepository,
        UserActivityRendererInterface $userActivityRenderer,
        UserFollowerRepositoryInterface $userFollowerRepository,
        UserFollowStateRendererInterface $userFollowStateRenderer
    ) {
        $this->ui                      = $ui;
        $this->modelFactory            = $modelFactory;
        $this->useractivityRepository  = $useractivityRepository;
        $this->userActivityRenderer    = $userActivityRenderer;
        $this->userFollowerRepository  = $userFollowerRepository;
        $this->userFollowStateRenderer = $userFollowStateRenderer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        define('TABLE_RENDERED', 1);

        // Temporary workaround to avoid sorting on custom base requests
        define('NO_BROWSE_SORTING', true);

        $userId = (int) $request->getQueryParams()['user_id'] ?? 0;

        $client = $this->modelFactory->createUser($userId);

        $this->ui->show(
            'show_user.inc.php',
            [
                'client' => $client,
                'activities' => $this->useractivityRepository->getActivities($userId),
                'followers' => $this->userFollowerRepository->getFollowers($userId),
                'following' => $this->userFollowerRepository->getFollowing($userId),
                'userFollowStateRenderer' => $this->userFollowStateRenderer,
                'userActivityRenderer' => $this->userActivityRenderer
            ]
        );

        show_table_render(false, true);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
