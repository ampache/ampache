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
use Ahc\Cli\IO\Interactor;
use Ampache\Module\Song\SongFilesystemCleanupInterface;

final class SongCleanupCommand extends Command
{
    private SongFilesystemCleanupInterface $songFilesystemCleanup;

    public function __construct(
        SongFilesystemCleanupInterface $songFilesystemCleanup
    ) {
        parent::__construct('cleanup:songs', T_('Delete disabled songs'));

        $this->songFilesystemCleanup = $songFilesystemCleanup;

        $this
            ->option('-d|--delete', T_('Disables dry-run'), 'boolval', false)
            ->usage('<bold>  cleanup:songs</end> <comment> ## ' . T_('Show a list of disabled songs') . '<eol/>');
    }

    public function execute(): void
    {
        /* @var Interactor $interactor */
        $interactor = $this->app()?->io();
        if (!$interactor) {
            return;
        }

        $delete = $this->values()['delete'];
        $result = $this->songFilesystemCleanup->cleanup($delete === false);

        if ($delete === false) {
            $interactor->green(
                T_('The following songs would be deleted:'),
                true
            );
        } else {
            $interactor->red(
                T_('The following songs have been deleted:'),
                true
            );
        }
        foreach ($result as $file_name) {
            $interactor->white(
                $file_name,
                true
            );
        }
    }
}
