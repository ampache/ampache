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

final class RatingRepository implements RatingRepositoryInterface
{
    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        Dba::write(
            "UPDATE IGNORE `rating` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?",
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    /**
     * Remove ratings for items that no longer exist.
     */
    public function collectGarbage(?string $objectType = null, ?int $objectId = null): void
    {
        $types = [
            'song',
            'album',
            'artist',
            'video',
            'tvshow',
            'tvshow_season',
            'playlist',
            'label',
            'podcast',
            'podcast_episode'
        ];

        if ($objectType !== null && $objectType !== '') {
            if (in_array($objectType, $types)) {
                $sql = 'DELETE FROM `rating` WHERE `object_type` = ? AND `object_id` = ?';
                Dba::write($sql, [$objectType, $objectId]);
            } else {
                debug_event(self::class, 'Garbage collect on type `' . $objectType . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write('DELETE FROM `rating` WHERE `object_type` = \'$type\' AND `rating`.`object_id` NOT IN (SELECT `$type`.`id` FROM `$type`);');
            }
        }
        // delete 'empty' ratings
        Dba::write('DELETE FROM `rating` WHERE `rating`.`rating` = 0');
    }
}
