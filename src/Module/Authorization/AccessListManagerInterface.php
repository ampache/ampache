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

namespace Ampache\Module\Authorization;

use Ampache\Module\Authorization\Exception\AclItemDuplicationException;
use Ampache\Module\Authorization\Exception\InvalidEndIpException;
use Ampache\Module\Authorization\Exception\InvalidIpRangeException;
use Ampache\Module\Authorization\Exception\InvalidStartIpException;

/**
 * Manages the creation and update of acl items
 */
interface AccessListManagerInterface
{
    /**
     * Updates an existing acl item
     *
     * @throws InvalidEndIpException
     * @throws InvalidIpRangeException
     * @throws InvalidStartIpException
     */
    public function update(
        int $accessId,
        string $startIp,
        string $endIp,
        string $name,
        int $userId,
        int $level,
        string $type
    ): void;

    /**
     * Creates a new acl item
     * Also creates further items on special type configs
     *
     * @throws AclItemDuplicationException
     * @throws InvalidEndIpException
     * @throws InvalidIpRangeException
     * @throws InvalidStartIpException
     */
    public function create(
        string $startIp,
        string $endIp,
        string $name,
        int $userId,
        int $level,
        string $type,
        string $additionalType
    ): void;
}
