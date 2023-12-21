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

use Ampache\Module\Authorization\Access;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\User;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\User\Following\UserFollowTogglerInterface;

final class UserAjaxHandler implements AjaxHandlerInterface
{
    private RequestParserInterface $requestParser;

    private UserFollowTogglerInterface $followToggler;

    private UserFollowStateRendererInterface $userFollowStateRenderer;

    public function __construct(
        RequestParserInterface $requestParser,
        UserFollowTogglerInterface $followToggler,
        UserFollowStateRendererInterface $userFollowStateRenderer
    ) {
        $this->requestParser           = $requestParser;
        $this->followToggler           = $followToggler;
        $this->userFollowStateRenderer = $userFollowStateRenderer;
    }

    public function handle(): void
    {
        $results = array();
        $action  = $this->requestParser->getFromRequest('action');
        $user_id = (int)$this->requestParser->getFromRequest('user_id');

        // Switch on the actions
        switch ($action) {
            case 'flip_follow':
                if (Access::check('interface', 25) && AmpConfig::get('sociable')) {
                    $fuser = new User($user_id);
                    if ($fuser->id > 0 && $user_id !== (int) Core::get_global('user')->id) {
                        $this->followToggler->toggle(
                            $user_id,
                            Core::get_global('user')->getId()
                        );
                        $results['button_follow_' . $user_id] = $this->userFollowStateRenderer->render(
                            $user_id,
                            Core::get_global('user')->getId()
                        );
                    }
                }
                break;
            default:
                $results['rfc3514'] = '0x1';
                break;
        } // switch on action;

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
