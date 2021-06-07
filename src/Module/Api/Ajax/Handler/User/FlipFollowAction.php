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

declare(strict_types=1);

namespace Ampache\Module\Api\Ajax\Handler\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\User\Following\UserFollowTogglerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FlipFollowAction implements ActionInterface
{
    private ConfigContainerInterface $configContainer;

    private PrivilegeCheckerInterface $privilegeChecker;

    private ModelFactoryInterface $modelFactory;

    private UserFollowTogglerInterface $userFollowToggler;

    private UserFollowStateRendererInterface $userFollowStateRenderer;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PrivilegeCheckerInterface $privilegeChecker,
        ModelFactoryInterface $modelFactory,
        UserFollowTogglerInterface $userFollowToggler,
        UserFollowStateRendererInterface $userFollowStateRenderer
    ) {
        $this->configContainer         = $configContainer;
        $this->privilegeChecker        = $privilegeChecker;
        $this->modelFactory            = $modelFactory;
        $this->userFollowToggler       = $userFollowToggler;
        $this->userFollowStateRenderer = $userFollowStateRenderer;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];
        if (
            $this->privilegeChecker->check(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE)
        ) {
            $fuser = $this->modelFactory->createUser(
                (int) $request->getQueryParams()['user_id'] ?? 0
            );

            $followUserId = $fuser->getId();
            $userId       = $user->getId();

            if ($followUserId && $followUserId !== $userId) {
                $this->userFollowToggler->toggle(
                    $followUserId,
                    $userId
                );
                $results['button_follow_' . $followUserId] = $this->userFollowStateRenderer->render(
                    $followUserId,
                    $userId
                );
            }
        }

        return $results;
    }
}
