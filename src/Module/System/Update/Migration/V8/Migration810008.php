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

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Index the column every API request is looked up by
 */
final class Migration810008 extends AbstractMigration
{
    protected array $changelog = ['Add an index on `user`.`apikey`, which the API authenticates against on every request'];

    public function migrate(): void
    {
        // `UserRepository::findByApiKey()` opens with `WHERE apikey = ?` and the column carried no key, so
        // authenticating a client read the whole user table. The hashed-key fallback below it scans the same
        // table again, which is why an unknown key cost twice over.
        if (!Dba::has_index('user', 'apikey')) {
            $this->updateDatabase('ALTER TABLE `user` ADD KEY `apikey` (`apikey`);');
        }
    }
}
