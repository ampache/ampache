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
 */

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add the `mini_player` preference used to lock a user into the mini player page (m.php).
 * It's an admin level preference so a locked user can't turn it off from a page they can't reach.
 */
final class Migration800021 extends AbstractMigration
{
    protected array $changelog = ['Add the `mini_player` preference to lock a user into the mini player interface'];

    public function migrate(): void
    {
        $this->updatePreferences('mini_player', 'Lock this user into the mini player interface', '0', AccessLevelEnum::ADMIN->value, 'boolean', 'interface', 'theme');
    }
}
