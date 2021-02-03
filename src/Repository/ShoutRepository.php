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
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Module\System\Dba;

final class ShoutRepository implements ShoutRepositoryInterface
{
    /**
     * @return int[]
     */
    public function getBy(string $object_type, int $object_id): array
    {
        $sql        = 'SELECT `id` FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ? ORDER BY `sticky`, `date` DESC';
        $db_results = Dba::read($sql, [$object_type, $object_id]);
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Cleans out orphaned shoutbox items
     */
    public function collectGarbage(
        ?string $object_type = null,
        ?int $object_id = null
    ): void {
        $types = array('song', 'album', 'artist', 'label');

        if ($object_type !== null) {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event(__CLASS__, 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write("DELETE FROM `user_shout` USING `user_shout` LEFT JOIN `$type` ON `$type`.`id` = `user_shout`.`object_id` WHERE `$type`.`id` IS NULL AND `user_shout`.`object_type` = '$type'");
            }
        }
    }

    /**
     * this function deletes the shoutbox entry
     */
    public function delete(int $shoutboxId): void
    {
        Dba::write(
            'DELETE FROM `user_shout` WHERE `id` = ?',
            [$shoutboxId]
        );
    }

    /**
     * This returns the top user_shouts, shoutbox objects are always shown regardless and count against the total
     * number of objects shown
     *
     * @return int[]
     */
    public function getTop(int $limit, ?int $userId = null): array
    {
        $sql        = 'SELECT `id` FROM `user_shout` WHERE `sticky`=\'1\' ORDER BY `date` DESC';
        $db_results = Dba::read($sql);

        $shouts = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $shouts[] = (int) $row['id'];
        }

        // If we've already got too many stop here
        if (count($shouts) > $limit) {
            $shouts = array_slice($shouts, 0, $limit);

            return $shouts;
        }

        // Only get as many as we need
        $limit  = (int)($limit) - count($shouts);
        $params = [];
        $sql    = 'SELECT `id` FROM `user_shout` WHERE `sticky`=\'0\' ';
        if ($userId !== null) {
            $sql .= 'AND `user` = ? ';
            $params[] = $userId;
        }
        $sql .= sprintf('ORDER BY `date` DESC LIMIT %d', $limit);
        $db_results = Dba::read($sql, $params);

        while ($row = Dba::fetch_assoc($db_results)) {
            $shouts[] = (int) $row['id'];
        }

        return $shouts;
    }
}
