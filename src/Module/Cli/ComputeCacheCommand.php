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
use Ampache\Module\Cache\ObjectCacheInterface;

final class ComputeCacheCommand extends Command
{
    private ObjectCacheInterface $objectCache;

    public function __construct(
        ObjectCacheInterface $objectCache
    ) {
        parent::__construct('run:computeCache', T_('Update the object cache tables'));

        $this->objectCache     = $objectCache;
    }

    public function execute(): void
    {
        $interactor = $this->app()->io();

        debug_event('compute_cache', 'started cache process', 5);
        $interactor->info(
            T_('Start cache process'),
            true
        );

        $this->objectCache->compute();

        debug_event('compute_cache', 'Completed cache process', 5);
        $interactor->info(
            T_('Completed cache process'),
            true
        );
    }
}
