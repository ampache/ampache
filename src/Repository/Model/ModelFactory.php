<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
use Ampache\Module\Playback\PlaybackFactoryInterface;
use Ampache\Module\Tag\TagListUpdaterInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Video\ClipCreatorInterface;
use Ampache\Module\Wanted\MissingArtistLookupInterface;
use Ampache\Repository\BroadcastRepositoryInteface;
use Ampache\Repository\ClipRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\TvShowSeasonRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use MusicBrainz\MusicBrainz;
use Psr\Container\ContainerInterface;

/**
 * This class is used to instantiate model objects (like Playlist, Song, ...)
 */
final class ModelFactory implements ModelFactoryInterface
{
    private ContainerInterface $dic;

    public function __construct(
        ContainerInterface $dic
    ) {
        $this->dic = $dic;
    }

    /** @var array<class-string, callable(int): database_object>|null */
    private ?array $map = null;

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
            $browse_id,
            $cached
        );
    }

    public function createSong(
        ?int $songId = null,
        string $limitThreshold = ''
    ): Song {
        return new Song(
            $songId,
            $limitThreshold
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
        return new User($userId);
    }

    public function createAlbum(
        ?int $albumId = null
    ): Album {
        return new Album($albumId);
    }

    public function createArtist(
        ?int $artistId = null,
        int $catalogId = 0
    ): Artist {
        return new Artist($artistId, $catalogId);
    }

    public function createWanted(
        ?int $wantedId = null
    ): WantedInterface {
        return new Wanted(
            $this->dic->get(WantedRepositoryInterface::class),
            $this->dic->get(MusicBrainz::class),
            $this->dic->get(MissingArtistLookupInterface::class),
            $this->dic->get(ModelFactoryInterface::class),
            $wantedId
        );
    }

    public function createArt(
        ?int $artId = null,
        string $type = 'album',
        string $kind = 'default'
    ): Art {
        return new Art($artId, $type, $kind);
    }

    public function createBroadcast(
        int $broadcastId
    ): BroadcastInterface {
        return new Broadcast(
            $this->dic->get(BroadcastRepositoryInteface::class),
            $this->dic->get(TagListUpdaterInterface::class),
            $broadcastId
        );
    }

    public function createLiveStream(
        int $liveStreamId
    ): LiveStreamInterface {
        return new Live_Stream(
            $this->dic->get(LiveStreamRepositoryInterface::class),
            $liveStreamId
        );
    }

    public function createChannel(
        int $channelId
    ): Channel {
        return new Channel($channelId);
    }

    public function createPodcast(
        int $podcastId
    ): PodcastInterface {
        return new Podcast($podcastId);
    }

    public function createPodcastEpisode(
        int $podcastEpisodeId
    ): PodcastEpisodeInterface {
        return new Podcast_Episode($podcastEpisodeId);
    }

    public function createPrivateMsg(
        int $privateMessageId
    ): PrivateMsg {
        return new PrivateMsg(
            $this->dic->get(PrivateMessageRepositoryInterface::class),
            $privateMessageId
        );
    }

    public function createTvShow(
        int $tvShowId
    ): TvShowInterface {
        return new TvShow(
            $this->dic->get(TagListUpdaterInterface::class),
            $tvShowId
        );
    }

    public function createTvShowSeason(
        int $tvShowSeasonId
    ): TvShowSeasonInterface {
        return new TVShow_Season(
            $this->dic->get(ShoutRepositoryInterface::class),
            $this->dic->get(UserActivityRepositoryInterface::class),
            $this->dic->get(TvShowSeasonRepositoryInterface::class),
            $this,
            $tvShowSeasonId
        );
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
        return new Search($searchId, $searchType, $user);
    }

    public function createShoutbox(
        int $shoutboxId
    ): ShoutboxInterface {
        return new Shoutbox(
            $this->dic->get(ShoutRepositoryInterface::class),
            $shoutboxId
        );
    }

    public function createLicense(
        int $licenseId = 0
    ): LicenseInterface {
        return new License(
            $this->dic->get(LicenseRepositoryInterface::class),
            $licenseId
        );
    }

    public function createAccess(
        int $accessId
    ): Access {
        return new Access($accessId);
    }

    public function createLabel(
        int $labelId
    ): LabelInterface {
        return new Label(
            $this->dic->get(LabelRepositoryInterface::class),
            $this->dic->get(SongRepositoryInterface::class),
            $labelId
        );
    }

    public function createTag(
        int $tagId
    ): Tag {
        return new Tag($tagId);
    }

    public function createVideo(
        int $videoId
    ): Video {
        return new Video($videoId);
    }

    public function createBookmark(
        int $bookmarkId,
        ?string $objectType = null,
        ?int $userId = null
    ): BookmarkInterface {
        return new Bookmark(
            $this,
            $bookmarkId,
            $objectType,
            $userId
        );
    }

    public function createUseractivity(
        int $useractivityId
    ): UseractivityInterface {
        return new Useractivity($useractivityId);
    }

    public function createUserflag(
        int $userFlagId,
        string $type
    ): Userflag {
        return new Userflag(
            $userFlagId,
            $type
        );
    }

    public function createShare(int $shareId): ShareInterface
    {
        return new Share(
            $this->dic->get(ShareRepositoryInterface::class),
            $this,
            $this->dic->get(PlaybackFactoryInterface::class),
            $shareId
        );
    }

    public function createClip(int $clipId): Clip
    {
        return new Clip(
            $this->dic->get(ClipRepositoryInterface::class),
            $this,
            $this->dic->get(SongRepositoryInterface::class),
            $this->dic->get(ClipCreatorInterface::class),
            $clipId
        );
    }

    /**
     * Maps an object type name like `song` to its corresponding model class
     */
    public function mapObjectType(string $objectType, int $objectId): ?database_object
    {
        $className = ObjectTypeToClassNameMapper::map($objectType);

        if ($className === $objectType) {
            return null;
        }
        $mapper = $this->getMap()[$className] ?? null;
        if ($mapper !== null) {
            return $mapper($objectId);
        }

        return new $className($objectId);
    }

    /**
     * @return array<class-string, callable(int): database_object>
     */
    private function getMap(): array
    {
        if ($this->map === null) {
            $this->map = [
                Live_Stream::class => fn (int $objectId): LiveStreamInterface => $this->createLiveStream($objectId),
                Share::class => fn (int $objectId): ShareInterface => $this->createShare($objectId),
                Label::class => fn (int $objectId): Label => $this->createLabel($objectId),
                Broadcast::class => fn (int $objectId): BroadcastInterface => $this->createBroadcast($objectId),
                Clip::class => fn (int $objectId): Clip => $this->createClip($objectId),
            ];
        }

        return $this->map;
    }
}
