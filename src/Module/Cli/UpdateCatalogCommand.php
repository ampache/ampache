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
use Ampache\Module\Catalog\Update\UpdateCatalogInterface;

final class UpdateCatalogCommand extends Command
{
    private UpdateCatalogInterface $updateCatalog;

    public function __construct(
        UpdateCatalogInterface $updateCatalog
    ) {
        parent::__construct('run:updateCatalog', T_('Perform catalog actions for all files of a catalog. If no options are given, the defaults actions -ceag are assumed'));

        $this->updateCatalog   = $updateCatalog;

        $this
            ->option('-c|--cleanup', T_('Removes missing files from the database'), 'boolval', false)
            ->option('-a|--add', T_('Adds new media files to the database'), 'boolval', false)
            ->option('-g|--art', T_('Gathers media Art'), 'boolval', false)
            ->option('-e|--verify', T_('Reads your files and updates the database to match changes'), 'boolval', false)
            ->option('-f|--find', T_('Find missing files and print a list of filenames'), 'boolval', false)
            ->option('-u|--update', T_('Update local object metadata using external plugins'), 'boolval', false)
            ->option('-i|--import', T_('Adds new media files and imports playlist files'),  'boolval',false)
            ->option('-o|--optimize', T_('Optimizes database tables'), 'boolval', false)
            ->option('-t|--garbage', T_('Update table mapping, counts and delete garbage data'), 'boolval', false)
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
        // do a default list of actions if you don't have anything set
        if (empty($values['cleanup']) && empty($values['add']) && empty($values['art']) && empty($values['verify']) && empty($values['update']) && empty($values['import']) && empty($values['optimize']) && empty($values['garbage']) && empty($values['memorylimit']) && empty($values['catalogName']) && $values['catalogType'] === 'local') {
            $values['cleanup'] = true;
            $values['add']     = true;
            $values['art']     = true;
            $values['garbage'] = true;
            $values['verify']  = true;
        }

        $this->updateCatalog->update(
            $this->io(),
            $values['memorylimit'],
            $values['add'],
            $values['art'],
            $values['import'],
            $values['cleanup'],
            $values['find'],
            $values['verify'],
            $values['update'],
            $values['optimize'],
            $values['garbage'],
            $catalogName,
            $catalogType
        );
    }
}
