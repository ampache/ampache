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
use Ampache\Module\Song\SongSorterInterface;

final class SortFilesCommand extends Command
{
    private SongSorterInterface $songSorter;

    public function __construct(
        SongSorterInterface $songSorter
    ) {
        parent::__construct('cleanup:sortSongs', T_('Sort songs files'));

        $this->songSorter      = $songSorter;

        $this
            ->option('-x|--execute', T_('Disables dry-run'), 'boolval', false)
            ->option('-f|--files', T_('Rename files and keep them in the current folder'), 'boolval', false)
            ->option('-l|--limit', T_('Limit how many moves to allow before stopping'), 'intval', 0)
            ->option('-w|--windows', T_('Replace all Windows-incompatible characters with an underscore'), 'boolval', false)
            ->option('-n|--name', T_('Sets the default name for `Various Artists`'), 'strval')
            ->argument('[catalogName]', T_('Name of Catalog (optional)'))
            ->usage('<bold>  cleanup:sortSongs</end> <comment> ## ' . T_('Sort song files') . '<eol/>');
    }

    public function execute(
        ?string $catalogName
    ): void {
        $interactor = $this->app()->io();
        $values     = $this->values();
        $dryRun     = $values['execute'] === false;

        if ($dryRun === true) {
            $interactor->info(
                T_('Running in Test Mode. Use -x to execute'),
                true
            );
        } else {
            $interactor->warn(
                T_('Running in Write Mode. Make sure you\'ve tested first!'),
                true
            );
        }

        $this->songSorter->sort(
            $interactor,
            $dryRun,
            $values['files'],
            $values['limit'],
            $values['windows'],
            $values['name'],
            $catalogName
        );
    }
}
