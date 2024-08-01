<?php

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

namespace Ampache\Repository;

use Ampache\Repository\Model\User;
use DateTimeInterface;
use Traversable;

interface IpHistoryRepositoryInterface
{
    /**
     * This returns the ip_history for the provided user
     *
     * @return Traversable<array{date: int, ip: string, agent: string, action: string}>
     */
    public function getHistory(
        User $user,
        ?bool $limited = true
    ): Traversable;

    /**
     * Returns the most recent ip-address used by the provided user
     */
    public function getRecentIpForUser(User $user): ?string;

    /**
     * Deletes outdated records
     */
    public function collectGarbage(): void;

    /**
     * Inserts a new row into the database
     */
    public function create(
        User $user,
        string $ipAddress,
        string $userAgent,
        DateTimeInterface $date,
        string $action
    ): void;
}
