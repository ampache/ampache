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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Module\Art\Art;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

/**
 * Maps Ampache models to Jellyfin BaseItemDto-shaped arrays. Cheap fields are always populated; only
 * `MediaSources` is gated behind the requested `fields` list.
 */
final class JellyfinItemMapper
{
    /**
     * @return array<string, mixed>
     */
    public function mapAlbum(Album $album, User $user): array
    {
        $albumArtistName = ($album->album_artist !== null) ? Artist::get_fullname_by_id($album->album_artist) : null;

        $artistItems = ($album->album_artist !== null)
            ? [['Name' => $albumArtistName, 'Id' => JellyfinId::encode('artist', $album->album_artist)]]
            : [];

        $dto = [
            'Id' => JellyfinId::encode('album', $album->id),
            'ServerId' => null,
            'Name' => $album->get_fullname(),
            'SortName' => $this->sortName($album->name ?? $album->get_fullname()),
            'Type' => 'MusicAlbum',
            'IsFolder' => true,
            'AlbumArtist' => $albumArtistName,
            'ArtistItems' => $artistItems,
            'AlbumArtists' => $artistItems,
            'ChildCount' => $album->song_count,
            'RunTimeTicks' => ($album->time ?? 0) * 10_000_000,
            'ImageTags' => $this->imageTags('album', $album->id),
            'Etag' => $this->etag('album', $album->id, $album->get_fullname(), (string) $album->song_count, (string) ($album->time ?? 0)),
            'UserData' => $this->userData('album', $album->id, $user),
        ];

        if ($album->year) {
            $dto['ProductionYear'] = $album->year;
        }

        return $this->withDateCreated($dto, $album->addition_time);
    }

