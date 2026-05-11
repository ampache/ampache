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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Fix Licenses on uploaded Songs and delete bad data
 */
final class Migration793001 extends AbstractMigration
{
    protected array $changelog = ['Fix Licenses on uploaded Songs and delete bad data'];

    protected bool $warning = true;

    public function migrate(): void
    {
        $sql        = "SELECT * FROM `license` WHERE `name` REGEXP '^-?[0-9]+$' AND CAST(`name` AS UNSIGNED) > 0 AND `description` = '' AND `external_link` = '' AND `order` IS NULL;";
        $db_results = Dba::query($sql);

        if (Dba::num_rows($db_results) > 0) {
            while ($row = Dba::fetch_assoc($db_results)) {
                $bad_id  = (int) $row['id'];
                $good_id = (int) $row['name'];
                $this->updateDatabase("UPDATE `song` SET `license` = ? WHERE `license` = ?;", [$good_id, $bad_id]);
            }
        }

        $this->updateDatabase("DELETE FROM `license` WHERE `name` REGEXP '^-?[0-9]+$' AND CAST(`name` AS UNSIGNED) > 0 AND `description` = '' AND `external_link` = '' AND `order` IS NULL;");
    }
}
