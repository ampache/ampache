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

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add ui option ('subsonic_always_download') Force Subsonic streams to download. (Enable scrobble in your client to record stats)
 */
final class Migration510005 extends AbstractMigration
{
    protected array $changelog = ['Add ui option (\'subsonic_always_download\') Force Subsonic streams to download. (Enable scrobble in your client to record stats)'];

    public function migrate(): void
    {
        $this->updatePreferences('subsonic_always_download', 'Force Subsonic streams to download. (Enable scrobble in your client to record stats)', '0', AccessLevelEnum::USER->value, 'boolean', 'options', 'subsonic');
    }
}
