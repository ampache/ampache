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
use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\InstallationHelperInterface;

final class InstallerCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private InstallationHelperInterface $installationHelper;

    public function __construct(
        ConfigContainerInterface $configContainer,
        InstallationHelperInterface $installationHelper
    ) {
        parent::__construct('install', 'Installs the database');

        $this->configContainer = $configContainer;

        $this
            ->option('-U|--dbuser', 'The database user', 'strval')
            ->option('-P|--dbpassword', 'The database password', 'strval')
            ->option('-H|--dbhost', 'The database host', 'strval')
            ->option('-o|--dbport', 'The database port', 'intval')
            ->option('-d|--dbname', 'The database name', 'strval')
            ->option('-u|--ampachedbuser', 'The database user meant for ampache', 'strval')
            ->option('-p|--ampachedbpassword', 'The password for the ampache db user')
            ->option('-w|--webpath', 'The ampache webpath', 'strval')
            ->option('-f|--force', 'Force the installation (ignore existing config file)', 'boolval', false)
            ->usage('<bold>  install</end> <comment> ## Displays database update information</end><eol/>');
        $this->installationHelper = $installationHelper;
    }

    public function execute(): void
    {
        $interactor = $this->io();
        $configfile = __DIR__ . '/../../../config/ampache.cfg.php';
        $values     = $this->values();

        $force       = $values['force'];
        $db_user     = $values['dbuser'];
        $db_pass     = $values['dbpassword'];
        $db_host     = $values['dbhost'];
        $db_port     = $values['dbport'];
        $db_name     = $values['dbname'];
        $new_db_user = $values['ampachedbuser'];
        $new_db_pass = $values['ampachedbpassword'];
        $web_path    = $values['webpath'];

        // Make sure we have all the required information
        if (!$db_user || !$db_pass || !$db_host || !$db_name) {
            $this->showHelp();

            return;
        }

        // Now let's make sure it's not already installed
        if (!$this->installationHelper->install_check_status($configfile)) {
            $interactor->error(
                T_('Existing Ampache installation found'),
                true
            );
            if ($force) {
                $interactor->warn(
                    T_('Force specified, proceeding anyway'),
                    true
                );
            } else {
                return;
            }
        }

        AmpConfig::set_by_array(array(
            'web_path' => $web_path,
            'database_name' => $db_name,
            'database_username' => $db_user,
            'database_password' => $db_pass,
            'database_hostname' => $db_host,
            'database_port' => $db_port
        ), true);

        // Install the database
        if (!$this->installationHelper->install_insert_db($new_db_user, $new_db_pass, true, $force, true)) {
            $interactor->error(
                T_('Database creation failed'),
                true
            );
            $interactor->error(
                AmpError::get('general'),
                true
            );

            return;
        }

        AmpConfig::set_by_array(array(
            'database_username' => $new_db_user ?: $db_user,
            'database_password' => $new_db_pass ?: $db_pass
        ), true);

        // Write the config file
        /** @noinspection PhpUnhandledExceptionInspection */
        if (!$this->installationHelper->install_create_config()) {
            $interactor->error(
                T_('Config file creation failed'),
                true
            );
            $interactor->error(AmpError::get('general'), true);

            return;
        }
    }
}
