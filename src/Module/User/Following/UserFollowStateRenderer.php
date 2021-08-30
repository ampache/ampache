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

namespace Ampache\Module\User\Following;

use Ampache\Module\Api\Ajax;
use Ampache\Repository\UserFollowerRepositoryInterface;

final class UserFollowStateRenderer implements UserFollowStateRendererInterface
{
    private UserFollowerRepositoryInterface $userFollowerRepository;

    public function __construct(
        UserFollowerRepositoryInterface $userFollowerRepository
    ) {
        $this->userFollowerRepository = $userFollowerRepository;
    }

    /**
     * Get html code to display the follow/unfollow link
     *
     * @param integer $userId
     * @param integer $foreignUserId
     * @return string
     */
    public function render(
        int $userId,
        int $foreignUserId
    ): string {
        if ($userId === $foreignUserId) {
            return '';
        }

        $followed       = $this->userFollowerRepository->isFollowedBy($userId, $foreignUserId);
        $followersCount = count($this->userFollowerRepository->getFollowers($userId));

        $html = sprintf('<span id=\'button_follow_%s\' class=\'followbtn\'>', $userId);
        $html .= Ajax::text(
            '?page=user&action=flip_follow&user_id=' . $userId,
            ($followed ? T_('Unfollow') : T_('Follow')) . ' (' . $followersCount . ')',
            'flip_follow_' . $userId
        );
        $html .= "</span>";

        return $html;
    }
}
