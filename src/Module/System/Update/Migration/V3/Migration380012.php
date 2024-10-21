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
 * Fix change in https://github.com/ampache/ampache/commit/0c26c336269624d75985e46d324e2bc8108576ee
 * That left the user base with an inconsistent database.
 * For more information, please look at 360035.
 *
 * @see Migration360035
 */
final class Migration380012 extends AbstractMigration
{
    protected array $changelog = ['Fix change in https://github.com/ampache/ampache/commit/0c26c336269624d75985e46d324e2bc8108576ee that left the userbase with an inconsistent database, if users updated or installed Ampache before 28 Apr 2015'];

    public function migrate(): void
    {
        $this->updateDatabase("UPDATE `preference` SET `description`='Enable url rewriting' WHERE `preference`.`name`='stream_beautiful_url';");
    }
}
