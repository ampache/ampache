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

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Fix bug that caused the remote_username/password fields to not be created.
 * FIXME: Huh?
 */
final class Migration360008 extends AbstractMigration
{
    protected array $changelog = ['Verify remote_username and remote_password were added correctly to catalog table'];

    public function migrate(): void
    {
        $remote_username = false;
        $remote_password = false;

        $sql        = "DESCRIBE `catalog`";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['Field'] == 'remote_username') {
                $remote_username = true;
            }
            if ($row['Field'] == 'remote_password') {
                $remote_password = true;
            }
        } // end while

        if (!$remote_username) {
            // Add in Username / Password for catalog - to be used for remote catalogs
            $this->updateDatabase("ALTER TABLE `catalog` ADD COLUMN `remote_username` VARCHAR (255) AFTER `catalog_type`");
        }
        if (!$remote_password) {
            $this->updateDatabase("ALTER TABLE `catalog` ADD COLUMN `remote_password` VARCHAR (255) AFTER `remote_username`");
        }
    }
}
