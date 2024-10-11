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

final class AdminResetPreferencesCommand extends Command
{
    public function __construct()
    {
        parent::__construct('admin:resetPreferences', T_('Reset preference values for users'));
        $this
            ->option('-e|--execute', T_('Execute the update'), 'boolval', false)
            ->option(
                '-p|--preset',
                T_('Config Preset') . " ('system', 'default', 'minimalist', 'community')",
                'strval',
                ''
            )
            ->argument('<username>', T_('Username'))
            ->usage('<bold>  admin:resetPreferences some-user --preset default</end> <comment> ## ' . T_('Reset preferences for some-user to default values') . '<eol/>');
    }

    public function execute(
        string $username
    ): void {
        if ($this->app() === null) {
            return;
        }
        $interactor = $this->io();
        $dryRun     = $this->values()['execute'] === false;
        $preset     = $this->values()['preset'];

        if ($dryRun === true) {
            $interactor->info(
                "\n" . T_('Running in Test Mode. Use -e|--execute to update'),
                true
            );
            if ($preset === '') {
                $interactor->warn(
                    "\n" . T_('Missing mandatory parameter') . ' -p|--preset',
                    true
                );
            }
            $interactor->ok(
                "\n" . T_('No changes have been made'),
                true
            );
        } else {
            if (
                $preset &&
                Preference::set_preset($username, $preset)
            ) {
                $interactor->ok(
                    "\n" . T_('Updated'),
                    true
                );
            } else {
                if ($preset === '') {
                    $interactor->warn(
                        "\n" . T_('Missing mandatory parameter') . ' -p|--preset',
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
