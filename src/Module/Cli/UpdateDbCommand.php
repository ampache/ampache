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

final class UpdateDbCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private DatabaseCharsetUpdaterInterface $databaseCharsetUpdater;

    public function __construct(
        ConfigContainerInterface $configContainer,
        DatabaseCharsetUpdaterInterface $databaseCharsetUpdater
    ) {
        parent::__construct('run:updateDb', T_('Update the database collation and charset'));

        $this->configContainer        = $configContainer;
        $this->databaseCharsetUpdater = $databaseCharsetUpdater;

        $this
            ->option('-x|--execute', T_('Disables dry-run'), 'boolval', false)
            ->usage('<bold>  run:updateDb</end> <comment> ## ' . T_('Update the database') . '<eol/>');
    }

    public function execute(): void
    {
        $interactor = $this->app()->io();
        $dryRun     = $this->values()['execute'] === false;

        $translated_charset = Dba::translate_to_mysqlcharset($this->configContainer->get('site_charset'));
        $target_charset     = $translated_charset['charset'];
        $target_collation   = $translated_charset['collation'];
        $table_engine       = ($target_charset == 'utf8mb4') ? 'InnoDB' : 'MyISAM';

        $interactor->info(
            T_('This script makes changes to your database based on your config settings'),
            true
        );
        $interactor->info(
            sprintf(T_('Target charset: %s'), $target_charset),
            true
        );
        $interactor->info(
            sprintf(T_('Target collation: %s'), $target_collation),
            true
        );
        $interactor->info(
            sprintf(T_('Table engine: %s'), $table_engine),
            true
        );

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
                T_("WARNING") . "*** " . T_("Running in Write Mode. Make sure you've tested first!"),
                true
            );

            $this->databaseCharsetUpdater->update();
        }
    }
}