    /**
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    public function mapArtist(Artist $artist, User $user, array $fields): array
    {
        $dto = [
            'Id' => JellyfinId::encode('artist', $artist->id),
            'ServerId' => null,
            'Name' => $artist->get_fullname(),
            'SortName' => $this->sortName($artist->name ?? $artist->get_fullname()),
            'Type' => 'MusicArtist',
            'IsFolder' => true,
            'ChildCount' => $artist->album_count,
            'ImageTags' => $this->imageTags('artist', $artist->id),
            'Etag' => $this->etag('artist', $artist->id, $artist->get_fullname() ?? '', (string) $artist->album_count),
            'UserData' => $this->userData('artist', $artist->id, $user),
        ];

        // omitted rather than null: Symfonium's own model declares these non-nullable and rejects a null value
        if (in_array('Overview', $fields, true) && $artist->summary !== null) {
            $dto['Overview'] = $artist->summary;
        }

        return $this->withDateCreated($dto, $artist->addition_time);
    }

    /**
     * @param array{id: int, name: string, is_hidden: int, count: int} $tag
     * @return array<string, mixed>
     */
    public function mapGenre(array $tag, User $user): array
    {
        return [
            'Id' => JellyfinId::encode('genre', $tag['id']),
            'ServerId' => null,
            'Name' => $tag['name'],
            'SortName' => $this->sortName($tag['name']),
            'Type' => 'MusicGenre',
            'Etag' => $this->etag('genre', $tag['id'], $tag['name']),
            'UserData' => $this->userData('genre', $tag['id'], $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapPlaylist(Playlist $playlist, User $user): array
    {
        $dto = [
            'Id' => JellyfinId::encode('playlist', $playlist->id),
            'ServerId' => null,
            'Name' => $playlist->get_fullname(),
            'SortName' => $this->sortName($playlist->name ?? $playlist->get_fullname()),
            'Type' => 'Playlist',
            'MediaType' => 'Audio',
            'IsFolder' => true,
            'ChildCount' => $playlist->last_count,
            'RunTimeTicks' => ($playlist->last_duration ?? 0) * 10_000_000,
            'CanDelete' => $playlist->user === $user->getId(),
            'ImageTags' => $this->imageTags('playlist', $playlist->id),
            'Etag' => $this->etag('playlist', $playlist->id, (string) $playlist->name, (string) $playlist->last_count, (string) $playlist->last_update),
            'UserData' => $this->userData('playlist', $playlist->id, $user),
        ];

        return $this->withDateCreated($dto, $playlist->date);
    }

    /**
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    public function mapSong(Song $song, User $user, array $fields): array
    {
        $id             = JellyfinId::encode('song', $song->id);
        $runTimeTicks   = $song->time * 10_000_000;
        $albumArtistId  = $song->get_album_artist();
        $albumArtistDto = ($albumArtistId !== null && $albumArtistId > 0)
            ? [['Name' => $song->get_album_artist_fullname($albumArtistId), 'Id' => JellyfinId::encode('artist', $albumArtistId)]]
            : [];

        $dto = [
            'Id' => $id,
            'ServerId' => null,
            'Name' => $song->title,
            'SortName' => $this->sortName($song->title),
            'Type' => 'Audio',
            'MediaType' => 'Audio',
            'IsFolder' => false,
            'AlbumId' => JellyfinId::encode('album', $song->album),
            'Album' => $song->get_album_fullname(),
            'ArtistItems' => [
                ['Name' => $song->get_parent_fullname(), 'Id' => JellyfinId::encode('artist', $song->artist ?? 0)],
            ],
            'Artists' => [$song->get_parent_fullname()],
            'AlbumArtist' => $song->get_parent_fullname(),
            'AlbumArtists' => $albumArtistDto,
            'IndexNumber' => $song->track,
            'ParentIndexNumber' => $song->disk,
            'RunTimeTicks' => $runTimeTicks,
            'Container' => $song->type,
            'ImageTags' => $this->imageTags('song', $song->id),
            'Etag' => $this->etag('song', $song->id, $song->title ?? '', (string) $song->time, (string) ((int) $song->played)),
            'UserData' => $this->userData('song', $song->id, $user, $song),
            // real clients (confirmed: gelly's Rust MusicDto) declare these non-nullable/non-optional, so a
            // missing key silently drops the whole song from a list rather than just this field
            'HasLyrics' => false,
            'Genres' => array_map(static fn(array $tag): string => $tag['name'], $song->get_tags()),
            'NormalizationGain' => $song->replaygain_track_gain,
        ];

        // omitted rather than null: several clients (confirmed: Symfonium) reject a null value for these
        if ($song->year) {
            $dto['ProductionYear'] = $song->year;
        }
        if (Art::has_db($song->album, 'album')) {
            $dto['AlbumPrimaryImageTag'] = 'a1';
        }

        if (in_array('MediaSources', $fields, true)) {
            $dto['MediaSources'] = [
                [
                    'Id' => $id,
                    'Protocol' => 'File',
                    'Container' => $song->type,
                    'Size' => $song->size,
                    'Bitrate' => $song->bitrate,
                    'RunTimeTicks' => $runTimeTicks,
                    'SupportsDirectPlay' => true,
                    'SupportsDirectStream' => true,
                    'SupportsTranscoding' => false,
                    'IsRemote' => false,
                ],
            ];
        }

        return $this->withDateCreated($dto, $song->addition_time);
    }

    /**
     * Public wrapper for the standalone `UserItemDataDto` responses (favorite/played/rating endpoints),
     * which additionally carry the item id `userData()`'s nested, envelope-only form doesn't need.
     *
     * @return array<string, mixed>
     */
    public function mapUserData(string $type, int $objectId, User $user, ?Song $song = null): array
    {
        $id = JellyfinId::encode($type, $objectId);

        return $this->userData($type, $objectId, $user, $song) + ['ItemId' => $id, 'Key' => $id];
    }

    /**
     * A stable per-object cache-validation hash: several real clients (confirmed: Symfonium) key their own
     * sync/diff logic on `Etag`, and an item that never carries one can get stuck showing stale local state.
     */
    private function etag(string $type, int $objectId, string ...$parts): string
    {
        return md5($type . '-' . $objectId . '|' . implode('|', $parts));
    }

    /**
     * A non-empty value is all a client checks for; the tag itself is never validated back (no conditional
     * GET support), so a fixed placeholder is enough to tell a client this item's art is worth fetching.
     *
     * @return array<string, string>|\stdClass
     */
    private function imageTags(string $type, int $objectId): array|\stdClass
    {
        return Art::has_db($objectId, $type) ? ['Primary' => 'a1'] : new \stdClass();
    }

    /** Ampache already strips a leading article into `$prefix` for artist/album, so this only lowercases. */
    private function sortName(?string $name): string
    {
        return strtolower(trim((string) $name));
    }

    private function toIso8601(?int $unixTime): ?string
    {
        if ($unixTime === null || $unixTime <= 0) {
            return null;
        }

        return gmdate('Y-m-d\TH:i:s.000000\Z', $unixTime);
    }

    /**
     * `$song` enriches `Played`/`PlaybackPositionTicks` with real data — only meaningful for a song, so
     * album/artist/playlist callers pass none and keep the previous (false/0) defaults.
     *
     * @return array<string, mixed>
     */
    private function userData(string $type, int $objectId, User $user, ?Song $song = null): array
    {
        $flag   = new Userflag($objectId, $type);
        $rating = new Rating($objectId, $type);
        $stars  = $rating->get_user_rating($user->getId());

        $played        = false;
        $positionTicks = 0;
        if ($song !== null) {
            $played        = (bool) $song->played;
            $bookmark      = new Bookmark($song->id, 'song', $user->getId());
            $positionTicks = $bookmark->position * 10_000_000;
        }

        return [
            'IsFavorite' => (bool) $flag->get_flag($user->getId()),
            'Rating' => ($stars !== null) ? $stars * 2 : null,
            // Ampache has no native like/dislike toggle; the rating endpoint maps likes to 5/1 stars
            'Likes' => match (true) {
                $stars === null || $stars === 0 => null,
                $stars >= 4 => true,
                default => false,
            },
            'Played' => $played,
            'PlayCount' => 0,
            // Finamp's UserItemDataDto declares this a non-nullable int; omitting the key crashes the client
            'PlaybackPositionTicks' => $positionTicks,
        ];
    }

    /**
     * @param array<string, mixed> $dto
     * @return array<string, mixed>
     */
    private function withDateCreated(array $dto, ?int $unixTime): array
    {
        $iso = $this->toIso8601($unixTime);
        if ($iso !== null) {
            $dto['DateCreated'] = $iso;
        }

        return $dto;
    }
}
