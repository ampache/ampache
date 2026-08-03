<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Module\User\Following\UserFollowTogglerInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

final class ToggleFollow3Method implements MethodInterface
{
    public const string ACTION = 'toggle_follow';

    public function __construct(
        private UserFollowTogglerInterface $userFollowToggler,
    ) {}

    /**
     * toggle_follow
     * This follow/unfollow a user
     *
     * @param array{
     *     username: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 3 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            if (!empty($username)) {
                $leader = User::get_from_username($username);
                if ($leader instanceof User) {
                    $this->userFollowToggler->toggle(
                        $leader,
                        $user
                    );
                    ob_end_clean();
                    echo Xml3_Data::success();
                }
            } else {
                debug_event(self::class, 'Username to toggle required on follow function call.', 1);
            }
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }

        return $response;
    }
}
