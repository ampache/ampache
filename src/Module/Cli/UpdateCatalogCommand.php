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
use Ampache\Module\Catalog\Update\UpdateCatalogInterface;

final class UpdateCatalogCommand extends Command
{
    private UpdateCatalogInterface $updateCatalog;

    public function __construct(
        UpdateCatalogInterface $updateCatalog
    ) {
        parent::__construct('run:updateCatalog', T_('Perform catalog actions for all files of a catalog'));

        $this->updateCatalog   = $updateCatalog;

        $this
            ->option('-c|--cleanup', T_('Removes missing files from the database'), 'boolval', false)
            ->option('-e|--verify', T_('Reads your files and updates the database to match changes'), 'boolval', false)
            ->option('-a|--add', T_('Adds new media files to the database'), 'boolval', false)
            ->option('-g|--art', T_('Gathers media Art'), 'boolval', false)
            ->option('-u|--update', T_('Update local object metadata using external plugins'), 'boolval', false)
            ->option('-i|--import', T_('Imports playlists'),  'boolval',false)
            ->option('-o|--optimize', T_('Optimizes database tables'), 'boolval', false)
            ->option('-m|--memorylimit', T_('Temporarily deactivates PHP memory limit'), 'boolval', false)
            ->argument('[catalogName]', T_('Name of Catalog (optional)'))
            ->argument('[catalogType]', T_('Type of Catalog (optional)'), 'local')
            ->usage('<bold>  run:updateCatalog some-catalog local</end> <comment> ## ' . T_('Update the local catalog called `some-catalog`') . '</end><eol/>');
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
