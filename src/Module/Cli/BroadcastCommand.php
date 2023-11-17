<?php

/**
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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Upnp_Api;

final class BroadcastCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct('run:broadcast', T_('Run a UPnP broadcast'));

        $this->configContainer = $configContainer;
    }

    public function execute(): void
    {
        $interactor = $this->app()->io();

        $interactor->white(
            T_('Starting broadcasts...'),
            true
        );

        if ($this->configContainer->get('upnp_backend')) {
            $interactor->white(
                T_("UPnP broadcast... "),
                true
            );
            Upnp_Api::sddpSend();
            $interactor->white(
                T_('Done'),
                true
            );
        } else {
            $interactor->error(
                T_('UPnP backend disabled. Broadcast skipped.'),
                true
            );
        }
    }
}
