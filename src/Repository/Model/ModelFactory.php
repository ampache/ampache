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
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Module\Playback\Democratic;
use Ampache\Module\Playback\Tmp_Playlist;
use Ampache\Module\Statistics\Rating;

/**
 * This class is used to instantiate model objects (like Playlist, Song, ...)
 */
final class ModelFactory implements ModelFactoryInterface
{
    public function createAccess(
        int $accessId,
    ): Access {
        return new Access($accessId);
    }

    public function createAlbum(
        ?int $albumId = null,
    ): Album {
        return new Album((int) $albumId);
    }

    public function createAlbumDisk(
        ?int $albumDiskId = null,
    ): AlbumDisk {
        return new AlbumDisk((int) $albumDiskId);
    }

    public function createArt(
        ?int $artId = null,
        string $type = 'album',
        string $kind = 'default',
    ): Art {
        return new Art((int) $artId, $type, $kind);
    }

    public function createArtist(
        ?int $artistId = null,
    ): Artist {
        return new Artist((int) $artistId);
    }

    public function createBroadcast(
        int $broadcastId,
    ): Broadcast {
        return new Broadcast($broadcastId);
    }

    public function createBrowse(
        ?int $browse_id = null,
        bool $cached = true,
    ): Browse {
        return new Browse(
            (int) $browse_id,
            $cached
        );
    }

    public function createDemocratic(
        int $democraticId,
    ): Democratic {
        return new Democratic($democraticId);
    }

    public function createLiveStream(
        int $liveStreamId,
    ): Live_Stream {
        return new Live_Stream($liveStreamId);
    }

    public function createPlaylist(
        int $id,
    ): Playlist {
        return new Playlist($id);
    }

    public function createPodcast(
        int $podcastId,
    ): Podcast {
        return new Podcast($podcastId);
    }

    public function createPodcastEpisode(
        int $podcastEpisodeId,
    ): Podcast_Episode {
        return new Podcast_Episode($podcastEpisodeId);
    }

    public function createPrivateMsg(
        int $privateMessageId,
    ): PrivateMessageInterface {
        return new PrivateMsg($privateMessageId);
    }

    public function createRating(
        int $objectId,
        string $typeId,
    ): Rating {
        return new Rating(
            $objectId,
            $typeId
        );
    }

    public function createSearch(
        ?int $searchId = 0,
        string $searchType = 'song',
        ?User $user = null,
    ): Search {
        return new Search((int) $searchId, $searchType, $user);
    }

    public function createSmartlist(
        ?int $searchId = 0,
        ?User $user = null,
    ): Smartlist {
        return new Smartlist((int) $searchId, $user);
    }

    public function createSong(
        ?int $songId = null,
    ): Song {
        return new Song(
            (int) $songId
        );
    }

    public function createTmpPlaylist(
        int $tmpPlaylistId,
    ): Tmp_Playlist {
        return new Tmp_Playlist($tmpPlaylistId);
    }

    public function createUser(
        ?int $userId = null,
    ): User {
        return new User((int) $userId);
    }

    public function createVideo(
        int $videoId,
    ): Video {
        return new Video($videoId);
    }

    public function createWanted(
        ?int $wantedId = null,
    ): Wanted {
        return new Wanted((int) $wantedId);
    }
}
