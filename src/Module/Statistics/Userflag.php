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

namespace Ampache\Module\Statistics;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Database\database_object;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Plugin\PluginSaveMediaplayInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserflagRepositoryInterface;
use Exception;

/**
 * This user flag/unflag songs, albums, artists, videos... as favorite.
 */
class Userflag extends database_object
{
    protected const string DB_TABLENAME = 'user_flag';
    private const array FLAG_TYPES      = [
        'album_disk',
        'album',
        'artist',
        'collection',
        'folder',
        'live_stream',
        'playlist',
        'podcast_episode',
        'podcast',
        'search',
        'song',
        'stream',
        'video',
    ];

    // Public variables
    public int $id; // The object_id of the object flagged
    public string $type; // The object_type of object we want

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull the flag for
     */
    public function __construct(
        ?int $object_id,
        string $type,
    ) {
        $this->id   = (int) ($object_id);
        $this->type = $type;
    }

    /**
     * build_cache
     * This attempts to get everything we'll need for this page load in a
     * single query, saving on connection overhead
     * @param array<int|string> $ids
     */
    public static function build_cache(string $type, array $ids, ?int $user_id = null): bool
    {
        if (empty($ids)) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        if ($user_id === 0) {
            return false;
        }

        $userflags = self::getUserflagRepository()->getFlagDates($type, array_values($ids), $user_id);

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
     */
    public static function garbage_collection(?string $object_type = null, ?int $object_id = null): void
    {
        self::getUserflagRepository()->collectGarbage($object_type, $object_id);
    }

    /**
     * get_latest
     * Get the latest user flagged objects
     * @return int[]
     */
    public static function get_latest(
        string $type,
        ?User $user = null,
        int $count = 0,
        int $offset = 0,
        int $since = 0,
        int $before = 0,
        bool $by_user = false,
        int $catalog_id = 0,
    ): array {
        if ($count === 0) {
            $count = AmpConfig::get('popular_threshold', 10);
        }

        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        return self::getUserflagRepository()->findLatestIds($type, $user, $count, $offset, $since, $before, $by_user, $catalog_id);
    }

    public static function is_valid(string $type): bool
    {
        return in_array($type, self::FLAG_TYPES);
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        self::getUserflagRepository()->migrate($object_type, $old_object_id, $new_object_id);
    }

    /**
     * save_flag
     * Forward flag to last.fm and Libre.fm (song only)
     */
    public static function save_flag(User $user, Song $song, bool $flagged): void
    {
        foreach (Plugin::get_plugins(PluginTypeEnum::USER_FLAG_MANAGER) as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin instanceof PluginSaveMediaplayInterface && $plugin->load($user)) {
                    debug_event(self::class, 'save_flag...' . $plugin_name, 5);
                    $plugin->_plugin->set_flag($song, $flagged);
                }
            } catch (Exception $error) {
                debug_event(self::class, 'save_flag plugin error: ' . $error->getMessage(), 1);
            }
        }
    }

    /**
     * show
     * This takes an id and a type and displays the flag statemenabled.
     */
    public static function show(int $object_id, string $type): string
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
            $action = $base_url . '&userflag=0';
            $source = 'userflag_i_' . $userflag->id . '_' . $userflag->type;
            $icon   = 'favorite-fill';
            $alt    = T_('Unfavorite');
        } else {
            $action = $base_url . '&userflag=1';
            $source = 'userflag_i_' . $userflag->id . '_' . $userflag->type;
            $icon   = 'favorite';
            $alt    = T_('Favorite');
        }

        return Ajax::button($action, $icon, $alt, $source);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserflagRepository(): UserflagRepositoryInterface
    {
        global $dic;

        return $dic->get(UserflagRepositoryInterface::class);
    }

    /**
     * get_flag
     * @return bool|array{bool, int}
     */
    public function get_flag(?int $user_id = null, bool $get_date = false): bool|array
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
                return [
                    (bool) $object[0],
                    (int) $object[1],
                ];
            }

            return (bool) $object[0];
        }

        $flagged = false;
        $date    = self::getUserflagRepository()->getFlagDate($this->id, $this->type, $user_id);
        if ($date !== null) {
            // always cache the date in case it's called by subsonic
            parent::add_to_cache($key, $this->id, [true, $date]);
            if ($get_date) {
                return [
                    true,
                    $date
                ];
            }

            $flagged = true;
        }

        return $flagged;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * set_flag
     * This function sets the user flag for the current object.
     * If no user_id is passed in, we use the currently logged in user.
     */
    public function set_flag(bool $flagged, ?int $user_id = null, ?int $date = null): bool
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        if ($user_id === 0) {
            return false;
        }

        if (!self::is_valid($this->type)) {
            return false;
        }

        if ($this->get_flag($user_id) === $flagged) {
            return true;
        }

        $date = $date ?? time();

        debug_event(self::class, sprintf('Setting userflag for %s %d to %s (%s)', $this->type, $this->id, $flagged, $date), 4);

        $repository = self::getUserflagRepository();

        if (!$flagged) {
            parent::add_to_cache('userflag_' . $this->type . '_user' . $user_id, $this->id, [false]);
            $repository->adjustWeight($this->type, $this->id, -1);
            $repository->deleteFlag($this->id, $this->type, $user_id);
        } else {
            parent::add_to_cache('userflag_' . $this->type . '_user' . $user_id, $this->id, [1, $date]);
            $this->getUserActivityPoster()->post((int) $user_id, 'userflag', $this->type, $this->id, $date);
            $repository->adjustWeight($this->type, $this->id, 1);
            $repository->setFlag($this->id, $this->type, $user_id, $date);
        }

        if ($this->type == 'song') {
            $user = new User($user_id);
            $song = new Song($this->id);
            if ($song->isNew() === false) {
                self::save_flag($user, $song, $flagged);
            }
        }

        return true;
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
