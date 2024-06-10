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

namespace Ampache\Module\System\Update\Migration\V5;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add `show_album_artist` and `show_artist` preferences to show/hide Sidebar Browse menu links. (Fallback to Album Artist if both disabled)
 */
final class Migration530015 extends AbstractMigration
{
    protected array $changelog = [
        'Add `show_album_artist` and `show_artist` preferences to show/hide Sidebar Browse menu links',
        'Fallback to Album Artist if both disabled'
    ];

    public function migrate(): void
    {
        $this->updatePreferences('show_album_artist', 'Show \'Album Artists\' link in the main sidebar', '1', 25, 'boolean', 'interface', 'theme');
        $this->updatePreferences('show_artist', 'Show \'Artists\' link in the main sidebar', '0', 25, 'boolean', 'interface', 'theme');
    }
}
