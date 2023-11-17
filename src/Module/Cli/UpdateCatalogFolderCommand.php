<?php

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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Module\Catalog\Update\UpdateSingleCatalogFolderInterface;

final class UpdateCatalogFolderCommand extends Command
{
    private UpdateSingleCatalogFolderInterface $updateSingleCatalogFolder;

    public function __construct(
        UpdateSingleCatalogFolderInterface $updateSingleCatalogFolder
    ) {
        parent::__construct('run:updateCatalogFolder', T_('Perform catalog actions for a single folder'));

        $this->updateSingleCatalogFolder = $updateSingleCatalogFolder;

        $this
            ->option('-c|--cleanup', T_('Removes missing files from the database'), 'boolval', false)
            ->option('-e|--verify', T_('Reads your files and updates the database to match changes'), 'boolval', false)
            ->option('-a|--add', T_('Adds new media files to the database'), 'boolval', false)
            ->option('-g|--art', T_('Gathers media Art'), 'boolval', false)
            ->argument('<catalogName>', T_('Catalog Name'))
            ->argument('<folderPath>', T_('Path'))
            /* HINT: filename (/tmp/some-file.mp3) OR folder path (/tmp/Artist/Album) */
            ->usage('<bold>  run:updateCatalogFolder some-catalog /tmp/Artist/Album</end> <comment> ## ' . sprintf(T_('Update %s in the catalog `some-catalog`'), '/tmp/Artist/Album') . '<eol/>');
    }

    public function execute(
        string $catalogName,
        string $folderPath
    ): void {
        $values = $this->values();

        $this->updateSingleCatalogFolder->update(
            $this->io(),
            $catalogName,
            $folderPath,
            $values['verify'],
            $values['add'],
            $values['cleanup'],
            $values['art']
        );
    }
}
