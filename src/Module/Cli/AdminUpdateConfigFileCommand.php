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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\InstallationHelperInterface;

final class AdminUpdateConfigFileCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private InstallationHelperInterface $installationHelper;

    public function __construct(
        ConfigContainerInterface $configContainer,
        InstallationHelperInterface $installationHelper
    ) {
        parent::__construct('admin:updateConfigFile', T_('Update the Ampache config file'));

        $this->configContainer    = $configContainer;
        $this->installationHelper = $installationHelper;
        $this
            ->option('-e|--execute', T_('Execute the update'), 'boolval', false)
            ->usage('<bold>  admin:updateConfigFile</end> <comment> ## ' . T_('Update the config file') . '<eol/>');
    }

    public function execute(): void
    {
        /* @var Interactor $interactor */
        $interactor = $this->app()?->io();
        if (!$interactor) {
            return;
        }
        $dryRun     = $this->values()['execute'] === false;
        $outOfDate  = $this->configContainer->get('int_config_version') > $this->configContainer->get('config_version');

        if ($outOfDate) {
            $interactor->warn(
                "\n" . T_('Your Ampache config file is out of date!'),
                true
            );
        }

        if ($dryRun === true) {
            $interactor->info(
                "\n" . T_('Running in Test Mode. Use -e|--execute to update'),
                true
            );
            $interactor->ok(
                "\n" . T_('No changes have been made'),
                true
            );
        } elseif ($outOfDate) {
            $interactor->warn(
                "\n" . "***" . T_("WARNING") . "*** " . T_("Running in Write Mode. Make sure you've tested first!"),
                true
            );

            if ($this->installationHelper->write_config(__DIR__ . '/../../../config/ampache.cfg.php')) {
                $interactor->ok(
                    "\n" . T_('Updated'),
                    true
                );
            }
        }
    }
}
