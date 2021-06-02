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

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;

final class UserActivityRepository implements UserActivityRepositoryInterface
{

    /**
     * @return int[]
     */
    public function getFriendsActivities(int $user_id, int $limit = 0, int $since = 0): array
    {
        if ($limit < 1) {
            $limit = AmpConfig::get('popular_threshold', 10);
        }

        $params = [$user_id];
        $sql    = "SELECT `user_activity`.`id` FROM `user_activity` INNER JOIN `user_follower` ON `user_follower`.`follow_user` = `user_activity`.`user` WHERE `user_follower`.`user` = ?";
        if ($since > 0) {
            $sql .= " AND `user_activity`.`activity_date` <= ?";
            $params[] = $since;
        }
        $sql .= " ORDER BY `user_activity`.`activity_date` DESC LIMIT " . $limit;
        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * @return int[]
     */
    public function getActivities(
        int $user_id,
        int $limit = 0,
        int $since = 0
    ): array {
        if ($limit < 1) {
            $limit = AmpConfig::get('popular_threshold', 10);
        }

        $params = array($user_id);
        $sql    = "SELECT `id` FROM `user_activity` WHERE `user` = ?";
        if ($since > 0) {
            $sql .= " AND `activity_date` <= ?";
            $params[] = $since;
        }
        $sql .= " ORDER BY `activity_date` DESC LIMIT " . $limit;
        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Delete activity by date
     */
    public function deleteByDate(
        int $date,
        string $action,
        int $user_id = 0
    ): void {
        Dba::write(
            "DELETE FROM `user_activity` WHERE `activity_date` = ? AND `action` = ? AND `user` = ?",
            [$date, $action, $user_id]
        );
    }

    /**
     * Remove activities for items that no longer exist.
     */
    public function collectGarbage(
        ?string $object_type = null,
        ?int $object_id = null
    ): void {
        $types = array('song', 'album', 'artist', 'video', 'tvshow', 'tvshow_season');

        if ($object_type !== null) {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `user_activity` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event(__CLASS__, 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write("DELETE FROM `user_activity` WHERE `object_type` = '$type' AND `user_activity`.`object_id` NOT IN (SELECT `$type`.`id` FROM `$type`);");
            }
            // accidental plays
            Dba::write("DELETE FROM `user_activity` WHERE `object_type` IN ('album', 'artist') AND `action` = 'play';");
        }
    }

    /**
     * Inserts the necessary data to register the playback of a song
     *
     * @todo Replace when active record models are available
     */
    public function registerSongEntry(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date,
        string $songName,
        string $artistName,
        string $albumName,
        string $songMbId,
        string $artistMbId,
        string $albumMbId
    ): void {
        Dba::write(
            'INSERT INTO `user_activity`
                (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_track`, `name_artist`, `name_album`, `mbid_track`, `mbid_artist`, `mbid_album`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $action,
                $objectType,
                $objectId,
                $date,
                $songName,
                $artistName,
                $albumName,
                $songMbId,
                $artistMbId,
                $albumMbId
            ]
        );
    }

    /**
     * Inserts the necessary data to register a generic action on an object
     *
     * @todo Replace when active record models are available
     */
    public function registerGenericEntry(
        int $userId,
        string $action,
        string $object_type,
        int $objectId,
        int $date
    ): void {
        Dba::write(
            "INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`) VALUES (?, ?, ?, ?, ?)",
            [$userId, $action, $object_type, $objectId, $date]
        );
    }

    /**
     * Inserts the necessary data to register an artist related action
     *
     * @todo Replace when active record models are available
     */
    public function registerArtistEntry(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date,
        string $artistName,
        string $artistMbId
    ): void {
        Dba::write(
            "INSERT INTO `user_activity`
                (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_artist`, `mbid_artist`)
                VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $action,
                $objectType,
                $objectId,
                $date,
                empty($artistMbId)
                    ? null
                    : $artistMbId,
                $artistMbId,
            ]
        );
    }

    /**
     * Inserts the necessary data to register the playback of a song
     *
     * @todo Replace when active record models are available
     */
    public function registerAlbumEntry(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date,
        string $artistName,
        string $albumName,
        string $artistMbId,
        string $albumMbId
    ): void {
        Dba::write(
            'INSERT INTO `user_activity`
                (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_artist`, `name_album`, `mbid_artist`, `mbid_album`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $action,
                $objectType,
                $objectId,
                $date,
                $artistName,
                $albumName,
                empty($artistMbId)
                    ? null
                    : $artistMbId,
                empty($albumMbId)
                    ? null
                    : $albumMbId
            ]
        );
    }
}
