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
 */

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add user preference `webplayer_removeplayed`, Remove tracks before the current playlist item in the webplayer when played
 */
final class Migration700001 extends AbstractMigration
{
    protected array $changelog = ['Add user preferences to show/hide menus in the sidebar and the switcher arrows.'];

    public function migrate(): void
    {
        $this->updatePreferences('sidebar_hide_switcher', 'Hide sidebar switcher arrows', '0', 25, 'boolean', 'interface', 'home');
        $this->updatePreferences('sidebar_hide_browse', 'Hide the Browse menu in the sidebar', '0', 25, 'boolean', 'interface', 'home');
        $this->updatePreferences('sidebar_hide_dashboard', 'Hide the Dashboard menu in the sidebar', '0', 25, 'boolean', 'interface', 'home');
        $this->updatePreferences('sidebar_hide_video', 'Hide the Video menu in the sidebar', '0', 25, 'boolean', 'interface', 'home');
        $this->updatePreferences('sidebar_hide_search', 'Hide the Search menu in the sidebar', '0', 25, 'boolean', 'interface', 'home');
        $this->updatePreferences('sidebar_hide_playlist', 'Hide the Playlist menu in the sidebar', '0', 25, 'boolean', 'interface', 'home');
        $this->updatePreferences('sidebar_hide_information', 'Hide the Information menu in the sidebar', '0', 25, 'boolean', 'interface', 'home');
    }
}
