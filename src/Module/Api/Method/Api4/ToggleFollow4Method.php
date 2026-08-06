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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\User\Following\UserFollowTogglerInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

final class ToggleFollow4Method implements MethodInterface
{
    public const string ACTION = 'toggle_follow';

    public function __construct(
        private UserFollowTogglerInterface $userFollowToggler,
    ) {}

    /**
     * toggle_follow
     * MINIMUM_API_VERSION=380001
     *
     * This will follow/unfollow a user
     *
     * username = (string) $username
     *
     * @param array{
     *     username: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!AmpConfig::get('sociable')) {
            Api4::message('error', 'Access Denied: social features are not enabled.', '400', $input['api_format']);

            return $response;
        }
        if (!Api4::check_parameter($input, ['username'], self::ACTION)) {
            return $response;
        }
        $username = $input['username'];
        if (!empty($username)) {
            $leader = User::get_from_username($username);
            if ($leader instanceof User) {
                $this->userFollowToggler->toggle(
                    $leader,
                    $user
                );
                ob_end_clean();
                Api4::message('success', 'follow toggled for: ' . $user->id, null, $input['api_format']);

                return $response;
            }

            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api4::message('error', 'User `' . $username . '` cannot be found.', '400', $input['api_format']);

            return $response;
        }

        Api4::message('error', 'Invalid request', '405', $input['api_format']);

        return $response;
    }
}
