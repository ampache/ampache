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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\User;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\User\Following\UserFollowTogglerInterface;

final readonly class UserAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private UserFollowTogglerInterface $followToggler,
        private UserFollowStateRendererInterface $userFollowStateRenderer,
        private PrivilegeCheckerInterface $privilegeChecker,
        private ConfigContainerInterface $configContainer,
    ) {
    }

    public function handle(User $user): void
    {
        $results = array();
        $action  = $this->requestParser->getFromRequest('action');
        $user_id = (int)$this->requestParser->getFromRequest('user_id');

        // Switch on the actions
        switch ($action) {
            case 'flip_follow':
                if (
                    $this->privilegeChecker->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) &&
                    $this->configContainer->isFeatureEnabled('sociable')
                ) {
                    $fuser = new User($user_id);
                    if ($fuser->id > 0 && $user_id !== $user->getId()) {
                        $this->followToggler->toggle(
                            $fuser,
                            $user
                        );
                        $results['button_follow_' . $user_id] = $this->userFollowStateRenderer->render(
                            $fuser,
                            $user
                        );
                    }
                }
                break;
            default:
                $results['rfc3514'] = '0x1';
                break;
        } // switch on action;

        // We always do this
        echo xoutput_from_array($results);
    }
}
