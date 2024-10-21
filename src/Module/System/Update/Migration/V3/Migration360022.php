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

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Remove unused live_stream fields and add codec field
 */
final class Migration360022 extends AbstractMigration
{
    protected array $changelog = ['Remove unused live_stream fields and add codec field'];

    public function migrate(): void
    {
        $this->updateDatabase("ALTER TABLE `live_stream` ADD COLUMN `codec` varchar(32) NULL AFTER `catalog`, DROP `frequency`, DROP `call_sign`;");
        $this->updateDatabase("ALTER TABLE `stream_playlist` ADD COLUMN `codec` varchar(32) NULL AFTER `time`;");
    }
}
