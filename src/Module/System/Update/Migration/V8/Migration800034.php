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
 * Let a label be associated with an album, for OpenSubsonic `recordLabels`
 *
 * `artist` becomes nullable so a row links a label to exactly one of an artist or an album.
 */
final class Migration800034 extends AbstractMigration
{
    protected array $changelog = ['Allow `label_asso` to associate a label with an album as well as an artist'];

    public function migrate(): void
    {
        // MODIFY re-states the whole definition, so it is safe to replay after a partly-applied run
        $this->updateDatabase('ALTER TABLE `label_asso` MODIFY COLUMN `artist` int(11) UNSIGNED DEFAULT NULL;');

        if (!Dba::has_column('label_asso', 'album')) {
            $this->updateDatabase('ALTER TABLE `label_asso` ADD COLUMN `album` int(11) UNSIGNED DEFAULT NULL;');
            $this->updateDatabase('ALTER TABLE `label_asso` ADD KEY `label_asso_album_IDX` (`album`);');
        }
    }
}
