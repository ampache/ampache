<?php

declare(strict_types=0);

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

namespace Ampache\Repository\Model;

use Ampache\Module\Api\Ajax;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Exception;

/**
 * This user flag/unflag songs, albums, artists, videos... as favorite.
 */
class Userflag extends database_object
{
    protected const DB_TABLENAME = 'user_flag';

    // Public variables
    public int $id; // The object_id of the object flagged
    public string $type; // The object_type of object we want

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull the flag for
     * @param int|null $object_id
     * @param string $type
     */
    public function __construct(
        $object_id,
        $type
    ) {
        $this->id   = (int)($object_id);
        $this->type = $type;
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * build_cache
     * This attempts to get everything we'll need for this page load in a
     * single query, saving on connection overhead
     * @param string $type
     * @param array $ids
     * @param int $user_id
     */
    public static function build_cache($type, $ids, $user_id = null): bool
    {
        if (empty($ids)) {
            return false;
        }

        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        if ($user_id === 0) {
            return false;
        }

        $userflags  = [];
        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = sprintf('SELECT `object_id`, `date` FROM `user_flag` WHERE `user` = ? AND `object_id` IN %s AND `object_type` = ?', $idlist);
        $db_results = Dba::read($sql, [$user_id, $type]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $userflags[$row['object_id']] = $row['date'];
        }

        foreach ($ids as $object_id) {
            if (isset($userflags[$object_id])) {
                parent::add_to_cache(
                    'userflag_' . $type . '_user' . $user_id,
                    $object_id,
                    [1, $userflags[$object_id]]
                );
            } else {
                parent::add_to_cache('userflag_' . $type . '_user' . $user_id, $object_id, [false]);
            }
        }

        return true;
    }

    /**
     * garbage_collection
     *
     * Remove userflag for items that no longer exist.
     * @param string $object_type
     * @param int $object_id
     */
    public static function garbage_collection($object_type = null, $object_id = null): void
    {
        $types = [
            'album',
            'album_disk',
            'artist',
            'catalog',
            'tag',
            'label',
            'live_stream',
            'playlist',
            'podcast',
            'podcast_episode',
            'search',
            'song',
            'user',
            'video',
        ];

        if ($object_type !== null) {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `user_flag` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, [$object_type, $object_id]);
            } else {
                debug_event(self::class, 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write(sprintf('DELETE FROM `user_flag` WHERE `object_type` = \'%s\' AND `user_flag`.`object_id` NOT IN (SELECT `%s`.`id` FROM `%s`);', $type, $type, $type));
            }
        }
    }

    /**
     * get_flag
     * @param int $user_id
     * @param bool $get_date
     * @return bool|array
     */
    public function get_flag($user_id = null, $get_date = false)
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        if ($user_id === 0) {
            return false;
        }

        $key = 'userflag_' . $this->type . '_user' . $user_id;
        if (parent::is_cached($key, $this->id)) {
            $object = parent::get_from_cache($key, $this->id);
            if (empty($object) || !$object[0]) {
                return false;
            }

            if ($get_date) {
                return $object;
            }

            return (bool)$object[0];
        }

        $flagged    = false;
        $sql        = "SELECT `id`, `date` FROM `user_flag` WHERE `user` = ? AND `object_id` = ? AND `object_type` = ?";
        $db_results = Dba::read($sql, [$user_id, $this->id, $this->type]);
        if ($row = Dba::fetch_assoc($db_results)) {
            // always cache the date in case it's called by subsonic
            parent::add_to_cache($key, $this->id, [true, $row['date']]);
            if ($get_date) {
                return [
                    true,
                    $row['date']
                ];
            }

            $flagged = true;
        }

