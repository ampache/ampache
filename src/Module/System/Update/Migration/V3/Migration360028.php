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

namespace Ampache\Module\System\Update\Migration\V3;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Personal information: allow/disallow to show in now playing.
 * Personal information: allow/disallow to show in recently played.
 * Personal information: allow/disallow to show time and/or agent in recently played.
 */
final class Migration360028 extends AbstractMigration
{
    protected array $changelog = [
        'Personal information: allow/disallow to show in now playing',
        'Personal information: allow/disallow to show in recently played',
        'Personal information: allow/disallow to show time and/or agent in recently played',
    ];

    public function migrate(): void
    {
        // Update previous update preference
        $this->updateDatabase("UPDATE `preference` SET `name`='allow_personal_info_now', `description`='Personal information visibility - Now playing' WHERE `name`='allow_personal_info';");

        // Insert new recently played preference
        $this->updatePreferences('allow_personal_info_recent', 'Personal information visibility - Recently played / actions', '1', AccessLevelEnum::USER->value, 'boolean', 'interface');

        // Insert streaming time preference
        $this->updatePreferences('allow_personal_info_time', 'Personal information visibility - Recently played - Allow to show streaming date/time', '1', AccessLevelEnum::USER->value, 'boolean', 'interface');

        // Insert streaming agent preference
        $this->updatePreferences('allow_personal_info_agent', 'Personal information visibility - Recently played - Allow to show streaming agent', '1', AccessLevelEnum::USER->value, 'boolean', 'interface');
    }
}
