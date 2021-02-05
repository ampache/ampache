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

final class MoveCatalogPathCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private UpdateCatalogInterface $updateCatalog;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UpdateCatalogInterface $updateCatalog
    ) {
        parent::__construct('run:moveCatalogPath', T_('Moving Catalog path'));

        $this->configContainer = $configContainer;
        $this->updateCatalog   = $updateCatalog;

        $this
            ->argument('[catalogName]', T_('The name of the catalog to update'), null)
            ->argument('[catalogType]', 'Type of the catalog', 'local')
            ->argument('[path]', T_('New path'), null)
            ->usage('<bold>  run:moveCatalogPath some-catalog /new/path/to/catalog</end> <comment> ## Update the path of all files of the given catalogs</end><eol/>');
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
