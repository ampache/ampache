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
use Ampache\Module\System\AmpError;
use Ampache\Module\System\InstallationHelperInterface;

final class HtaccessCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private InstallationHelperInterface $installationHelper;

    public function __construct(
        ConfigContainerInterface $configContainer,
        InstallationHelperInterface $installationHelper
    ) {
        parent::__construct('htaccess', T_('Create .htaccess files'));

        $this->configContainer = $configContainer;

        $this
            ->option('-e|--execute', T_('Execute the update'), 'boolval', false)
            ->usage('<bold>  htaccess -e</end> <comment> ## ' . T_('Recreate Ampache .htaccess files') . '</end><eol/>');
        $this->installationHelper = $installationHelper;
    }

    public function execute(): void
    {
        $interactor = $this->io();
        $execute    = $this->values()['execute'] === true;

        // Make sure we have all the required information
        if (!$execute) {
            $this->showHelp();

            return;
        }
        $htaccess_play_file = __DIR__ . '/../../../play/.htaccess';
        $htaccess_rest_file = __DIR__ . '/../../../rest/.htaccess';

        // check permissions
        if (!check_htaccess_play_writable()) {
            $interactor->error(
                T_('Permission Denied') . ": " . $htaccess_play_file,
                true
            );
            $interactor->error(
                AmpError::get('general'),
                true
            );

            return;
        }
        if (!check_htaccess_rest_writable()) {
            $interactor->error(
                T_('Permission Denied') . ": " . $htaccess_rest_file,
                true
            );
            $interactor->error(
                AmpError::get('general'),
                true
            );

            return;
        }
        unlink($htaccess_play_file);
        unlink($htaccess_rest_file);

        // create the files
        if (!$this->installationHelper->install_rewrite_rules($htaccess_play_file, $this->configContainer->getWebPath(), false)) {
            $interactor->error(
                T_('Failed to write config file') . ": " . $htaccess_play_file,
                true
            );
            $interactor->error(
                AmpError::get('general'),
                true
            );

            return;
        }
        if (!$this->installationHelper->install_rewrite_rules($htaccess_rest_file, $this->configContainer->getWebPath(), false)) {
            $interactor->error(
                T_('Failed to write config file') . ": " . $htaccess_rest_file,
                true
            );
            $interactor->error(
                AmpError::get('general'),
                true
            );

            return;
        }
        // successfully created htaccess files
        $interactor->white(
            T_('Success'),
            true
        );
    }
}
