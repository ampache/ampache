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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\DemocraticRepositoryInterface;

/**
 * This class handles democratic play, which is a fancy
 * name for voting based playback.
 */
class Democratic extends Tmp_Playlist
{
    protected const string DB_TABLENAME = 'democratic';

    public int $base_playlist = 0;
    public ?int $cooldown     = null;
    public int $level         = 0;
    public ?string $name      = null;

    /** @var array<int|string> $object_ids */
    public array $object_ids = [];

    public bool $primary      = false;
    public ?int $tmp_playlist = null;
    public int $user          = 0;

    /** @var array<int|string> $vote_ids */
    public array $vote_ids = [];

    public function __construct(?int $democratic_id = 0)
    {
        if (!$democratic_id) {
            return;
        }

        parent::__construct($democratic_id);

        $info                = $this->get_info($democratic_id, static::DB_TABLENAME);
        $this->id            = (int) ($info['id'] ?? 0);
        $this->name          = $info['name'] ?? null;
        $this->base_playlist = (int) ($info['base_playlist'] ?? 0);
        $this->cooldown      = isset($info['cooldown']) ? (int) $info['cooldown'] : null;
        $this->level         = (int) ($info['level'] ?? 0);
        $this->user          = (int) ($info['user'] ?? 0);
        $this->primary       = (bool) ($info['primary'] ?? false);
        $this->tmp_playlist  = isset($info['tmp_playlist']) ? (int) $info['tmp_playlist'] : null;
    }

    /**
     * build_vote_cache
     * This builds a vote cache of the objects we've got in the playlist
     * @param array<int|string> $ids
     */
    public static function build_vote_cache(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        foreach (self::getDemocraticRepository()->getVoteCounts(array_values($ids)) as $rowId => $count) {
            parent::add_to_cache('democratic_vote', $rowId, [$count]);
        }

        return true;
    }

    /**
     * create
     * This is the democratic play create function it inserts this into the democratic table
     * @param array{
     *     name: string,
     *     democratic: int,
     *     cooldown: int,
     *     level: int,
     *     make_default: int,
     * } $data
     */
    public static function create(array $data): ?string
    {
        // Clean up the input
        $name    = $data['name'];
        $base    = (int) $data['democratic'];
        $cool    = (int) $data['cooldown'];
        $level   = (int) $data['level'];
        $default = (int) $data['make_default'];
        $user    = (int) Core::get_global('user')?->getId();
        if ($cool < 0 || $cool > 999999) {
            $cool = 1;
        }

        $democratic_id = self::getDemocraticRepository()->insert($name, $base, $cool, $level, $user, $default);

        if ($democratic_id !== null) {
            parent::create(['session_id' => (string) $democratic_id, 'type' => 'vote', 'object_type' => 'song']);

            return (string) $democratic_id;
        }

        return null;
    }

    /**
     * delete
     * This deletes a democratic playlist
     */
    public static function delete(int $democratic_id): bool
    {
        self::getDemocraticRepository()->delete($democratic_id);

        self::prune_tracks();

        return true;
    }

    /**
     * get_current_playlist
     * This returns the current users current playlist, or if specified
     * this current playlist of the user
     */
    public static function get_current_playlist(?User $user = null): Democratic
    {
        if (!$user) {
            $user = Core::get_global('user');
        }

        $democratic_id = AmpConfig::get('democratic_id', null);
        if (!$democratic_id) {
            $democraticId = self::getDemocraticRepository()->findByAccessLevel((int) ($user->access ?? 0));
            $row          = ($democraticId === null) ? [] : ['id' => $democraticId];
            if ($row !== []) {
                $democratic_id = (int) $row['id'];
            }
        }

        return new Democratic($democratic_id);
    }

    /**
     * get_playlists
     * This returns all of the current valid 'Democratic' Playlists that have been created.
     * @return int[]
     */
    public static function get_playlists(): array
    {
        return self::getDemocraticRepository()->getAllIds();
    }

    /**
     * prune_tracks
     * This replaces the normal prune tracks and correctly removes the votes
     * as well
     */
    public static function prune_tracks(): void
    {
        self::getDemocraticRepository()->pruneTracks();
    }

    /**
     * show_playlist_select
     * This one is for playlists!
     */
    public static function show_playlist_select(string $name, string $selected = '', string $style = ''): string
    {
        $user             = Core::get_global('user');
        $string           = "<select name=\"{$name}\" style=\"{$style}\">\n\t<option value=\"\">" . T_('None') . "</option>\n";
        $already_selected = false;
        $index            = 1;
        $use_search       = AmpConfig::get('demo_use_search');
        $playlists        = ($use_search)
            ? Search::get_search_array($user?->id)
            : Playlist::get_playlist_array($user?->id);
        $nb_items = count($playlists);

        foreach ($playlists as $key => $value) {
            $select_txt = '';
            if (!$already_selected && ($key == $selected || $index == $nb_items)) {
                $select_txt       = 'selected="selected"';
                $already_selected = true;
            }

            $string .= "\t<option value=\"" . $key . sprintf('" %s>', $select_txt) . scrub_out($value) . "</option>\n";
            ++$index;
        }

        return $string . "</select>\n";
    }

