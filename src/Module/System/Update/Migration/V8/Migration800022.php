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

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Remove the obsolete `ajax_load` preference.
 *
 * It never was the page-load toggle its name claims: link clicks have always been intercepted
 * regardless of it, and since the History API navigation rework no JavaScript reads it at all.
 * What it actually did was disable the embedded web player in favour of a popup window that
 * modern browsers block, and silently switch off play-next and append.
 *
 * Rolling back to Ampache7 restores the preference: Ampache7 still lists `ajax_load` in
 * Preference::SYSTEM_LIST and set_defaults(), so its rollback re-inserts it, the same way it
 * already handles `webplayer_html5` (removed in Migration800020).
 */
final class Migration800022 extends AbstractMigration
{
    protected array $changelog = ['Remove the obsolete `ajax_load` preference'];

    public function migrate(): void
    {
        $this->updateDatabase("DELETE FROM `user_preference` WHERE `preference` IN (SELECT `id` FROM `preference` WHERE `name` = 'ajax_load');");
        $this->updateDatabase("DELETE FROM `preference` WHERE `name` = 'ajax_load';");
    }
}
