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

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Drop id column from catalog_map
 * Alter `catalog_map` object_type charset and collation
 */
final class Migration530003 extends AbstractMigration
{
    protected array $changelog = [
        'Drop id column from catalog_map table',
        'Alter `catalog_map` object_type charset and collation',
    ];

    public function migrate(): void
    {
        Dba::write("ALTER TABLE `catalog_map` DROP COLUMN `id`;");
        $this->updateDatabase("ALTER TABLE `catalog_map` MODIFY COLUMN object_type varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;");
    }
}
