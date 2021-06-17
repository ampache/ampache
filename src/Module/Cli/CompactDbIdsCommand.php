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

final class CompactDbIdsCommand extends Command
{
    private UpdateCatalogInterface $updateCatalog;

    public function __construct(
        UpdateCatalogInterface $updateCatalog
    ) {
        parent::__construct('run:compactDbIds', T_('Compact Song, Album and Artist Id numbers'));

        $this->updateCatalog = $updateCatalog;

        $this
            ->option('-x|--execute', T_('Disables dry-run and compacts the Id numbers'), 'boolval', false)
            ->usage('<bold>  run:compactDbIds</end> <comment> ## ' . T_('Compact Song, Album and Artist Id numbers.') . ' (' . T_('MariaDB ONLY') . ')<eol/>');
    }

    public function execute(): void
    {
        $dryRun = $this->values()['execute'] === false;

        $this->updateCatalog->compactIds(
            $this->io(),
            $dryRun
        );
    }
}
