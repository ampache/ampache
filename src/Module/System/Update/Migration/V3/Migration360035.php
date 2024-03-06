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

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add beautiful stream url setting
 * Reverted https://github.com/ampache/ampache/commit/0c26c336269624d75985e46d324e2bc8108576ee
 * with adding update_380012.
 * Because it was changed after many systems have already performed this update.
 * Fix for this is update_380012 that actually reads the preference string.
 * So all users have a consistent database.
 */
final class Migration360035 extends AbstractMigration
{
    protected array $changelog = ['Add beautiful stream url setting'];

    public function migrate(): void
    {
        $this->updatePreferences('stream_beautiful_url', 'Use beautiful stream url', '0', AccessLevelEnum::ADMIN->value, 'boolean', 'streaming');
    }
}
