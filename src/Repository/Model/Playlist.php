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

use Ampache\Config\AmpConfig;
use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Database\database_object;
use Ampache\Module\System\Core;
use Ampache\Module\System\Preference;
use Ampache\Repository\PlaylistRepositoryInterface;
use Override;

/**
 * This class handles playlists in ampache. it references the playlist* tables
 */
class Playlist extends playlist_object
{
    protected const string DB_TABLENAME = 'playlist';

    /**
     * @var array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track: int,
     *     track_id: int,
     *     time: int
     * }>
     */
    public array $items = [];

    /**
     * Constructor
     * This takes a playlist_id as an optional argument and gathers the information
     * if not playlist_id is passed returns false (or if it isn't found
     */
    public function __construct(?int $object_id = 0)
    {
        if (!$object_id) {
            return;
        }

        $info                = $this->get_info($object_id, static::DB_TABLENAME);
        $this->id            = (int) ($info['id'] ?? 0);
        $this->name          = $info['name'] ?? null;
        $this->user          = isset($info['user']) ? (int) $info['user'] : null;
        $this->username      = $info['username'] ?? null;
        $this->type          = $info['type'] ?? null;
        $this->date          = (int) ($info['date'] ?? 0);
        $this->last_update   = isset($info['last_update']) ? (int) $info['last_update'] : 0;
        $this->last_count    = isset($info['last_count']) ? (int) $info['last_count'] : 0;
        $this->last_duration = isset($info['last_duration']) ? (int) $info['last_duration'] : 0;
        $this->collaborate   = $info['collaborate'] ?? '';
    }

