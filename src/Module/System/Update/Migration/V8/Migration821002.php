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
 * Converts `preference`.`category` to an enum of its seven real values and shrinks `preference`.`subcategory`,
 * which holds short, ascii, free-text (plugin names and API-created subcategories, so it stays a `varchar`).
 */
final class Migration821002 extends AbstractMigration
{
    protected array $changelog = [
        'Convert `preference`.`category` from a plain `varchar` to an `enum` of its seven real values',
        'Shrink `preference`.`subcategory` from `varchar(128)` to `varchar(32)` and pin it to `utf8mb3`',
    ];
    protected bool $warning = true;

    public function migrate(): void
    {
        // normalize rather than delete - this is the preference definition row, not disposable mapping data
        $this->updateDatabase("UPDATE `preference` SET `category` = 'interface' WHERE `category` IS NOT NULL AND `category` NOT IN ('interface', 'internal', 'options', 'playlist', 'plugins', 'streaming', 'system');");
        $this->updateDatabase("ALTER TABLE `preference` MODIFY COLUMN `category` enum('interface','internal','options','playlist','plugins','streaming','system') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");

        // subcategory holds plugin display names (e.g. 'Lyrist Lyrics', 18 chars) and free text from the
        // preference_create API, not a fixed vocabulary, so it can't become an enum like type/category did
        $this->updateDatabase("UPDATE `preference` SET `subcategory` = LEFT(`subcategory`, 32) WHERE CHAR_LENGTH(`subcategory`) > 32;");
        $this->updateDatabase("ALTER TABLE `preference` MODIFY COLUMN `subcategory` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");
    }
}
