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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add `order` column to license table
 * Set `order` column to match the current `id` of the license
 */
final class Migration700021 extends AbstractMigration
{
    protected array $changelog = [
        'Add `order` column to license table',
        'Set `order` column to match the current `id` of the license',
    ];

    public function migrate(): void
    {
        Dba::write("ALTER TABLE `license` DROP COLUMN `order`;", [], true);
        $this->updateDatabase("ALTER TABLE `license` ADD COLUMN `order` SMALLINT(4) UNSIGNED NULL AFTER `external_link`;");

        $this->updateDatabase("UPDATE `license` SET `order` = `id` WHERE `order` IS NULL;");

    }
}