    /**
     * build_cache
     * This is what builds the cache from the objects
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

        foreach (self::getPlaylistRepository()->getRowsByIds(array_values($ids)) as $row) {
            parent::add_to_cache('playlist', $row['id'], $row);
        }

        Art::build_cache($ids, 'playlist');

        return true;
    }

    /**
     * check
     * This function creates an empty playlist, gives it a name and type
     */
    public static function check(string $name, string $type, ?int $user_id = null): int
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? -1;
        }

        // return the duplicate ID
        return self::getPlaylistRepository()->findIdByName($name, $user_id, $type) ?? 0;
    }

    /**
     * create
     * This function creates an empty playlist, gives it a name and type
     */
    public static function create(string $name, string $type, ?int $user_id = null, bool $existing = true): ?int
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? -1;
        }

        // check for duplicates
        $existing_id = self::check($name, $type, $user_id);
        if ($existing_id > 0) {
            if (!$existing) {
                return null;
            }

            return $existing_id;
        }

        // get the public_name/username
        $username = User::get_username($user_id);

        $insert_id = self::getPlaylistRepository()->insert($name, $user_id, (string) $username, $type, time());
        if ($insert_id === null) {
            return null;
        }

        self::getCatalogCounter()->count(CountableTableEnum::PLAYLIST);

        return $insert_id;
    }

    /**
     * garbage_collection
     *
     * Clean dead items out of playlists
     */
    public static function garbage_collection(): void
    {
        self::getPlaylistRepository()->collectGarbage();
    }

    /**
     * get_playlist_array
     * Returns a list of playlists accessible by the user with formatted name.
     * @return string[]
     */
    public static function get_playlist_array(?int $user_id = null): array
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        $key = 'playlistarray';
        if ($user_id > 0 && parent::is_cached($key, $user_id)) {
            return parent::get_from_cache($key, $user_id);
        }

        $is_admin = (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user_id) || $user_id == -1);
        $results  = self::getPlaylistRepository()->findNames($user_id, $is_admin);

        parent::add_to_cache($key, $user_id, $results);

        return $results;
    }

    /**
     * get_playlists
     * Returns a list of playlists accessible by the user.
     * @return int[]
     */
    public static function get_playlists(
        ?int $user_id = null,
        ?string $playlist_name = '',
        ?bool $like = true,
        ?bool $includePublic = true,
        ?bool $includeHidden = true,
        ?bool $userOnly = false,
    ): array {
        if (!$user_id) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        $key = ($includePublic)
            ? 'playlistids'
            : 'accessibleplaylistids';
        if (empty($playlist_name) && ($user_id > 0 && parent::is_cached($key, $user_id))) {
            return parent::get_from_cache($key, $user_id);
        }

        $is_admin = (
            $userOnly === false
            || (
                Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user_id)
                || $user_id == -1
            )
        );
        $hide_string = ($includeHidden)
            ? null
            : str_replace('%', '\%', str_replace('_', '\_', (string) Preference::get_by_user($user_id, 'api_hidden_playlists')));

        $results = self::getPlaylistRepository()->findIds($user_id, $is_admin, (bool) $includePublic, (string) $playlist_name, (bool) $like, $hide_string);

        if (
            $playlist_name === ''
            || $playlist_name === '0'
        ) {
            parent::add_to_cache($key, $user_id, $results);
        }

        return $results;
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        self::getPlaylistRepository()->migrateObject($object_type, $old_object_id, $new_object_id);
    }

    /**
     * Splits the id list of a playlist_search browse, which mixes playlist ids with `smart_` prefixed search ids
     *
     * @param array<int|string> $object_ids
     *
     * @return array{playlist: list<int>, search: list<int>}
     */
    public static function split_mixed_ids(array $object_ids): array
    {
        $split = ['playlist' => [], 'search' => []];
        foreach ($object_ids as $object_id) {
            if (is_string($object_id) && str_starts_with($object_id, 'smart_')) {
                $split['search'][] = (int) substr($object_id, 6);
            } else {
                $split['playlist'][] = (int) $object_id;
            }
        }

        return $split;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getCatalogCounter(): CatalogCounterInterface
    {
        global $dic;

        return $dic->get(CatalogCounterInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getPlaylistRepository(): PlaylistRepositoryInterface
    {
        global $dic;

        return $dic->get(PlaylistRepositoryInterface::class);
    }

    /**
     * add_medias
     * @param array<array{object_type: LibraryItemEnum|string, object_id: int}> $medias
     */
    public function add_medias(array $medias): bool
    {
        if ($medias === []) {
            return false;
        }

        debug_event(self::class, "add_medias to: " . $this->id, 5);
        $unique     = (bool) AmpConfig::get('unique_playlist', false);
        $track_data = ($unique)
            ? $this->get_songs()
            : [];
        $base_track = self::getPlaylistRepository()->getLastTrackNumber($this);
        $count      = 0;
        $rows       = [];
        foreach ($medias as $data) {
            $object_type = (is_string($data['object_type']))
                ? LibraryItemEnum::tryFrom((string) $data['object_type'])
                : $data['object_type'];
            if ($unique && in_array($data['object_id'], $track_data)) {
                debug_event(self::class, "Can't add a duplicate " . $object_type?->value . " (" . $data['object_id'] . ") when unique_playlist is enabled", 3);
            } else {
                ++$count;
                $rows[] = [(int) $data['object_id'], $object_type?->value, $base_track + $count];
            } // if valid id
        }

        if ($rows !== []) {
            self::getPlaylistRepository()->addTracks($this, $rows);
            debug_event(self::class, sprintf('Added %d tracks to playlist: ', $count) . $this->id, 5);
            $this->_update_last();

            return true;
        }

        return false;
    }

    /**
     * @param int[]|string[] $song_ids
     * This takes an array of song_ids and then adds it to the playlist
     */
    public function add_songs(iterable $song_ids = []): bool
    {
        $medias = [];
        foreach ($song_ids as $song_id) {
            $medias[] = [
                'object_type' => LibraryItemEnum::SONG,
                'object_id' => (int) $song_id,
            ];
        }

        if ($this->add_medias($medias)) {
            Catalog::update_mapping('playlist');

            return true;
        }

        return false;
    }

    /**
     * delete
     * This deletes the current playlist and all associated data
     */
    public function delete(): bool
    {
        self::getPlaylistRepository()->delete($this);

        $this->getPlaylistObjectRepository()->deleteCollaborators($this);

        return true;
    }

    /**
     * delete_all
     *
     * this deletes all tracks from a playlist, you specify the playlist.id here
     */
    public function delete_all(): bool
    {
        self::getPlaylistRepository()->deleteAllTracks($this);
        debug_event(self::class, 'Delete all tracks from: ' . $this->id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * delete_song
     * this deletes a single track, you specify the playlist_data.id here
     */
    public function delete_song(int $object_id): bool
    {
        self::getPlaylistRepository()->deleteTrackByObjectId($this, $object_id);
        debug_event(self::class, 'Delete object_id: ' . $object_id . ' from ' . $this->id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * delete_track
     * this deletes a single track, you specify the playlist_data.id here
     */
    public function delete_track(int $object_id): bool
    {
        self::getPlaylistRepository()->deleteTrackById($this, $object_id);
        debug_event(self::class, 'Delete item_id: ' . $object_id . ' from ' . $this->id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * delete_track_number
     * this deletes a single track by it's track #, you specify the playlist_data.track here
     */
    public function delete_track_number(int $track): bool
    {
        self::getPlaylistRepository()->deleteTrackByNumber($this, $track);
        debug_event(self::class, 'Delete track: ' . $track . ' from ' . $this->id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * Get item f_time, from the cached last_duration rather than summing the songs on every call
     */
    #[Override]
    public function get_f_time(): string
    {
        $duration = (int) $this->last_duration;
        $min      = sprintf("%02d", (floor($duration / 60) % 60));
        $sec      = sprintf("%02d", ($duration % 60));
        $hours    = floor($duration / 3600);

        return ltrim($hours . ':' . $min . ':' . $sec, '0:');
    }

    /**
     * get_items
     * This returns an array of playlist medias that are in this playlist.
     * Because the same media can be on the same playlist twice they are
     * keyed by the uid from playlist_data
     * @return array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track_id: int,
     *     track: int,
     *     time: int
     * }>
     */
    public function get_items(): array
    {
        if ($this->isNew()) {
            return [];
        }

        $results = [];
        $user    = Core::get_global('user');
        $user_id = $user->id ?? -1;

        $repository    = self::getPlaylistRepository();
        $catalogFilter = (bool) AmpConfig::get('catalog_filter');

        foreach ($repository->getObjectTypes($this->id) as $type) {
            $object_type = LibraryItemEnum::from($type);
            foreach ($repository->getItemsOfType($this->id, $type, $user_id, $catalogFilter, true, false) as $row) {
                $results[] = [
                    'object_type' => LibraryItemEnum::from($row['object_type']),
                    'object_id' => (int) $row['object_id'],
                    'track_id' => $row['id'],
                    'track' => (int) $row['track'],
                    'time' => (int) $row['time'],
                ];
            }
        }

        // sort these object types by the track column
        $tracks = array_column($results, 'track');
        array_multisort($tracks, SORT_ASC, $results);

        return $results;
    }

    /**
     * get_media_count
     * This simply returns a int of how many media elements exist in this playlist
     * For now let's consider a dyn_media a single entry
     */
    public function get_media_count(string $type = ''): int
    {
        $user = Core::get_global('user');

        return self::getPlaylistRepository()->getMediaCount(
            $this->id,
            $type,
            $user->id ?? -1,
            (bool) AmpConfig::get('catalog_filter')
        );
    }

    /**
     * get_random_items
     * This is the same as before but we randomize the buggers!
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int, track: int, track_id: int}>
     */
    public function get_random_items(?string $limit = ''): array
    {
        $results       = [];
        $user          = Core::get_global('user');
        $user_id       = $user->id ?? -1;
        $repository    = self::getPlaylistRepository();
        $catalogFilter = (bool) AmpConfig::get('catalog_filter');

        foreach ($repository->getObjectTypes($this->id) as $type) {
            foreach ($repository->getItemsOfType($this->id, $type, $user_id, $catalogFilter, false, true, (string) $limit) as $row) {
                $results[] = [
                    'object_type' => LibraryItemEnum::from($row['object_type']),
                    'object_id' => (int) $row['object_id'],
                    'track' => (int) $row['track'],
                    'track_id' => $row['id'],
                ];
            }
        }

        return $results;
    }

    /**
     * get_songs
     * This is called by the batch script, because we can't pass in Dynamic objects they pulled once and then their
     * target song.id is pushed into the array
     * @return int[]
     */
    public function get_songs(): array
    {
        $user    = Core::get_global('user');
        $results = [];

        foreach (
            self::getPlaylistRepository()->getItemsOfType(
                $this->id,
                'song',
                $user->id ?? -1,
                (bool) AmpConfig::get('catalog_filter'),
                false,
                false
            ) as $row
        ) {
            $results[] = (int) $row['object_id'];
        }

        return $results;
    }

    /**
     * get_total_duration
     * Get the total duration of every item in the playlist that has a duration (songs, videos, podcast episodes).
     */
    public function get_total_duration(): int
    {
        $user          = Core::get_global('user');
        $userId        = $user->id ?? -1;
        $repository    = self::getPlaylistRepository();
        $catalogFilter = (bool) AmpConfig::get('catalog_filter');

        $total = 0;
        foreach ($repository->getObjectTypes($this->id) as $type) {
            foreach ($repository->getItemsOfType($this->id, $type, $userId, $catalogFilter, true, false) as $row) {
                $total += (int) $row['time'];
            }
        }

        return $total;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::PLAYLIST;
    }

    /**
     * has_item
     * look for the track id or the object id in a playlist
     */
    public function has_item(?int $object = null, ?int $track = null, string $object_type = 'song'): bool
    {
        if (!$object && !$track) {
            return false;
        }

        return self::getPlaylistRepository()->hasItem($this->id, $object, $track, $object_type);
    }

    /**
     * has_search
     * Look for a saved smartlist with the same name as this playlist that the user can access
     */
    public function has_search(int $playlist_user): int
    {
        $repository  = self::getPlaylistRepository();
        $global_user = (int) (Core::get_global('user')?->getId());

        // the name lists are the same for every row of a page, so read them once
        $cache_key = $playlist_user . '/' . $global_user;
        if (parent::is_cached('playlist_search_names', $cache_key)) {
            $name_lists = parent::get_from_cache('playlist_search_names', $cache_key);
        } else {
            $name_lists = [
                $repository->findSearchNames($playlist_user, true),
                $repository->findSearchNames($global_user, false),
            ];
            parent::add_to_cache('playlist_search_names', $cache_key, $name_lists);
        }

        // search for your own playlist, then for the public ones
        foreach ($name_lists as $names) {
            $searchId = array_search($this->name, $names, true);
            if ($searchId !== false) {
                return (int) $searchId;
            }
        }

        return 0;
    }

    /**
     * Regenerate track numbers to fill gaps.
     */
    public function regenerate_track_numbers(): void
    {
        $index = 1;
        foreach (self::getPlaylistRepository()->getTrackIdsInOrder($this) as $trackId) {
            $this->update_track_number($trackId, $index);
            ++$index;
        }

        $this->_update_last();
    }

    /**
     * set_by_track_number
     * resort a playlist by track number and update
     */
    public function set_by_track_number(int $object_id, int $track): bool
    {
        if (AmpConfig::get('unique_playlist') && $this->has_item($object_id, $track)) {
            return false;
        }

        self::getPlaylistRepository()->replaceTrackAtNumber($this, $object_id, $track);

        debug_event(self::class, $this->id . ' set track: ' . $track . ' to ' . $object_id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * set_items
     * This calls the get_items function and sets it to $this->items which is an array in this object
     */
    public function set_items(): void
    {
        $this->items = $this->get_items();
    }

    /**
     * Sort the tracks and save the new position
     */
    public function sort_tracks(): bool
    {
        $repository = self::getPlaylistRepository();

        $track  = 1;
        $tracks = [];
        foreach ($repository->getTrackIdsSorted($this) as $trackId) {
            $tracks[$trackId] = $track;
            ++$track;
        }

        $repository->setTrackNumbers($tracks);

        $this->_update_last();

        return true;
    }

    /**
     * update_track_number
     * This takes a playlist_data.id and a track (int) and updates the track value
     */
    public function update_track_number(int $track_id, int $index): void
    {
        self::getPlaylistRepository()->setTrackNumber($track_id, $index);
    }

    /**
     * _update_last
     * This updates the playlist last update along with the cached totals
     */
    private function _update_last(): void
    {
        $this->last_update = time();

        self::getPlaylistRepository()->setLastUpdate($this, $this->last_update);

        $this->set_last($this->get_total_duration(), 'last_duration');
        $this->set_last($this->get_media_count(), 'last_count');
    }
}