    /**
     * @deprecated inject dependency
     */
    private static function getDemocraticRepository(): DemocraticRepositoryInterface
    {
        global $dic;

        return $dic->get(DemocraticRepositoryInterface::class);
    }

    /**
     * vote
     * This function is called by users to vote on a system wide playlist
     * This adds the specified objects to the tmp_playlist and adds a 'vote'
     * by this user, naturally it checks to make sure that the user hasn't
     * already voted on any of these objects
     * @param array<array{string, string|int}> $items
     */
    public function add_vote(array $items): void
    {
        /* Iterate through the objects if no vote, add to playlist and vote */
        foreach ($items as $element) {
            $type      = (string) array_shift($element);
            $object_id = (int) array_shift($element);
            if (!$this->has_vote($object_id, $type)) {
                $this->_add_vote($object_id, $type);
            }
        }
    }

    /**
     * clear
     * This is really just a wrapper function, it clears the entire playlist
     * including all votes etc.
     */
    public function clear(): bool
    {
        if (!$this->tmp_playlist) {
            return false;
        }

        // Clear all votes then prune
        self::getDemocraticRepository()->deleteVotesForPlaylist($this->tmp_playlist);

        // Prune!
        self::prune_tracks();

        // Clean the votes
        $this->clear_votes();

        return true;
    }

    /**
     * clean_votes
     * This removes in left over garbage in the votes table
     */
    public function clear_votes(): bool
    {
        self::getDemocraticRepository()->pruneVotes();

        return true;
    }

    /**
     * delete_from_oid
     * This takes an OID and type and removes the object from the democratic playlist
     */
    public function delete_from_oid(int $object_id, string $object_type): bool
    {
        $row_id = $this->get_uid_from_object_id($object_id, $object_type);
        if ($row_id) {
            debug_event(self::class, 'Removing Votes for ' . $object_id . ' of type ' . $object_type, 5);
            $this->delete_votes($row_id);
        } else {
            debug_event(self::class, 'Unable to find Votes for ' . $object_id . ' of type ' . $object_type, 3);
        }

        return true;
    }

    /**
     * delete_votes
     * This removes the votes for the specified object on the current playlist
     */
    public function delete_votes(int|string $row_id): bool
    {
        self::getDemocraticRepository()->deleteRow((int) $row_id);

        return true;
    }

    /**
     * get_cool_songs
     * This returns all of the song_ids for songs that have happened within
     * the last 'cooldown' for this user.
     */
    public function get_cool_songs(): array
    {
        // Convert cooldown time to a timestamp in the past
        $cool_time = time() - ($this->cooldown * 60);

        return Stats::get_object_history($cool_time);
    }

    /**
     * get_items
     * This returns a sorted array of all object_ids in this Tmp_Playlist.
     * The array is multidimensional; the inner array needs to contain the
     * keys 'id', 'object_type' and 'object_id'.
     *
     * Sorting is highest to lowest vote count, then by oldest to newest
     * vote activity.
     *
     * @return array<int, array{
     *   object_type: LibraryItemEnum,
     *   object_id: int,
     *   track_id: int,
     *   track: int}>
     */
    public function get_items(?int $limit = null): array
    {
        $repository = self::getDemocraticRepository();
        // Remove 'unconnected' users votes
        if (AmpConfig::get('demo_clear_sessions')) {
            $repository->deleteUnconnectedVotes();
        }

        $results = [];
        $count   = 1;
        foreach ($repository->getItems((int) $this->tmp_playlist, $limit) as $row) {
            if ($row['id']) {
                $results[] = [
                    'object_type' => LibraryItemEnum::from($row['object_type']),
                    'object_id' => $row['object_id'],
                    'track_id' => $row['id'],
                    'track' => $count++
                ];
            }
        }

        return $results;
    }

    /**
     * get_next_object
     * This returns the next object in the tmp_playlist.
     * Most of the time this will just be the top entry, but if there is a
     * base_playlist and no items in the playlist then it returns a random
     * entry from the base_playlist
     */
    public function get_next_object(int $offset = 0): ?int
    {
        // FIXME: Shouldn't this return object_type?

        $items      = $this->get_items($offset + 1);
        $use_search = AmpConfig::get('demo_use_search');

        if (count($items) > $offset) {
            return $items[$offset]['object_id'];
        }

        // If nothing was found and this is a voting playlist then get from base_playlist
        if ($this->base_playlist !== 0) {
            $base_playlist = ($use_search)
                ? new Smartlist($this->base_playlist)
                : new Playlist($this->base_playlist);
            $data = $base_playlist->get_random_items('1');

            return $data[0]['object_id'];
        }
        $catalogFilter = (AmpConfig::get('catalog_filter'))
            ? ' AND' . Catalog::get_user_filter('song', Core::get_global('user')->id ?? -1)
            : '';

        return self::getDemocraticRepository()->findRandomSongId($catalogFilter);
    }

