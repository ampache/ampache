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
 * This update makes changes to the cataloging to accomodate the new method
 * for syncing between Ampache instances.
 */
final class Migration360002 extends AbstractMigration
{
    protected array $changelog = [
        'Add Bandwidth and Feature preferences to simplify how interface is presented',
        'Change Tables to FULLTEXT() for improved searching',
        'Increase Filename lengths to 4096',
        'Remove useless KEY reference from ACL and Catalog tables',
        'Add new Remote User / Remote Password fields to Catalog',
    ];

    public function migrate(): void
    {
        // Drop the key from catalog and ACL
        $sql_array = array(
            "ALTER TABLE `catalog` DROP COLUMN `key`",
            "ALTER TABLE `access_list` DROP COLUMN `key`",
            "ALTER TABLE `catalog` ADD COLUMN `remote_username` VARCHAR (255) AFTER `catalog_type`",
            "ALTER TABLE `catalog` ADD COLUMN `remote_password` VARCHAR (255) AFTER `remote_username`",
            "ALTER TABLE `song` CHANGE COLUMN `file` `file` VARCHAR (4096)",
            "ALTER TABLE `video` CHANGE COLUMN `file` `file` VARCHAR (4096)",
            "ALTER TABLE `live_stream` CHANGE COLUMN `url` `url` VARCHAR (4096)",
            "ALTER TABLE `artist` ADD FULLTEXT(`name`)",
            "ALTER TABLE `album` ADD FULLTEXT(`name`)",
            "ALTER TABLE `song` ADD FULLTEXT(`title`)"
        );
        foreach ($sql_array as $sql) {
            $this->updateDatabase($sql);
        }

        // Now add in the min_object_count preference and the random_method
        $this->updatePreferences('bandwidth', 'Bandwidth', '50', AccessLevelEnum::GUEST->value, 'integer', 'interface');
        $this->updatePreferences('features', 'Features', '50', AccessLevelEnum::GUEST->value, 'integer', 'interface');
    }
}
