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

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Allow the system user (-1) to be stored in stat tables and attribute existing share plays to it.
 * Legacy databases can still have an UNSIGNED `user` column, where -1 is silently clamped to 0
 */
final class Migration800012 extends AbstractMigration
{
    protected array $changelog = [
        'Allow the system user (-1) in `object_count`, `user_activity`, `user_data` and `now_playing` on databases where `user` was UNSIGNED',
        'Attribute existing `share.php` plays to the system user (-1) instead of user 0',
    ];

    public function migrate(): void
    {
        $columns = [
            'object_count' => 'int(11) NOT NULL',
            'user_activity' => 'int(11) NOT NULL',
            'user_data' => 'int(11) DEFAULT NULL',
            'now_playing' => 'int(11) NOT NULL',
        ];

        foreach ($columns as $table => $definition) {
            if ($this->isUnsigned($table)) {
                $this->updateDatabase(sprintf('ALTER TABLE `%s` MODIFY COLUMN `user` %s;', $table, $definition));
            }
        }

        $this->updateDatabase("UPDATE IGNORE `object_count` SET `user` = -1 WHERE `user` = 0 AND `agent` = 'share.php';");
    }

    private function isUnsigned(string $table): bool
    {
        $db_results = Dba::read(
            'SELECT `COLUMN_TYPE` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = ? AND `COLUMN_NAME` = \'user\';',
            [$table]
        );

        $row = Dba::fetch_assoc($db_results);

        return str_contains(strtolower((string) ($row['COLUMN_TYPE'] ?? '')), 'unsigned');
    }
}
