<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Repository\UserFollowerRepositoryInterface;

final class UserFollowToggler implements UserFollowTogglerInterface
{
    private UserFollowerRepositoryInterface $userFollowerRepository;

    private UserActivityPosterInterface $userActivityPoster;

    public function __construct(
        UserFollowerRepositoryInterface $userFollowerRepository,
        UserActivityPosterInterface $userActivityPoster
    ) {
        $this->userFollowerRepository = $userFollowerRepository;
        $this->userActivityPoster     = $userActivityPoster;
    }

    public function toggle(
        int $userId,
        int $followingUserId
    ): void {
        if ($this->userFollowerRepository->isFollowedBy($userId, $followingUserId)) {
            $this->userFollowerRepository->delete($userId, $followingUserId);
        } else {
            $this->userFollowerRepository->add($userId, $followingUserId);

            $this->userActivityPoster->post($followingUserId, 'follow', 'user', $userId, time());
        }
    }
}
