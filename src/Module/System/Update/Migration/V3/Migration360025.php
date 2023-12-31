<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\System\Update\Migration\V3;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add options to enable HTML5 / Flash on web players
 */
final class Migration360025 extends AbstractMigration
{
    protected array $changelog = ['Add options to enable HTML5 / Flash on web players'];

    public function migrate(): void
    {
        $this->updatePreferences('webplayer_flash', 'Authorize Flash Web Player(s)', '1', 25, 'boolean', 'streaming');
        $this->updatePreferences('webplayer_html5', 'Authorize HTML5 Web Player(s)', '1', 25, 'boolean', 'streaming');
    }
}
