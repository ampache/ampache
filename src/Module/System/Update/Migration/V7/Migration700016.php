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

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration700016 extends AbstractMigration
{
    protected array $changelog = [
        'Put sidebar preferences into their own category',
        'Add user preferences to order menu sections in the sidebar',
    ];

    public function migrate(): void
    {
        // separate sidebar preferences into their own category
        Dba::write("UPDATE `preference` SET `category` = 'sidebar' WHERE name IN ('sidebar_light', 'show_album_artist', 'show_artist', 'sidebar_hide_switcher', 'sidebar_hide_browse', 'sidebar_hide_dashboard', 'sidebar_hide_video', 'sidebar_hide_search', 'sidebar_hide_playlist', 'sidebar_hide_information')");
        // allow reordering the sidebar without relying on CSS
        $this->updatePreferences('sidebar_order_browse', 'Hide sidebar switcher arrows', 10, 25, 'integer', 'interface', 'sidebar');
        $this->updatePreferences('sidebar_order_dashboard', 'Hide the Browse menu in the sidebar', 15, 25, 'integer', 'interface', 'sidebar');
        $this->updatePreferences('sidebar_order_information', 'Hide the Dashboard menu in the sidebar', 20, 25, 'integer', 'interface', 'sidebar');
        $this->updatePreferences('sidebar_order_playlist', 'Hide the Video menu in the sidebar', 30, 25, 'integer', 'interface', 'sidebar');
        $this->updatePreferences('sidebar_order_search', 'Hide the Search menu in the sidebar', 40, 25, 'integer', 'interface', 'sidebar');
        $this->updatePreferences('sidebar_order_video', 'Hide the Playlist menu in the sidebar', 60, 25, 'integer', 'interface', 'home');
    }
}