        return $flagged;
    }

    /**
     * set_flag
     * This function sets the user flag for the current object.
     * If no user_id is passed in, we use the currently logged in user.
     * @param bool $flagged
     * @param int $user_id
     * @param int|null $date
     */
    public function set_flag($flagged, $user_id = null, $date = null): bool
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        if ($user_id === 0) {
            return false;
        }

        $date = $date ?? time();

        debug_event(self::class, sprintf('Setting userflag for %s %d to %s (%s)', $this->type, $this->id, $flagged, $date), 4);

        if (!$flagged) {
            $sql    = "DELETE FROM `user_flag` WHERE `object_id` = ? AND `object_type` = ? AND `user` = ?";
            $params = [$this->id, $this->type, $user_id];
            parent::add_to_cache('userflag_' . $this->type . '_user' . $user_id, $this->id, [false]);
        } else {
            $sql    = "REPLACE INTO `user_flag` (`object_id`, `object_type`, `user`, `date`) VALUES (?, ?, ?, ?)";
            $params = [$this->id, $this->type, $user_id, $date];
            parent::add_to_cache('userflag_' . $this->type . '_user' . $user_id, $this->id, [1, $date]);

            $this->getUserActivityPoster()->post((int) $user_id, 'userflag', $this->type, $this->id, $date);
        }

        Dba::write($sql, $params);

        if ($this->type == 'song') {
            $user = new User($user_id);
            $song = new Song($this->id);
            if ($song->isNew() === false) {
                $song->format();
                self::save_flag($user, $song, $flagged);
            }
        }

        return true;
    }

    /**
     * save_flag
     * Forward flag to last.fm and Libre.fm (song only)
     * @param User $user
     * @param Song $song
     * @param bool $flagged
     */
    public static function save_flag($user, $song, $flagged): void
    {
        foreach (Plugin::get_plugins(PluginTypeEnum::USER_FLAG_MANAGER) as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin !== null && $plugin->load($user)) {
                    debug_event(self::class, 'save_flag...' . $plugin->_plugin->name, 5);
                    $plugin->_plugin->set_flag($song, $flagged);
                }
            } catch (Exception $error) {
                debug_event(self::class, 'save_flag plugin error: ' . $error->getMessage(), 1);
            }
        }
    }

    /**
     * get_latest_sql
     * Get the latest sql
     * @param string $input_type
     * @param int $since
     * @param int $before
     */
    public static function get_latest_sql(
        $input_type,
        ?User $user = null,
        $since = 0,
        $before = 0
    ): string {
        $type    = Stats::validate_type($input_type);
        $sql     = "SELECT DISTINCT(`user_flag`.`object_id`) AS `id`, COUNT(DISTINCT(`user_flag`.`user`)) AS `count`, `user_flag`.`object_type` AS `type`, MAX(`user_flag`.`user`) AS `user`, MAX(`user_flag`.`date`) AS `date` FROM `user_flag`";
        if ($input_type == 'album_artist' || $input_type == 'song_artist') {
            $sql .= " LEFT JOIN `artist` ON `artist`.`id` = `user_flag`.`object_id` AND `user_flag`.`object_type` = 'artist'";
        }

        $sql .= ($user !== null)
            ? " WHERE `user_flag`.`object_type` = '" . $type . "' AND `user_flag`.`user` = '" . $user->getId() . "'"
            : " WHERE `user_flag`.`object_type` = '" . $type . "'";
        if (AmpConfig::get('catalog_disable') && in_array($type, ['artist', 'album', 'album_disk', 'song', 'video'])) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }

        if (AmpConfig::get('catalog_filter') && $user !== null) {
            $sql .= " AND" . Catalog::get_user_filter('user_flag_' . $type, $user->getId());
        }

        if ($input_type == 'album_artist') {
            $sql .= " AND `artist`.`album_count` > 0";
        }

        if ($input_type == 'song_artist') {
            $sql .= " AND `artist`.`song_count` > 0";
        }

        if ($since > 0) {
            $sql .= " AND `user_flag`.`date` >= '" . $since . "'";
            if ($before > 0) {
                $sql .= " AND `user_flag`.`date` <= '" . $before . "'";
            }
        }


        //debug_event(self::class, 'get_latest_sql ' . $sql, 5);

        return $sql . " GROUP BY `user_flag`.`object_id`, `type` ORDER BY `count` DESC, `date` DESC ";
    }

    /**
     * get_latest
     * Get the latest user flagged objects
     * @param string $type
     * @param int $count
     * @param int $offset
     * @param int $since
     * @param int $before
     * @return int[]
     */
    public static function get_latest(
        $type,
        ?User $user = null,
        $count = 0,
        $offset = 0,
        $since = 0,
        $before = 0
    ): array {
        if ($count === 0) {
            $count = AmpConfig::get('popular_threshold', 10);
        }

        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        // Select Top objects counting by # of rows
        $sql   = self::get_latest_sql($type, $user, $since, $before);
        $limit = ($offset < 1)
            ? $count
            : $offset . "," . $count;
        if ($limit > 0) {
            $sql .= 'LIMIT ' . $limit;
        }

        //debug_event(self::class, 'get_latest ' . $sql, 5);
        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * show
     * This takes an id and a type and displays the flag state
     * enabled.
     * @param int $object_id
     * @param string $type
     */
    public static function show($object_id, $type): string
    {
        // If user flags aren't enabled don't do anything
        if (!AmpConfig::get('ratings')) {
            return '';
        }

        $userflag = new Userflag($object_id, $type);

        $base_url = sprintf(
            '?action=set_userflag&userflag_type=%s&object_id=%d',
            $userflag->type,
            $userflag->id
        );

        if ($userflag->get_flag()) {
            $text = Ajax::text(
                $base_url . '&userflag=0',
                '',
                'userflag_i_' . $userflag->id . '_' . $userflag->type,
                '',
                'userflag_true'
            );
        } else {
            $text = Ajax::text(
                $base_url . '&userflag=1',
                '',
                'userflag_i_' . $userflag->id . '_' . $userflag->type,
                '',
                'userflag_false'
            );
        }

        return sprintf('<span class="userflag">%s</span>', $text);
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     */
    public static function migrate($object_type, $old_object_id, $new_object_id): void
    {
        $sql = "UPDATE IGNORE `user_flag` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        Dba::write($sql, [$new_object_id, $object_type, $old_object_id]);
    }

    /**
     * @deprecated inject dependency
     */
    private function getUserActivityPoster(): UserActivityPosterInterface
    {
        global $dic;

        return $dic->get(UserActivityPosterInterface::class);
    }
}
