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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add `song_map` table for mapping song id's to external data sources
 */
final class Migration773001 extends AbstractMigration
{
    protected array $changelog = ['Convert `custom_favicon`, `custom_login_background`, `custom_login_logo` into system preferences'];

    public function migrate(): void
    {
        $this->updateDatabase("UPDATE `preference` SET `category` = 'system' WHERE `name` IN ('custom_favicon', 'custom_login_background', 'custom_login_logo');");
        $this->updateDatabase("UPDATE `preference` SET `subcategory` = 'interface' WHERE `name` IN ('custom_favicon', 'custom_login_background', 'custom_login_logo');");
        $this->updateDatabase("DELETE FROM `user_preference` WHERE `name` IN ('custom_favicon', 'custom_login_background', 'custom_login_logo') AND `user` != -1;");
    }
}
