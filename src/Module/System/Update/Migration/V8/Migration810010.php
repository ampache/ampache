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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Ampache\Repository\FolderRepositoryInterface;

/**
 * Add `folder`.`time` so the folder API can report each folder's summed duration
 *
 * It is rolled up the same way as `total_count`/`total_skip`: FolderRepository::update_folder_counts()
 * fills it for every existing folder here, and keeps it current afterwards on every catalog scan.
 */
final class Migration810010 extends AbstractMigration
{
    protected array $changelog = ['Add `folder`.`time` to hold the summed duration of everything below it'];
    private FolderRepositoryInterface $folderRepository;

    public function __construct(
        DatabaseConnectionInterface $connection,
        FolderRepositoryInterface $folderRepository,
    ) {
        parent::__construct($connection);

        $this->folderRepository = $folderRepository;
    }

    public function migrate(): void
    {
        // A partly-applied migration re-runs from the top, so the column is only added when it is absent.
        if (!Dba::has_column('folder', 'time')) {
            $this->updateDatabase("ALTER TABLE `folder` ADD COLUMN `time` bigint(20) unsigned NOT NULL DEFAULT '0';");
        }

        $this->folderRepository->update_folder_counts();
    }
}
