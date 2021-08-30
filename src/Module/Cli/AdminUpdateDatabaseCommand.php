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
        $interactor = $this->io();

        if (Update::need_update() && $this->values()['execute'] === true) {
            Update::run_update();
        }

        if (Update::need_update()) {
            $interactor->info(
                T_('The following updates need to be performed:'),
                true
            );
        }

        $result = Update::display_update();
        if ($result === []) {
            $interactor->info(T_('No update needed'), true);
        } else {
            foreach ($result as $updateInfo) {
                $interactor->info($updateInfo['version'], true);
                $interactor->info($updateInfo['description'], true);
            }
        }
    }
}
