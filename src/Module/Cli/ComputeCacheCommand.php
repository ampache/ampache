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
use Ampache\Module\Cache\ObjectCacheInterface;

final class ComputeCacheCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private ObjectCacheInterface $objectCache;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ObjectCacheInterface $objectCache
    ) {
        parent::__construct('run:computeCache', 'Update the object cache');

        $this->configContainer = $configContainer;
        $this->objectCache     = $objectCache;
    }

    public function execute(): void
    {
        $io = $this->app()->io();

        debug_event('compute_cache', 'started cache process', 5);

        $io->white(T_('Start cache process'), true);

        $this->objectCache->compute();

        debug_event('compute_cache', 'Completed cache process', 5);

        $io->white(T_('Completed cache process'), true);
    }
}
