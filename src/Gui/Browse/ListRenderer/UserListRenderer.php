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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\User\UserRowView;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\System\Core;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Repository\Model\User;
use Override;

/**
 * The user browse, serving both the user list and a user's followers.
 *
 * Its follow column was declared by the header on one condition and emitted by the row on another, so a
 * request with no session user lost a cell the header had already counted. It also had no empty state.
 */
final class UserListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly UserFollowStateRendererInterface $userFollowStateRenderer,
    ) {}

    /**
     * @return list<array{class: string, label: string, sort: null|string}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_username essential persist', 'label' => T_('Username'), 'sort' => 'username'],
            ['class' => 'cel_lastseen', 'label' => T_('Last Seen'), 'sort' => 'last_seen'],
            ['class' => 'cel_registrationdate', 'label' => T_('Registration Date'), 'sort' => 'create_date'],
        ];

        if ($this->showActivity()) {
            $columns[] = ['class' => 'cel_activity', 'label' => T_('Activity'), 'sort' => null];
        }

        if ($this->showLastIp()) {
            $columns[] = ['class' => 'cel_lastip', 'label' => T_('Last IP'), 'sort' => null];
        }

        if ($this->showFollow()) {
            $columns[] = ['class' => 'cel_follow essential', 'label' => T_('Following'), 'sort' => null];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Action'), 'sort' => null];
        $columns[] = ['class' => 'cel_online', 'label' => T_('Online'), 'sort' => null];

        return $columns;
    }

    /**
     * @return list<User>
     */
    public function getUsers(): array
    {
        $users = [];
        foreach ($this->getObjectIds() as $objectId) {
            $user = new User($objectId);
            if ($user->isNew()) {
                continue;
            }

            $users[] = $user;
        }

        return $users;
    }

    public function renderRow(User $user): string
    {
        $viewer = Core::get_global('user');

        return new UserRowView(
            $this->configContainer->getWebPath('/client'),
            $this->configContainer->getWebPath('/admin'),
            $user,
            ($viewer instanceof User) ? $viewer : null,
            $this->userFollowStateRenderer,
            $this->showActivity(),
            $this->showLastIp(),
            $this->showFollow(),
            $this->isSociable(),
            $this->mayAdminister(),
            $this->mayAdminister()
        )->render();
    }

    /**
     * The follow cell needs a session user to compare against, so the header must ask for one too.
     */
    public function showFollow(): bool
    {
        return $this->isSociable() && Core::get_global('user') instanceof User;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/users.phtml');
    }

    private function isSociable(): bool
    {
        return (bool) $this->configContainer->get('sociable') && $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    private function mayAdminister(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
    }

    private function showActivity(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
    }

    private function showLastIp(): bool
    {
        return $this->showActivity() && (bool) $this->configContainer->get('track_user_ip');
    }
}
