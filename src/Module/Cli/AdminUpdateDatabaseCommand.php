<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
use Ampache\Module\System\Update;

final class AdminUpdateDatabaseCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct('admin:updateDatabase', T_('Update the database to the latest version'));

        $this->configContainer = $configContainer;

        $this
            ->option('-e|--execute', T_('Execute the update'), 'boolval', false)
            ->usage('<bold>  admin:updateDatabase</end> <comment> ## ' . T_('Display database update information') . '</end><eol/>');
    }

    public function execute(): void
    {
        $updated    = false;
        $interactor = $this->io();
        $execute    = $this->values()['execute'] === true;
        /* HINT: Ampache version string (e.g. 5.4.0-release, develop) */
        $interactor->info(
            sprintf(T_('Ampache version: %s'), AmpConfig::get('version')),
            true
        );
        $interactor->info(
        /* HINT: config version string (e.g. 62) */
            sprintf(T_('Config version: %s'), AmpConfig::get('int_config_version')),
            true
        );
        /* HINT: db version string (e.g. 5.2.0 Build: 006) */
        $interactor->info(
            sprintf(T_('Database version: %s'), Update::format_version(Update::get_version())) . "\n",
            true
        );

        // check tables
        $missing = Update::check_tables($execute);
        if (!empty($missing)) {
            $message = ($execute)
                ? T_('Missing database tables have been created')
                : T_('Your database is missing these tables. Use -e|--execute to recreate them');
            $interactor->info(
                $message,
                true
            );
            foreach ($missing as $table_name) {
                /* HINT: filename (File path) OR table name (podcast, clip, etc) */
                $interactor->info(
                    sprintf(T_('Missing: %s'), $table_name),
                    true
                );
            }
        }

        if (Update::need_update() && $execute) {
            $updated = true;
            Update::run_update();
        }

        if (Update::need_update()) {
            $interactor->info(
                "\n" . T_('The following updates need to be performed:'),
                true
            );
        }

        $result = Update::display_update();
        if ($result === []) {
            if ($updated) {
                // tell the user that the database was updated and the version
                $interactor->info(
                    T_('Updated'),
                    true
                );
                /* HINT: db version string (e.g. 5.2.0 Build: 006) */
                $interactor->info(
                    sprintf(T_('Database version: %s'), Update::format_version(Update::get_version())),
                    true
                );
            } else {
                $interactor->info(
                    T_('No update needed'),
                    true
                );
            }
        } else {
            foreach ($result as $updateInfo) {
                $interactor->info(
                    $updateInfo['version'],
                    true
                );
                $interactor->info(
                    "\n" . str_replace(array("<b>", "</b>"), "", (str_replace(array("<br />"), "\n", $updateInfo['description']))),
                    true
                );
            }
        }
    }
}
