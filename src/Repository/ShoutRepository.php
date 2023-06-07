<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
                Dba::write("DELETE FROM `user_shout` USING `user_shout` LEFT JOIN `$type` ON `$type`.`id` = `user_shout`.`object_id` WHERE `$type`.`id` IS NULL AND `user_shout`.`object_type` = '$type';");
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
}