    /**
     * get_uid_from_object_id
     * This takes an object_id and an object type and returns the ID for the row
     */
    public function get_uid_from_object_id(int $object_id, string $object_type = 'song'): ?int
    {
        if (!$object_id) {
            return null;
        }

        return self::getDemocraticRepository()->findRowId($object_type, (int) $this->tmp_playlist, $object_id);
    }

    /**
     * get_vote
     * This returns the current count for a specific song
     */
    public function get_vote(int $object_id): int
    {
        if (parent::is_cached('democratic_vote', $object_id)) {
            return (int) (parent::get_from_cache('democratic_vote', $object_id))[0];
        }

        $count = self::getDemocraticRepository()->getVoteCount($object_id);
        parent::add_to_cache('democratic_vote', $object_id, [$count]);

        return $count;
    }

    public function getAccessLevel(): AccessLevelEnum
    {
        return AccessLevelEnum::from($this->level);
    }

    public function getId(): int
    {
        return $this->id ?: 0;
    }

    /**
     * has_vote
     * This checks to see if the current user has already voted on this object
     */
    public function has_vote(int $object_id, string $type = 'song'): bool
    {
        /* Query vote table */
        return self::getDemocraticRepository()->hasVoted(
            $type,
            $object_id,
            (int) $this->tmp_playlist,
            Core::get_global('user')?->getId(),
            (string) session_id()
        );
    }

    /**
     * is_enabled
     * This function just returns true / false if the current democratic
     * playlist is currently enabled / configured
     */
    public function is_enabled(): bool
    {
        return (bool) $this->tmp_playlist;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * play_url
     * This returns the special play URL for democratic play, only open to ADMINs
     */
    public function play_url(?User $user = null): string
    {
        if (empty($user)) {
            $user = Core::get_global('user');
        }

        $link = Stream::get_base_url(false, $user?->streamtoken) . 'uid=' . $user?->id . '&demo_id=' . scrub_out((string) $this->id);

        return Stream_Url::format($link);
    }

    /**
     * remove_vote
     * This is called to remove a vote by a user for an object.
     */
    public function remove_vote(int|string $row_id): bool
    {
        self::getDemocraticRepository()->deleteVote(
            $row_id,
            Core::get_global('user')?->getId(),
            (string) session_id()
        );

        /* Clean up anything that has no votes */
        self::prune_tracks();

        return true;
    }

    /**
     * set_parent
     * This returns the Tmp_Playlist for this democratic play instance
     */
    public function set_parent(): void
    {
        $row = self::getDemocraticRepository()->getTmpPlaylistRow($this->id);
        if ($row !== []) {
            $this->tmp_playlist = $row['id'] ?? null;
        }
    }

    /**
     * update
     * This updates an existing democratic playlist item. It takes a key'd array just like create
     * @param array{
     *     name?: string,
     *     democratic?: int,
     *     cooldown?: int,
     *     level?: int,
     *     make_default?: int,
     * } $data
     */
    public function update(array $data): int
    {
        $name    = $data['name'] ?? $this->name;
        $base    = (int) ($data['democratic'] ?? $this->base_playlist);
        $cool    = (int) ($data['cooldown'] ?? $this->cooldown);
        $level   = (int) ($data['level'] ?? $this->level);
        $default = (int) ($data['make_default'] ?? 0);
        $demo_id = $this->id;

        if ($cool < 0 || $cool > 999999) {
            $cool = 1;
        }

        self::getDemocraticRepository()->update($demo_id, (string) $name, $base, $cool, $default, $level);

        return $this->id;
    }

    /**
     * _add_vote
     * This takes an object id and user and actually inserts the row
     */
    private function _add_vote(int $object_id, string $object_type = 'song'): void
    {
        if (!$this->tmp_playlist) {
            return;
        }

        $className = ObjectTypeToClassNameMapper::map($object_type);
        /** @var Media $media */
        $media = new $className($object_id);
        $track = (isset($media->track)) ? (int) ($media->track) : null;

        $repository = self::getDemocraticRepository();

        /* If it's not on the playlist, add it and pull the row id */
        $rowId = $repository->findRowId($object_type, (int) $this->tmp_playlist, $object_id)
            ?? $repository->insertRow((int) $this->tmp_playlist, $object_id, $object_type, (int) $track);

        if ($rowId === null) {
            return;
        }

        /* Vote! */
        $repository->addVote($rowId, Core::get_global('user')?->getId(), (string) session_id(), time());
    }
}
