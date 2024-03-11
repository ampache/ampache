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

namespace Ampache\Module\System\Update\Migration\V5;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add ui option ('use_original_year') Browse by Original Year for albums (falls back to Year)
 */
final class Migration500009 extends AbstractMigration
{
    protected array $changelog = ['Add ui option (\'use_original_year\') Browse by Original Year for albums (falls back to Year)'];

    public function migrate(): void
    {
        $this->updatePreferences('use_original_year', 'Browse by Original Year for albums (falls back to Year)', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse');
    }
}
