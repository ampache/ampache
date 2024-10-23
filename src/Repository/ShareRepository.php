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

namespace Ampache\Repository;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use DateTimeInterface;

/**
 * Manages share related database access
 *
 * Tables: `share`
 */
final readonly class ShareRepository implements ShareRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private ConfigContainerInterface $configContainer
    ) {
    }

    /**
     * Finds a single item by its id
     */
    public function findById(int $itemId): ?Share
    {
        $result = new Share($itemId);
        if ($result->isNew()) {
            return null;
        }

        return $result;
    }

    /**
     * Migrate a share associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE `share` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    /**
     * Cleanup old shares
     */
    public function collectGarbage(): void
    {
        $this->connection->query(
            'DELETE FROM `share` WHERE (`expire_days` > 0 AND (`creation_date` + (`expire_days` * 86400)) < UNIX_TIMESTAMP()) OR (`max_counter` > 0 AND `counter` >= `max_counter`)',
        );
    }

    /**
     * Returns the ids of all items the user has access to
     *
     * @return list<int>
     */
    public function getIdsByUser(User $user): array
    {
        $userId = $user->getId();
        $params = [];

        $sql = 'SELECT `id` FROM `share` WHERE ';

        if (!$user->has_access(AccessLevelEnum::MANAGER)) {
            $sql .= '`user` = ?';
            $params[] = $userId;
        } else {
            $sql .= '1=1';
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_FILTER)) {
            $sql .= sprintf(' AND%s', Catalog::get_user_filter('share', $userId));
        }

        $result = $this->connection->query(
            $sql,
            $params
        );

        $items = [];

        while ($rowId = $result->fetchColumn()) {
            $items[] = (int) $rowId;
        }

        return $items;
    }

    /**
     * Deletes a single item
     */
    public function delete(Share $item): void
    {
        $this->connection->query(
            'DELETE FROM `share` WHERE `id` = ?',
            [$item->getId()]
        );
    }

    /**
     * Sets the last access-date and raises the counter
     */
    public function registerAccess(Share $share, DateTimeInterface $date): void
    {
        $this->connection->query(
            'UPDATE `share` SET `counter` = (`counter` + 1), lastvisit_date = ? WHERE `id` = ?',
            [$date->getTimestamp(), $share->getId()]
        );
    }
}
