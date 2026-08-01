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

namespace Ampache\Repository\Model;

use Ampache\Repository\UserActivityRepositoryInterface;

class Useractivity extends database_object
{
    protected const string DB_TABLENAME = 'user_activity';

    public string $action;
    public int $activity_date;
    public int $id = 0;
    public int $object_id;
    public string $object_type;
    public int $user;

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull the flag for
     */
    public function __construct(?int $useract_id = 0)
    {
        if (!$useract_id) {
            return;
        }

        $info                = $this->get_info($useract_id, static::DB_TABLENAME);
        $this->action        = (string) ($info['action'] ?? '');
        $this->activity_date = (int) ($info['activity_date'] ?? 0);
        $this->id            = (int) ($info['id'] ?? 0);
        $this->object_id     = (int) ($info['object_id'] ?? 0);
        $this->object_type   = (string) ($info['object_type'] ?? '');
        $this->user          = (int) ($info['user'] ?? 0);
    }

    /**
     * this attempts to build a cache of the data from the passed activities all in one query
     * @param array<int|string> $ids
     */
    public static function build_cache(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        foreach (self::getUserActivityRepository()->getRowsByIds(array_values($ids)) as $row) {
            parent::add_to_cache('user_activity', $row['id'], $row);
        }

        return true;
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        self::getUserActivityRepository()->migrate($object_type, $old_object_id, $new_object_id);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserActivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }

    public function getId(): int
    {
        return $this->id;
    }
}
