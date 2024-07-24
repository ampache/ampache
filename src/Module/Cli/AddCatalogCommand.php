<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Module\Catalog\Update\AddCatalogInterface;

final class AddCatalogCommand extends Command
{
    private AddCatalogInterface $addCatalog;

    public function __construct(
        AddCatalogInterface $addCatalog
    ) {
        parent::__construct('run:addCatalog', T_('Create a local media catalog'));

        $this->addCatalog = $addCatalog;

        $this
            ->argument('[catalogName]', T_('Catalog name'))
            ->argument('[catalogPath]', T_('Path'))
            ->argument('[mediaType]', T_('Catalog Media Type (optional)') . " ('music', 'clip', 'podcast')", 'music')
            ->argument('[filePattern]', T_('Filename Pattern (optional)'), '%T - %t')
            ->argument('[folderPattern]', T_('Folder Pattern (optional)'), '%a/%A')
            ->usage('<bold>  run:addCatalog some-catalog /mnt/path/to/music</end> <comment> ## ' . T_('Create a local music catalog called `some-catalog`') . '</end><eol/>');
    }

    public function execute(
        string $catalogName,
        string $catalogPath,
        string $mediaType,
        string $filePattern,
        string $folderPattern
    ): void {
        $this->addCatalog->add(
            $this->io(),
            $catalogName,
            $catalogPath,
            'local',
            $mediaType,
            $filePattern,
            $folderPattern
        );
    }
}
