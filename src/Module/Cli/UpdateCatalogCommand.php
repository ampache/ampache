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
use Ampache\Module\Catalog\Update\UpdateCatalogInterface;

final class UpdateCatalogCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private UpdateCatalogInterface $updateCatalog;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UpdateCatalogInterface $updateCatalog
    ) {
        parent::__construct('run:updateCatalog', 'Performs catalog actions for all files of a catalog');

        $this->configContainer = $configContainer;
        $this->updateCatalog   = $updateCatalog;

        $this
            ->option('-c|--cleanup', 'Cleans the catalog', 'boolval', false)
            ->option('-e|--verify', 'Verifies the catalog', 'boolval', false)
            ->option('-a|--add', 'Adds new media to the catalog', 'boolval', false)
            ->option('-g|--art', 'Searches for new art for the catalog', 'boolval', false)
            ->option('-u|--update', 'Update artist information and fetch similar artists from last.fm', 'boolval', false)
            ->option('-i|--import', 'Imports playlists',  'boolval',false)
            ->option('-o|--optimize', 'Optimize database tables', 'boolval', false)
            ->option('-m|--memorylimit', 'Temporarily deactivating PHP memory limit', 'boolval', false)
            ->argument('[catalogName]', 'The name of the catalog to update', null)
            ->argument('[catalogType]', 'Type of the catalog', 'local')
            ->usage('<bold>  run:updateCatalog some-catalog local</end> <comment> ## Update the local catalog with name `some-catalog`</end><eol/>');
    }

    public function execute(
        ?string $catalogName,
        string $catalogType
    ): void {
        $values = $this->values();

        $this->updateCatalog->update(
            $this->io(),
            $values['memorylimit'],
            $values['add'],
            $values['art'],
            $values['import'],
            $values['cleanup'],
            $values['verify'],
            $values['update'],
            $values['optimize'],
            $catalogName,
            $catalogType
        );
    }
}
