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

namespace Ampache\Config\Init;

use Ampache\Config\Init\Exception\DatabaseOutdatedException;
use Ampache\Config\Init\Exception\EnvironmentNotSuitableException;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update;

final class InitializationHandlerDatabaseUpdate implements InitializationHandlerInterface
{
    public function init(): void
    {
        // Check to see if we need to perform an update
        if (!defined('OUTDATED_DATABASE_OK')) {
            if (!Dba::check_database()) {
                throw new EnvironmentNotSuitableException();
            }
            if (Update::need_update()) {
                throw new DatabaseOutdatedException();
            }
        }
    }
}
