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
use Ampache\Repository\Model\Catalog;

final class CacheProcessCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct('run:cacheProcess', T_('Run the cache process'));

        $this->configContainer = $configContainer;
    }

    public function execute(): void
    {
        /* @var Interactor $interactor */
        $interactor = $this->app()?->io();
        if (!$interactor) {
            return;
        }

        $interactor->info(
            T_('Start cache process'),
            true
        );
        /**
         * Pre-cache any new files
         */
        if ($this->configContainer->get('cache_path') && $this->configContainer->get('cache_target')) {
            Catalog::cache_catalogs();
        }

        debug_event('cache', 'finished cache process', 4);
        $interactor->info(
            T_('Completed cache process'),
            true
        );
    }
}
