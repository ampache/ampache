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

namespace Ampache\Gui\Broadcast;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\User;
use Override;

/**
 * One row of the broadcasts browse.
 */
final class BroadcastRowView extends AbstractView
{
    public function __construct(
        private readonly Broadcast $broadcast,
        private readonly bool $directPlay,
    ) {}

    public function getBroadcast(): Broadcast
    {
        return $this->broadcast;
    }

    public function getDeleteUrl(): string
    {
        return AmpConfig::get_web_path('/client') . '/broadcast.php?action=show_delete&id=' . $this->broadcast->getId();
    }

    public function isDirectPlayEnabled(): bool
    {
        return $this->directPlay;
    }

    /**
     * Only a manager is offered the edit and delete buttons.
     */
    public function mayManage(): bool
    {
        return $this->broadcast->getId() !== 0
            && Core::get_global('user') instanceof User
            && Core::get_global('user')->has_access(AccessLevelEnum::MANAGER);
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('broadcast_row.phtml');
    }
}
