<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\System\Update\Migration\V6;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration600050 extends AbstractMigration
{
    protected array $changelog = ['Update Last.FM plugin preferences that could be incorrect'];

    public function migrate(): void
    {
        $sql = 'UPDATE `preference` SET `catagory` = \'plugins\' WHERE `name` = \'lastfm_grant_link\'';
        Dba::write($sql);
        $sql = 'UPDATE `preference` SET `catagory` = \'internal\' WHERE `name` = \'lastfm_challenge\'';
        Dba::write($sql);
        $sql = 'UPDATE `preference` SET `subcatagory` = \'last.fm\' WHERE `name` IN (\'lastfm_challenge\', \'lastfm_grant_link\')';
        Dba::write($sql);
    }
}
