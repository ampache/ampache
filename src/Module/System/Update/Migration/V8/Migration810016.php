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
 * Correct the type of the two preferences that shipped as `bool`, which renders as a text field
 */
final class Migration810016 extends AbstractMigration
{
    protected array $changelog = [
        'Fix the `bool` type on `home_recently_played_all` and `show_wrapped`, which should be `boolean`',
    ];

    public function migrate(): void
    {
        $this->updateDatabase(
            "UPDATE `preference` SET `type` = 'boolean' WHERE `name` IN ('home_recently_played_all', 'show_wrapped') AND `type` = 'bool';"
        );
    }
}
