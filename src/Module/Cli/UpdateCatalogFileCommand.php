<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Catalog\Update\UpdateSingleCatalogFileInterface;

final class UpdateCatalogFileCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private UpdateSingleCatalogFileInterface $updateSingleCatalogFile;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UpdateSingleCatalogFileInterface $updateSingleCatalogFile
    ) {
        parent::__construct('run:updateCatalogFile', T_('Perform catalog actions for a single file'));

        $this->configContainer         = $configContainer;
        $this->updateSingleCatalogFile = $updateSingleCatalogFile;

        $this
            ->option('-c|--cleanup', T_('Removes missing files from the database'), 'boolval', false)
            ->option('-e|--verify', T_('Reads your files and updates the database to match changes'), 'boolval', true)
            ->option('-a|--add', T_('Adds new media files to the database'), 'boolval', false)
            ->option('-g|--art', T_('Gathers media Art'), 'boolval', false)
            ->argument('<catalogName>', T_('Catalog Name'))
            ->argument('<filePath>', T_('File Path'))
            ->usage('<bold>  run:updateCatalogFile some-catalog /tmp/some-file.mp3</end> <comment> ## ' . T_('Update /tmp/some-file.mp3 in the catalog `some-catalog`') . '<eol/>');
    }

    public function execute(
        string $catalogName,
        string $filePath
    ): void {
        $values = $this->values();

        $this->updateSingleCatalogFile->update(
            $this->io(),
            $catalogName,
            $filePath,
            $values['verify'],
            $values['add'],
            $values['cleanup'],
            $values['art']
        );
    }
}
