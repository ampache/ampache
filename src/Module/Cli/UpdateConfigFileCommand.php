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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Database\DatabaseCharsetUpdaterInterface;
use Ampache\Module\System\Dba;
use Ampache\Module\System\InstallationHelperInterface;

final class UpdateConfigFileCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private DatabaseCharsetUpdaterInterface $databaseCharsetUpdater;

    public function __construct(
        ConfigContainerInterface $configContainer,
        InstallationHelperInterface $installationHelper
    ) {
        parent::__construct('run:updateConfigFile', T_('Update the Ampache config file'));

        $this->configContainer = $configContainer;
        $this->installationHelper = $installationHelper;
        $this
            ->option('-x|--execute', T_('Disables dry-run'), 'boolval', false)
            ->usage('<bold>  run:updateConfigFile</end> <comment> ## ' . T_('Update the config file') . '<eol/>');
    }

    public function execute(): void
    {
        $interactor = $this->app()->io();
        $dryRun     = $this->values()['execute'] === false;
        $outOfDate  = $this->configContainer->get('int_config_version') > $this->configContainer->get('config_version');

        if ($outOfDate) {
            $interactor->info(
                T_('Your Ampache config file is out of date!'),
                true
            );
        }

        if ($dryRun === true) {
            $interactor->info(
                T_('Running in Test Mode. Use -x to execute'),
                true
            );
            $interactor->ok(
                T_('No changes have been made'),
                true
            );
        } else {
            $interactor->warn(
                "***" . T_("WARNING") . "*** " . T_("Running in Write Mode. Make sure you've tested first!"),
                true
            );

            if (
                $outOfDate &&
                $this->installationHelper->write_config(__DIR__ . '/../../../config/ampache.cfg.php')
            ) {
                $interactor->ok(
                    T_('Updated'),
                    true
                );
            }
        }
    }
}
