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

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * The image table is not forcing unique values and can have a lot of duplicates
 */
final class Migration750004 extends AbstractMigration
{
    protected array $changelog = ['Fix 0 `width` and `height` columns using `size` for the `image` table'];

    public function migrate(): void
    {
        // Fix 0 width and height images (string sizes like original will still need a fix)
        $this->updateDatabase('UPDATE `image` SET `width` = CAST(SUBSTRING_INDEX(size, \'x\', 1) AS UNSIGNED), `height` = CAST(SUBSTRING_INDEX(size, \'x\', -1) AS UNSIGNED) WHERE `width` = 0 AND `height` = 0 AND `size` LIKE \'%_x_%\';');
    }
}
