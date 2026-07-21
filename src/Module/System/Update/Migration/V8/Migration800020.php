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
 * Remove the obsolete `webplayer_html5` preference.
 *
 * HTML5 is the only remaining web player. Since the Flash fallback was dropped in Migration800011 the
 * disabled state of this preference left jPlayer with no playback solution at all, so it no longer
 * selects between players and the web player always uses the HTML5 solution.
 */
final class Migration800020 extends AbstractMigration
{
    protected array $changelog = ['Remove the obsolete `webplayer_html5` preference'];

    public function migrate(): void
    {
        $this->updateDatabase("DELETE FROM `user_preference` WHERE `preference` IN (SELECT `id` FROM `preference` WHERE `name` = 'webplayer_html5');");
        $this->updateDatabase("DELETE FROM `preference` WHERE `name` = 'webplayer_html5';");
    }
}
