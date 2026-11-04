<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

/**
 * Tracks guest/share stream activity by source IP, for the stream control plugins' per-IP limits
 */
interface StreamControlIpActivityRepositoryInterface
{
    /**
     * Deletes outdated records
     */
    public function collectGarbage(): void;

    /**
     * Records one allowed guest/share request against the given IP
     */
    public function record(string $ip, int $date, int $hits, int $size, int $time): void;

    /**
     * Total bandwidth, in bytes, recorded for the IP within the date range
     */
    public function sumBandwidth(string $ip, int $startDate, int $endDate): int;

    /**
     * Total hits recorded for the IP within the date range
     */
    public function sumHits(string $ip, int $startDate, int $endDate): int;

    /**
     * Total playback time, in seconds, recorded for the IP within the date range
     */
    public function sumTime(string $ip, int $startDate, int $endDate): int;
}
