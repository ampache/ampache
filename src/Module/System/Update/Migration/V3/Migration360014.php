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
 * PHP session IDs are an ever-growing beast.
 */
final class Migration360014 extends AbstractMigration
{
    protected array $changelog = ['Increase the length of sessionids again'];

    public function migrate(): void
    {
        $this->updateDatabase("ALTER TABLE `stream_playlist` CHANGE COLUMN `sid` `sid` varchar(256);");
        $this->updateDatabase("ALTER TABLE `tmp_playlist` CHANGE COLUMN `session` `session` varchar(256);");
        $this->updateDatabase("ALTER TABLE `session` CHANGE COLUMN `id` `id` varchar(256) NOT NULL;");
    }
}
