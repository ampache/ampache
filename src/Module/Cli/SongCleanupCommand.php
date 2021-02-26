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
use Ampache\Module\Song\SongFilesystemCleanupInterface;

final class SongCleanupCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private SongFilesystemCleanupInterface $songFilesystemCleanup;

    public function __construct(
        ConfigContainerInterface $configContainer,
        SongFilesystemCleanupInterface $songFilesystemCleanup
    ) {
        parent::__construct('cleanup:songs', T_('Delete disabled songs'));

        $this->configContainer       = $configContainer;
        $this->songFilesystemCleanup = $songFilesystemCleanup;

        $this
            ->option('-d|--delete', T_('Disables dry-run and sorts the files'), 'boolval', false)
            ->usage('<bold>  cleanup:songs</end> <comment> ## Shows a list of disabled songs<eol/>');
    }

    public function execute(): void
    {
        $io = $this->app()->io();

        $delete = $this->values()['delete'];

        $result = $this->songFilesystemCleanup->cleanup($delete === false);

        if ($delete === false) {
            $io->green(T_('The following songs would be deleted:'), true);
        } else {
            $io->red(T_('The following songs have been deleted:'), true);
        }
        foreach ($result as $file_name) {
            $io->white($file_name, true);
        }
    }
}
