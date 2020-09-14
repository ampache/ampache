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
        parent::__construct('run:updateCatalogFile', 'Performs catalog actions for a single file');

        $this->configContainer         = $configContainer;
        $this->updateSingleCatalogFile = $updateSingleCatalogFile;

        $this
            ->option('-c|--cleanup', 'Cleans the file from the catalog', 'boolval', false)
            ->option('-e|--verify', 'Verify the file in the catalog', 'boolval', true)
            ->option('-a|--add', 'Adds the file to the catalog', 'boolval', false)
            ->option('-g|--art', 'Adds art for the file', 'boolval', false)
            ->argument('<catalogName>', 'The name of the catalog to update')
            ->argument('<filePath>', 'Path to the file')
            ->usage('<bold>  run:updateCatalogFile some-catalog /tmp/some-file.mp3</end> <comment> ## Updates the file in the catalog<eol/>');
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
