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

final class MoveCatalogPathCommand extends Command
{
    private UpdateCatalogInterface $updateCatalog;

    public function __construct(
        UpdateCatalogInterface $updateCatalog
    ) {
        parent::__construct('run:moveCatalogPath', T_('Change a Catalog path'));

        $this->updateCatalog   = $updateCatalog;

        $this
            ->argument('[catalogName]', T_('The name of the catalog to update'))
            ->argument('[catalogType]', T_('Type of Catalog (optional)'), 'local')
            ->argument('[path]', T_('New path'))
            ->usage('<bold>  run:moveCatalogPath some-catalog /new/path</end> <comment> ## ' . T_('Update the path of `some-catalog` to /new/path') . '</end><eol/>');
    }

    public function execute(
        ?string $catalogName,
        string $catalogType,
        ?string $path
    ): void {
        $this->updateCatalog->updatePath(
            $this->io(),
            $catalogType,
            $catalogName,
            $path
        );
    }
}
