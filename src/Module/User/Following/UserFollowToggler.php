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

namespace Ampache\Module\User\Following;

use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserFollowerRepositoryInterface;

final readonly class UserFollowToggler implements UserFollowTogglerInterface
{
    public function __construct(
        private UserFollowerRepositoryInterface $userFollowerRepository,
        private UserActivityPosterInterface $userActivityPoster
    ) {
    }

    /**
     * Let a user (un)follow another user
     */
    public function toggle(
        User $user,
        User $followingUser
    ): void {
        if ($this->userFollowerRepository->isFollowedBy($user, $followingUser)) {
            $this->userFollowerRepository->delete($user, $followingUser);
        } else {
            $this->userFollowerRepository->add($user, $followingUser);

            $this->userActivityPoster->post($followingUser->getId(), 'follow', 'user', $user->getId(), time());
        }
    }
}
