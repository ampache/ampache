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

namespace Ampache\Module\Api\Jellyfin\QuickConnect;

interface JellyfinQuickConnectRepositoryInterface
{
    /**
     * Row-and-secret-free lookup of one authorized-and-unconsumed request, atomically marking it consumed.
     * Returns the bound `user_id`, or null if the secret is unknown, expired, unauthorized, or already used.
     */
    public function consume(string $secret, int $now): ?int;

    /**
     * Counts how many requests this device has opened since `$since` — the `/Initiate` rate limit.
     */
    public function countRecentByDeviceId(string $deviceId, int $since): int;

    /**
     * @param array{secret: string, code: string, device_id: string, device_name: string, app_name: string, app_version: string, date_added: int, expires: int} $data
     */
    public function create(array $data): int;

    /**
     * Sweeps rows nothing can consume any more — expired outright, or already consumed once. Called from
     * `Session::garbage_collection()`, alongside every other session-adjacent table's own sweep.
     */
    public function deleteExpired(int $now): void;

    /** @return array<string, mixed>|null */
    public function findByCode(string $code, int $now): ?array;

    /** @return array<string, mixed>|null */
    public function findBySecret(string $secret, int $now): ?array;

    /** Returns the row's new attempt count. */
    public function incrementAuthorizeAttempts(int $id): int;

    public function markAuthorized(int $id, int $userId): void;
}
