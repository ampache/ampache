<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Module\Authorization\Access;

interface ModelFactoryInterface
{
    public function createPlaylist(int $id): Playlist;

    public function createBrowse(
        ?int $browse_id = null,
        bool $cached = true
    ): Browse;

    public function createSong(
        ?int $songId = null
    ): Song;

    public function createRating(
        int $objectId,
        string $typeId
    ): Rating;

    public function createUser(
        ?int $userId = null
    ): User;

    public function createAlbum(
        ?int $albumId = null
    ): Album;

    public function createAlbumDisk(
        ?int $albumDiskId = null
    ): AlbumDisk;

    public function createArtist(
        ?int $artistId = null
    ): Artist;

    public function createWanted(
        ?int $wantedId = null
    ): Wanted;

    public function createArt(
        ?int $artId = null,
        string $type = 'album',
        string $kind = 'default'
    ): Art;

    public function createBroadcast(
        int $broadcastId
    ): Broadcast;

    public function createLiveStream(
        int $liveStreamId
    ): Live_Stream;

    public function createPodcast(
        int $podcastId
    ): Podcast;

    public function createPodcastEpisode(
        int $podcastEpisodeId
    ): Podcast_Episode;

    public function createPrivateMsg(
        int $privateMessageId
    ): PrivateMsg;

    public function createTvShow(
        int $tvShowId
    ): TvShow;

    public function createDemocratic(
        int $democraticId
    ): Democratic;

    public function createTmpPlaylist(
        int $tmpPlaylistId
    ): Tmp_Playlist;

    public function createSearch(
        ?int $searchId = 0,
        string $searchType = 'song',
        ?User $user = null
    ): Search;

    public function createAccess(
        int $accessId
    ): Access;

    public function createLabel(
        int $labelId
    ): Label;

    public function createVideo(
        int $videoId
    ): Video;
}
