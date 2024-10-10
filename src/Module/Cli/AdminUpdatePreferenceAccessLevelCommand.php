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
use Ampache\Repository\Model\Preference;

final class AdminUpdatePreferenceAccessLevelCommand extends Command
{
    public function __construct()
    {
        parent::__construct('admin:updatePreferenceAccessLevel', T_('Update Preference Access Level'));
        $this
            ->option('-e|--execute', T_('Execute the update'), 'boolval', false)
            ->option(
                '-l|--level',
                T_('Access Level') . " ('default', 'guest', 'user', 'content_manager', 'manager', 'admin')",
                'strval',
                ''
            )
            ->usage('<bold>  admin:updatePreferenceAccessLevel --level admin</end> <comment> ## ' . T_('Update Preference Access Level') . '<eol/>');
    }

    public function execute(): void
    {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();
        $dryRun     = $this->values()['execute'] === false;
        $level      = $this->values()['level'];

        if ($dryRun === true) {
            $interactor->info(
                "\n" . T_('Running in Test Mode. Use -e|--execute to update'),
                true
            );
            if ($level === '') {
                $interactor->warn(
                    "\n" . T_('Missing mandatory parameter') . ' -l|--level',
                    true
                );
            }
            $interactor->ok(
                "\n" . T_('No changes have been made'),
                true
            );
        } else {
            if (
                $level &&
                Preference::set_level($level)
            ) {
                $interactor->ok(
                    "\n" . T_('Updated'),
                    true
                );
            } else {
                if ($level === '') {
                    $interactor->warn(
                        "\n" . T_('Missing mandatory parameter') . ' -l|--level',
                        true
                    );
                }
                $interactor->error(
                    "\n" . T_('Error'),
                    true
                );
            }
        }
    }
}
