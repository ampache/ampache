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

use Ampache\Module\Database\DatabaseConnectionInterface;

final class QuickConnectRepository implements QuickConnectRepositoryInterface
{
    public function __construct(private readonly DatabaseConnectionInterface $connection) {}

    public function consume(string $secret, int $now): ?int
    {
        $row = $this->connection->fetchRow(
            'SELECT `id`, `user_id` FROM `jellyfin_quick_connect` WHERE `secret` = ? AND `authorized` = 1 AND `consumed` = 0 AND `expires` > ?',
            [$secret, $now],
        );
        if ($row === false || $row['user_id'] === null) {
            return null;
        }

        // the WHERE repeats the same guard so a second, concurrent caller affects zero rows, not one
        $affected = $this->connection->query(
            'UPDATE `jellyfin_quick_connect` SET `consumed` = 1 WHERE `id` = ? AND `authorized` = 1 AND `consumed` = 0 AND `expires` > ?',
            [$row['id'], $now],
        )->rowCount();

        return ($affected === 1) ? (int) $row['user_id'] : null;
    }

    public function countRecentByDeviceId(string $deviceId, int $since): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM `jellyfin_quick_connect` WHERE `device_id` = ? AND `date_added` >= ?',
            [$deviceId, $since],
        );
    }

    public function create(array $data): int
    {
        $this->connection->query(
            'INSERT INTO `jellyfin_quick_connect` (`secret`, `code`, `device_id`, `device_name`, `app_name`, `app_version`, `date_added`, `expires`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['secret'],
                $data['code'],
                $data['device_id'],
                $data['device_name'],
                $data['app_name'],
                $data['app_version'],
                $data['date_added'],
                $data['expires'],
            ],
        );

        return $this->connection->getLastInsertedId();
    }

    public function deleteExpired(int $now): void
    {
        $this->connection->query(
            'DELETE FROM `jellyfin_quick_connect` WHERE `expires` < ? OR `consumed` = 1',
            [$now],
        );
    }

    public function findByCode(string $code, int $now): ?array
    {
        $row = $this->connection->fetchRow(
            'SELECT * FROM `jellyfin_quick_connect` WHERE `code` = ? AND `expires` > ? AND `consumed` = 0',
            [$code, $now],
        );

        return ($row === false) ? null : $row;
    }

    public function findBySecret(string $secret, int $now): ?array
    {
        $row = $this->connection->fetchRow(
            'SELECT * FROM `jellyfin_quick_connect` WHERE `secret` = ? AND `expires` > ? AND `consumed` = 0',
            [$secret, $now],
        );

        return ($row === false) ? null : $row;
    }

    public function incrementAuthorizeAttempts(int $id): int
    {
        $this->connection->query(
            'UPDATE `jellyfin_quick_connect` SET `authorize_attempts` = `authorize_attempts` + 1 WHERE `id` = ?',
            [$id],
        );

        return (int) $this->connection->fetchOne(
            'SELECT `authorize_attempts` FROM `jellyfin_quick_connect` WHERE `id` = ?',
            [$id],
        );
    }

    public function markAuthorized(int $id, int $userId): bool
    {
        // the guard is what stops a second caller taking a pairing someone else already bound
        $bound = $this->connection->query(
            'UPDATE `jellyfin_quick_connect` SET `authorized` = 1, `user_id` = ? WHERE `id` = ? AND `user_id` IS NULL',
            [$userId, $id],
        )->rowCount() === 1;

        // approving twice is the same approval, so the owner is told it worked rather than that their code is gone
        return $bound || (int) $this->connection->fetchOne(
            'SELECT `user_id` FROM `jellyfin_quick_connect` WHERE `id` = ?',
            [$id]
        ) === $userId;
    }
}
