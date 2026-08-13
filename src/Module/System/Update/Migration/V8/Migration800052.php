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
 * Convert `stream_beautiful_url` into a system preference
 */
final class Migration800052 extends AbstractMigration
{
    protected array $changelog = ['Convert `stream_beautiful_url` into a system preference'];

    public function migrate(): void
    {
        // URL rewriting is set up in the web server, so it is true or false for the whole instance. Keep the value the
        // server admin was using: the per-user rows are about to go and -1 may never have been touched.
        $this->updateDatabase(
            "UPDATE `user_preference` AS `system` JOIN `preference` ON `preference`.`id` = `system`.`preference` AND `preference`.`name` = 'stream_beautiful_url' JOIN (SELECT `user_preference`.`value` FROM `user_preference` JOIN `preference` ON `preference`.`id` = `user_preference`.`preference` AND `preference`.`name` = 'stream_beautiful_url' JOIN `user` ON `user`.`id` = `user_preference`.`user` AND `user`.`access` = 100 ORDER BY `user`.`id` LIMIT 1) AS `admin` SET `system`.`value` = `admin`.`value` WHERE `system`.`user` = -1;"
        );
        $this->updateDatabase("UPDATE `preference` SET `category` = 'system', `subcategory` = 'backend' WHERE `name` = 'stream_beautiful_url';");
        $this->updateDatabase("DELETE FROM `user_preference` WHERE `name` = 'stream_beautiful_url' AND `user` != -1;");
    }
}
