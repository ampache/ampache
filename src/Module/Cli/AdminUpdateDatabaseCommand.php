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
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\UpdateInfoRepositoryInterface;

final class AdminUpdateDatabaseCommand extends Command
{
    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private Update\UpdateHelperInterface $updateHelper;

    private Update\UpdaterInterface $updater;

    public function __construct(
        UpdateInfoRepositoryInterface $updateInfoRepository,
        Update\UpdateHelperInterface $updateHelper,
        Update\UpdaterInterface $updater
    ) {
        parent::__construct('admin:updateDatabase', T_('Update the database to the latest version'));

        $this
            ->option('-e|--execute', T_('Execute the update'), 'boolval', false)
            ->usage('<bold>  admin:updateDatabase</end> <comment> ## ' . T_('Display database update information') . '</end><eol/>');
        $this->updateInfoRepository = $updateInfoRepository;
        $this->updateHelper         = $updateHelper;
        $this->updater              = $updater;
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

        // Check for a valid connection first
        if (!Dba::check_database()) {
            $interactor->info(
                T_('Database Connection') . ": " . T_('Error'),
                true
            );
            $interactor->eol();

            return;
        }

        /* HINT: db version string (e.g. 5.2.0 Build: 006) */
        $interactor->info(
            sprintf(T_('Database version: %s'), $this->retrieveVersion()),
            true
        );

        // check tables
        try {
            $missing = $this->updater->checkTables($execute);
            if ($missing->valid()) {
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
        } catch (Update\Exception\UpdateFailedException $e) {
            $interactor->error(
                sprintf(
                    T_('Update failed! %s'),
                    $e->getMessage()
                ),
                true
            );

            return;
        }

        // Downgrade higher versions to the correct version
        if ($this->updater->hasOverUpdated()) {
            if ($execute) {
                try {
                    $this->updater->rollback();

                    $interactor->ok(
                        "\n" . T_('Updated'),
                        true
                    );
                    /* HINT: db version string (e.g. 5.2.0 Build: 006) */
                    $interactor->info(
                        sprintf(T_('Database version: %s'), $this->retrieveVersion()),
                        true
                    );
                } catch (Update\Exception\UpdateFailedException $e) {
                    $interactor->error(
                        sprintf(
                            T_('Update failed! %s'),
                            $e->getMessage()
                        ),
                        true
                    );
                }
            } else {
                $interactor->error(
                    T_('Your database version is higher than the Ampache version. Use -e|--execute to rollback'),
                    true
                );
                $interactor->eol();

                return;
            }
        }

        if ($this->updater->hasPendingUpdates()) {
            if ($execute) {
                $interactor->info(
                    "\n" . T_('Update Now!'),
                    true
                );
                $interactor->eol();
                $updated = true;
                try {
                    $this->updater->update($interactor);
                } catch (Update\Exception\UpdateException $e) {
                    $interactor->error(
                        "\n" . T_('Error'),
                        true
                    );

                    return;
                }
            } else {
                $interactor->warn(
                    "\n" . T_('The following updates need to be performed:'),
                    true
                );
            }
        }

        $result = $this->updater->getPendingUpdates();

        if ($result->valid() === false) {
            if ($updated) {
                // tell the user that the database was updated and the version
                $interactor->ok(
                    "\n" . T_('Updated'),
                    true
                );
                /* HINT: db version string (e.g. 5.2.0 Build: 006) */
                $interactor->info(
                    sprintf(T_('Database version: %s'), $this->retrieveVersion()),
                    true
                );
            } else {
                $interactor->info(
                    T_('No update needed'),
                    true
                );
            }

            // Make sure all default preferences are set
            Preference::set_defaults();
        } else {
            foreach ($result as $updateInfo) {
                $interactor->cyan(
                    "\n" . $updateInfo['versionFormatted'],
                    true
                );
                foreach ($updateInfo['migration']->getChangelog() as $changelog) {
                    $interactor->white(
                        sprintf('* %s', $changelog),
                        true
                    );
                }
            }
        }
    }

    private function retrieveVersion(): string
    {
        return $this->updateHelper->formatVersion(
            (string) $this->updateInfoRepository->getValueByKey(UpdateInfoEnum::DB_VERSION)
        );
    }
}
