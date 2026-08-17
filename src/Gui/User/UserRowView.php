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

namespace Ampache\Gui\User;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Repository\Model\User;
use Override;

/**
 * One row of the user browse.
 */
final class UserRowView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $adminPath,
        private readonly User $user,
        private readonly ?User $viewer,
        private readonly UserFollowStateRendererInterface $userFollowStateRenderer,
        private readonly bool $showActivity,
        private readonly bool $showLastIp,
        private readonly bool $showFollow,
        private readonly bool $showPrivateMessage,
        private readonly bool $mayAdminister,
        private readonly bool $showFullname,
    ) {}

    public function getAdminPath(): string
    {
        return $this->adminPath;
    }

    public function getAvatar(): string
    {
        return $this->user->get_f_avatar('f_avatar_mini');
    }

    public function getCreateDate(): string
    {
        return ($this->user->create_date) ? get_datetime($this->user->create_date) : T_('Unknown');
    }

    public function getFollowState(): string
    {
        return ($this->viewer instanceof User)
            ? $this->userFollowStateRenderer->render($this->user, $this->viewer)
            : '';
    }

    public function getFullname(): string
    {
        return (string) $this->user->fullname;
    }

    public function getIpHistory(): string
    {
        return $this->user->get_ip_history();
    }

    public function getLastSeen(): string
    {
        return ($this->user->last_seen) ? get_datetime($this->user->last_seen) : T_('Never');
    }

    /**
     * The online cell is a colored block, so its state is a class rather than any content.
     */
    public function getOnlineClass(): string
    {
        if ($this->user->is_logged_in() && $this->user->is_online()) {
            return 'user_online';
        }

        return ($this->isDisabled()) ? 'user_disabled' : 'user_offline';
    }

    public function getUsage(): string
    {
        return $this->user->get_f_usage();
    }

    public function getUserId(): int
    {
        return $this->user->getId();
    }

    public function getUsername(): string
    {
        return (string) $this->user->username;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isDisabled(): bool
    {
        return $this->user->disabled;
    }

    public function mayAdminister(): bool
    {
        return $this->mayAdminister;
    }

    public function showActivity(): bool
    {
        return $this->showActivity;
    }

    public function showFollow(): bool
    {
        return $this->showFollow;
    }

    public function showFullname(): bool
    {
        return $this->showFullname || $this->user->fullname_public;
    }

    public function showLastIp(): bool
    {
        return $this->showLastIp;
    }

    public function showPrivateMessage(): bool
    {
        return $this->showPrivateMessage;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('user_row.phtml');
    }
}
