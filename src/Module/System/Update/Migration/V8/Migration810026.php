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
 * Repair the preference catalogue: eight wrong types, and two preferences nothing reads
 *
 * `Migration740001` already retyped the eight on databases upgraded from before 7.4, but `ampache.sql` has
 * seeded a version above it since 7.5.0, so every installation created fresh since then kept the wrong type.
 *
 * `sidebar_order_video` arrived with 700016 to order a sidebar section that does not exist, and
 * `allow_personal_info_agent` lost its last reader in 4.3.0.
 */
final class Migration810026 extends AbstractMigration
{
    protected array $changelog = [
        'Fix the `integer`/`string` type on eight preferences that are booleans',
        'Remove the unread `sidebar_order_video` and `allow_personal_info_agent` preferences',
    ];

    public function migrate(): void
    {
        $this->updateDatabase(
            "UPDATE `preference` SET `type` = 'boolean' WHERE `type` != 'boolean' AND `name` IN ('allow_video', 'browser_notify', 'geolocation', 'home_moment_albums', 'home_moment_videos', 'home_now_playing', 'home_recently_played', 'show_played_times');"
        );

        $this->updateDatabase(
            "DELETE `user_preference` FROM `user_preference` JOIN `preference` ON `preference`.`id` = `user_preference`.`preference` WHERE `preference`.`name` IN ('sidebar_order_video', 'allow_personal_info_agent');"
        );

        $this->updateDatabase(
            "DELETE FROM `preference` WHERE `name` IN ('sidebar_order_video', 'allow_personal_info_agent');"
        );
    }
}
