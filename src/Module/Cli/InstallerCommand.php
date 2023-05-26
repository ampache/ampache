<?php
/*
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
use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\InstallationHelperInterface;

final class InstallerCommand extends Command
{
    private InstallationHelperInterface $installationHelper;

    public function __construct(
        InstallationHelperInterface $installationHelper
    ) {
        parent::__construct('install', T_('Install the database'));

        $this
            ->option('-U|--dbuser', T_('MySQL Administrative Username'), 'strval')
            ->option('-P|--dbpassword', T_('MySQL Administrative Password'), 'strval')
            ->option('-H|--dbhost', T_('MySQL Hostname'), 'strval')
            ->option('-o|--dbport', T_('MySQL Port'), 'intval')
            ->option('-d|--dbname', T_('Desired Database Name'), 'strval')
            ->option('-u|--ampachedbuser', T_('Ampache Database Username'), 'strval')
            ->option('-p|--ampachedbpassword', T_('Ampache Database Password'))
            ->option('-w|--webpath', T_('Web Path'), 'strval')
            ->option('-f|--force', T_('Overwrite if Config Already Exists'), 'boolval', false)
            ->usage('<bold>  install</end> <comment> ## ' . T_('Displays database update information') . '</end><eol/>');
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
        if (!$this->installationHelper->install_insert_db($new_db_user, $new_db_pass, true, $force)) {
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
            $interactor->error(
                AmpError::get('general'),
                true
            );
        }
    }
}
