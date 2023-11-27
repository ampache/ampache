<?php

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

declare(strict_types=1);

namespace Ampache\Repository\Model;

use Ampache\Module\Authorization\Access;

/**
 * This class is used to instantiate model objects (like Playlist, Song, ...)
 */
final class ModelFactory implements ModelFactoryInterface
{
    public function createPlaylist(
        int $id
    ): Playlist {
        return new Playlist($id);
    }

    public function createBrowse(
        ?int $browse_id = null,
        bool $cached = true
    ): Browse {
        return new Browse(
            (int) $browse_id,
            $cached
        );
    }

    public function createSong(
        ?int $songId = null
    ): Song {
        return new Song(
            (int) $songId
        );
    }

    public function createRating(
        int $objectId,
        string $typeId
    ): Rating {
        return new Rating(
            $objectId,
            $typeId
        );
    }

    public function createUser(
        ?int $userId = null
    ): User {
        return new User((int) $userId);
    }

    public function createAlbum(
        ?int $albumId = null
    ): Album {
        return new Album((int) $albumId);
    }

    public function createAlbumDisk(
        ?int $albumDiskId = null
    ): AlbumDisk {
        return new AlbumDisk((int) $albumDiskId);
    }

    public function createArtist(
        ?int $artistId = null
    ): Artist {
        return new Artist((int) $artistId);
    }

    public function createWanted(
        ?int $wantedId = null
    ): Wanted {
        return new Wanted((int) $wantedId);
    }

    public function createArt(
        ?int $artId = null,
        string $type = 'album',
        string $kind = 'default'
    ): Art {
        return new Art((int) $artId, $type, $kind);
    }

    public function createBroadcast(
        int $broadcastId
    ): Broadcast {
        return new Broadcast($broadcastId);
    }

    public function createLiveStream(
        int $liveStreamId
    ): Live_Stream {
        return new Live_Stream($liveStreamId);
    }

    public function createPodcast(
        int $podcastId
    ): Podcast {
        return new Podcast($podcastId);
    }

    public function createPodcastEpisode(
        int $podcastEpisodeId
    ): Podcast_Episode {
        return new Podcast_Episode($podcastEpisodeId);
    }

    public function createPrivateMsg(
        int $privateMessageId
    ): PrivateMsg {
        return new PrivateMsg($privateMessageId);
    }

    public function createTvShow(
        int $tvShowId
    ): TvShow {
        return new TvShow($tvShowId);
    }

    public function createDemocratic(
        int $democraticId
    ): Democratic {
        return new Democratic($democraticId);
    }

    public function createTmpPlaylist(
        int $tmpPlaylistId
    ): Tmp_Playlist {
        return new Tmp_Playlist($tmpPlaylistId);
    }

    public function createSearch(
        ?int $searchId = 0,
        string $searchType = 'song',
        ?User $user = null
    ): Search {
        return new Search((int) $searchId, $searchType, $user);
    }

    public function createShoutbox(
        int $shoutboxId
    ): Shoutbox {
        return new Shoutbox($shoutboxId);
    }

    public function createLicense(
        int $licenseId
    ): License {
        return new License($licenseId);
    }

    public function createAccess(
        int $accessId
    ): Access {
        return new Access($accessId);
    }

    public function createLabel(
        int $labelId
    ): Label {
        return new Label($labelId);
    }
}
