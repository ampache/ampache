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
 * Keep the last.fm page url returned with cached artist info, for OpenSubsonic `lastFmUrl`
 *
 * Artist info is cached for six months, so the url has to be stored with it. Albums have no cache path.
 */
final class Migration800033 extends AbstractMigration
{
    protected array $changelog = ['Add `artist`.`lastfm_url` to keep the last.fm page url with the cached artist info'];

    public function migrate(): void
    {
        // A partly-applied migration re-runs from the top, so the column is only added when it is absent.
        if (!Dba::has_column('artist', 'lastfm_url')) {
            $this->updateDatabase('ALTER TABLE `artist` ADD COLUMN `lastfm_url` varchar(255) DEFAULT NULL;');
        }
    }
}
