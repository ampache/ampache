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

use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Module\Playback\Democratic;
use Ampache\Module\Playback\Tmp_Playlist;
use Ampache\Module\Statistics\Rating;

interface ModelFactoryInterface
{
    public function createAccess(
        int $accessId,
    ): Access;

    public function createAlbum(
        ?int $albumId = null,
    ): Album;

    public function createAlbumDisk(
        ?int $albumDiskId = null,
    ): AlbumDisk;

    public function createArt(
        ?int $artId = null,
        string $type = 'album',
        string $kind = 'default',
    ): Art;

    public function createArtist(
        ?int $artistId = null,
    ): Artist;

    public function createBroadcast(
        int $broadcastId,
    ): Broadcast;

    public function createDemocratic(
        int $democraticId,
    ): Democratic;

    public function createLiveStream(
        int $liveStreamId,
    ): Live_Stream;

    public function createPlaylist(int $id): Playlist;

    public function createPodcast(
        int $podcastId,
    ): Podcast;

    public function createPodcastEpisode(
        int $podcastEpisodeId,
    ): Podcast_Episode;

    public function createPrivateMsg(
        int $privateMessageId,
    ): PrivateMessageInterface;

    public function createRating(
        int $objectId,
        string $typeId,
    ): Rating;

    public function createSearch(
        ?int $searchId = 0,
        string $searchType = 'song',
        ?User $user = null,
    ): Search;

    /**
     * Loads a *saved* search. Use this instead of `createSearch()` whenever the id comes from the
     * database — the stored row has no object type to restore, so a smartlist is always songs.
     */
    public function createSmartlist(
        ?int $searchId = 0,
        ?User $user = null,
    ): Smartlist;

    public function createSong(
        ?int $songId = null,
    ): Song;

    public function createTmpPlaylist(
        int $tmpPlaylistId,
    ): Tmp_Playlist;

    public function createUser(
        ?int $userId = null,
    ): User;

    public function createVideo(
        int $videoId,
    ): Video;

    public function createWanted(
        ?int $wantedId = null,
    ): Wanted;
}
