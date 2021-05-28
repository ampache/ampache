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

declare(strict_types=0);

namespace Ampache\Module\Art;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Art;

final class ArtDuplicator implements ArtDuplicatorInterface
{
    /**
     * Duplicate an object associate images to a new object
     * @param string $objectType
     * @param integer $oldObjectId
     * @param integer $newObjectId
     */
    public function duplicate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        if (Art::has_db($newObjectId, $objectType)) {
            return;
        }
        debug_event(self::class, 'duplicate... type:' . $objectType . ' old_id:' . $oldObjectId . ' new_id:' . $newObjectId, 5);
        if (AmpConfig::get('album_art_store_disk')) {
            $sql        = "SELECT `size`, `kind` FROM `image` WHERE `object_type` = ? AND `object_id` = ?";
            $db_results = Dba::read($sql, array($objectType, $oldObjectId));
            while ($row = Dba::fetch_assoc($db_results)) {
                $image = Art::read_from_dir($row['size'], $objectType, $oldObjectId, $row['kind']);
                if ($image !== null) {
                    Art::write_to_dir($image, $row['size'], $objectType, $newObjectId, $row['kind']);
                }
            }
        }

        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `object_type`, `object_id`, `kind`) SELECT `image`, `mime`, `size`, `object_type`, ? as `object_id`, `kind` FROM `image` WHERE `object_type` = ? AND `object_id` = ?";

        Dba::write($sql, array($newObjectId, $objectType, $oldObjectId));
    }
}
