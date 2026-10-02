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
 * Converts `preference`.`type` to an enum of the four values `Preference::insert()` ever writes.
 */
final class Migration810028 extends AbstractMigration
{
    protected array $changelog = [
        'Convert `preference`.`type` from a plain `varchar` to an `enum` of its four real values',
    ];
    protected bool $warning = true;

    public function migrate(): void
    {
        // normalize rather than delete - this is the preference definition row, not disposable mapping data
        $this->updateDatabase("UPDATE `preference` SET `type` = 'string' WHERE `type` IS NOT NULL AND `type` NOT IN ('boolean', 'integer', 'string', 'special');");
        $this->updateDatabase("ALTER TABLE `preference` MODIFY COLUMN `type` enum('boolean','integer','string','special') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");
    }
}
