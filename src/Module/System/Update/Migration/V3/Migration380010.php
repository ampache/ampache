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
 * Add custom blank album/video default image and alphabet browsing options
 */
final class Migration380010 extends AbstractMigration
{
    protected array $changelog = ['Add custom blank album/video default image and alphabet browsing options'];

    public function migrate(): void
    {
        $this->updatePreferences('custom_blankalbum', 'Custom blank album default image', '', 75, 'string', 'interface', 'custom');
        $this->updatePreferences('custom_blankmovie', 'Custom blank video default image', '', 75, 'string', 'interface', 'custom');
        $this->updatePreferences('libitem_browse_alpha', 'Alphabet browsing by default for following library items (album,artist,...)', '', 75, 'string', 'interface', 'library');
    }
}
