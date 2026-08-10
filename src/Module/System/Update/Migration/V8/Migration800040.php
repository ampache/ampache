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
 *
 */

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Remove the preferences of the 7digital preview plugin, whose api no longer exists.
 */
final class Migration800040 extends AbstractMigration
{
    protected array $changelog = [
        'Remove the `7digital_api_key` and `7digital_secret_api_key` preferences; the plugin they configured has been deleted',
    ];

    public function migrate(): void
    {
        $this->updateDatabase("DELETE FROM `user_preference` WHERE `preference` IN (SELECT `id` FROM `preference` WHERE `name` IN ('7digital_api_key', '7digital_secret_api_key'));");
        $this->updateDatabase("DELETE FROM `preference` WHERE `name` IN ('7digital_api_key', '7digital_secret_api_key');");
        $this->updateDatabase("DELETE FROM `update_info` WHERE `key` = 'Plugin_7digital';");
    }
}
