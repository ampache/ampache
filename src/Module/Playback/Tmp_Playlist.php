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

namespace Ampache\Module\Playback;

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\database_object;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\TmpPlaylistRepositoryInterface;

/**
 * TempPlaylist Class
 *
 * This class handles the temporary playlists in Ampache. It handles the
 * tmp_playlist and tmp_playlist_data tables, and sneaks out at night to
 * visit user_vote from time to time.
 */
class Tmp_Playlist extends database_object
{
    protected const DB_TABLENAME = 'tmp_playlist';

    // Variables from the Database
    public int $id = 0;

    // Generated Elements
    /** @var array<int, array{object_type: LibraryItemEnum, object_id: int, track: int, track_id: int}> */
    public array $items = [];

    public ?string $object_type = null;
    public ?string $session     = null;
    public ?string $type        = null;
    private ?int $_row_playlist = null;

    /**
     * Constructor
     * This takes a playlist_id as an optional argument and gathers the
     * information.  If no playlist_id is passed or the requested one isn't
     * found, return false.
     */
    public function __construct(?int $playlist_id = 0)
    {
        if (!$playlist_id) {
            return;
        }

        $info = $this->has_info($playlist_id);
        if (!$info) {
            return;
        }

        $this->id = $playlist_id;
    }

    /**
     * create
     * This function initializes a new Tmp_Playlist. It is associated with
     * the current session rather than a user, as you could have the same
     * user logged in from multiple locations.
     * @param array{session_id: string, type: string, object_type: string} $data
     */
    public static function create(array $data): ?string
    {
        $tmp_id = self::getTmpPlaylistRepository()->create($data['session_id'], $data['type'], $data['object_type']);
        if ($tmp_id === null) {
            return null;
        }

        $tmp_id = (string) $tmp_id;

        /* Clean any other playlists associated with this session */
        self::session_clean($data['session_id'], $tmp_id);

        return $tmp_id;
    }

    /**
     * garbage_collection
     * This cleans up old data
     */
    public static function garbage_collection(): void
    {
        self::prune_playlists();
        self::prune_tracks();
    }

    /**
     * get_from_session
     * This returns a playlist object based on the session that is passed to
     * us.  This is used by the load_playlist on user for the most part.
     */
    public static function get_from_session(string $session_id): Tmp_Playlist
    {
        $playlistId = self::getTmpPlaylistRepository()->findBySession($session_id);
        $row        = ($playlistId === null) ? [] : [$playlistId];

        if ($row === []) {
            $row[0] = self::create(['session_id' => $session_id, 'type' => 'user', 'object_type' => 'song']);
        }

        return new Tmp_Playlist((int) $row[0]);
    }

    /**
     * get_from_username
     * This returns a tmp playlist object based on a userid passed
     * this is used for the user profiles page
     */
    public static function get_from_username(string $username): ?int
    {
        return self::getTmpPlaylistRepository()->findByUsername($username);
    }

    /**
     * prune_playlists
     * This deletes any playlists that don't have an associated session
     */
    public static function prune_playlists(): bool
    {
        self::getTmpPlaylistRepository()->collectGarbage();

        return true;
    }

    /**
     * prune_tracks
     * This prunes tracks that don't have playlists or don't have votes
     */
    public static function prune_tracks(): void
    {
        self::getTmpPlaylistRepository()->collectGarbage();
    }

    /**
     * session_clean
     * This deletes any other tmp_playlists associated with thisvsession
     */
    public static function session_clean(string $sessid, string $plist_id): void
    {
        self::getTmpPlaylistRepository()->deleteOtherSessionPlaylists($sessid, (int) $plist_id);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getTmpPlaylistRepository(): TmpPlaylistRepositoryInterface
    {
        global $dic;

        return $dic->get(TmpPlaylistRepositoryInterface::class);
    }

    /**
     * @param array<int, array{object_type: LibraryItemEnum, object_id: int}> $medias
     */
    public function add_medias(array $medias): void
    {
        foreach ($medias as $media) {
            $this->add_object($media['object_id'], $media['object_type']);
        }
    }

    /**
     * add_object
     * This adds the object of $this->object_type to this tmp playlist
     * it takes an optional type, default is song
     */
    public function add_object(int $object_id, LibraryItemEnum $object_type): bool
    {
        self::getTmpPlaylistRepository()->addItem($this->id, $object_id, $object_type->value);

        return true;
    }

    /**
     * clear
     * This clears all the objects out of a single playlist
     */
    public function clear(): bool
    {
        self::getTmpPlaylistRepository()->deleteItems($this->id);

        return true;
    }

    /**
     * count_items
     * This returns a count of the total number of tracks that are in this
     * tmp playlist
     */
    public function count_items(): int
    {
        return self::getTmpPlaylistRepository()->countItems($this->_row_playlist());
    }

    /**
     * delete_track
     * This deletes a track from the tmpplaylist
     */
    public function delete_track(int $object_id): bool
    {
        /* delete the track its self */
        self::getTmpPlaylistRepository()->deleteItemByRowId($object_id);

        return true;
    }

    /**
     * get_items
     * Returns an array of all object_ids currently in this Tmp_Playlist.
     * @return array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track_id: int,
     *     track: int
     * }>
     */
    public function get_items(int $limit = 0): array
    {
        $items = [];
        $count = 1;
        foreach (self::getTmpPlaylistRepository()->getItems($this->_row_playlist(), $limit) as $row) {
            $items[] = [
                'object_type' => LibraryItemEnum::from($row['object_type']),
                'object_id' => $row['object_id'],
                'track_id' => $row['id'],
                'track' => $count++
            ];
        }

        return $items;
    }

    /**
     * get_next_object
     * This returns the next object in the tmp_playlist.
     */
    public function get_next_object(): ?int
    {
        return self::getTmpPlaylistRepository()->getNextObjectId($this->id);
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * has_items
     * Whether this tmp playlist holds anything at all, for the callers that only need to know that
     */
    public function has_items(): bool
    {
        return self::getTmpPlaylistRepository()->hasItems($this->_row_playlist());
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * The playlist id whose rows this object covers
     *
     * The session is resolved to an id rather than joined: `tmp_playlist` is the key the rows are already
     * ordered by, and reaching it through a join costs a sort of the whole queue before a LIMIT cuts it
     */
    private function _row_playlist(): int
    {
        if ($this->_row_playlist === null) {
            $this->_row_playlist = $this->id;
            $session_name        = AmpConfig::get('session_name', 'ampache');
            if (isset($_COOKIE[$session_name])) {
                // the cookie still holds the pre-login id for one request, and the header must agree with the list
                $playlistId = self::getTmpPlaylistRepository()->findBySession((string) $_COOKIE[$session_name]);
                if ($playlistId !== null) {
                    $this->_row_playlist = $playlistId;
                }
            }
        }

        return $this->_row_playlist;
    }

    /**
     * has_info
     * This is an internal (private) function that gathers the information
     * for this object from the playlist_id that was passed in.
     */
    private function has_info(int $playlist_id): bool
    {
        $data = self::getTmpPlaylistRepository()->getRow($playlist_id);
        if ($data === []) {
            return false;
        }

        $this->id          = (int) ($data['id'] ?? 0);
        $this->session     = $data['session'] ?? null;
        $this->type        = $data['type'] ?? null;
        $this->object_type = $data['object_type'] ?? null;

        return true;
    }
}
