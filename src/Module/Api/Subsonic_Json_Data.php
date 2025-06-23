<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\PrivateMsg;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\User_Playlist;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\SongRepositoryInterface;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Subsonic_Json_Data Class
 *
 * This class takes care of all of the xml document stuff for SubSonic Responses
 * https://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.16.1.xsd
 */
class Subsonic_Json_Data
{
    /**
     * _createResponse [OS]
     *
     * Common answer wrapper.Subsonic
     * @return array{
     *     'subsonic-response': array{
     *         'status': string,
     *         'version': string,
     *         'type': string,
     *         'serverVersion': string,
     *         'openSubsonic': bool
     *     }
     * }
     */
    private static function _createResponse(): array
    {

        return [
            'subsonic-response' => [
                'status' => 'ok',
                'version' => Subsonic_Api::API_VERSION,
                'type' => 'ampache',
                'serverVersion' => Api::$version,
                'openSubsonic' => true,
            ]
        ];
    }

    /**
     * _createSuccessResponse [OS]
     * @return array{
     *     'subsonic-response': array{
     *         'status': string,
     *         'version': string,
     *         'type': string,
     *         'serverVersion': string,
     *         'openSubsonic': bool
     *     }
     * }
     */
    private static function _createSuccessResponse(string $function = ''): array
    {
        debug_event(self::class, 'API success in function ' . $function . '-' . Subsonic_Api::API_VERSION, 5);

        return self::_createResponse();
    }

    /**
     * _createFailedResponse [OS]
     * @return array{
     *     'subsonic-response': array{
     *         'status': string,
     *         'version': string,
     *         'type': string,
     *         'serverVersion': string,
     *         'openSubsonic': bool,
     *         'error': array{
     *             'code': int,
     *             'message': string,
     *             'helpUrl': string
     *         }
     *     }
     * }
     */
    private static function _createFailedResponse(string $function = ''): array
    {
        debug_event(self::class, 'API success in function ' . $function . '-' . Subsonic_Api::API_VERSION, 5);

        return [
            'subsonic-response' => [
                'status' => 'failed',
                'version' => Subsonic_Api::API_VERSION,
                'type' => 'ampache',
                'serverVersion' => Api::$version,
                'openSubsonic' => true,
                'error' => [
                    'code' => Subsonic_Api::SSERROR_GENERIC,
                    'message' => 'Error creating response.',
                    'helpUrl' => 'https://ampache.org/api/subsonic'
                ]
            ]
        ];
    }

    /**
     * _getJukeboxStatus
     * @return array{
     *     'currentIndex': string,
     *     'playing': bool,
     *     'gain': string,
     *     'position': string
     * }
     */
    private static function _getJukeboxStatus(LocalPlay $localplay): array
    {
        $json   = [];
        $status = $localplay->status();
        $index  = (((int)$status['track']) === 0)
            ? 0
            : $status['track'] - 1;

        $json['currentIndex'] = (string)$index;
        $json['playing']      = ($status['state'] == 'play');
        $json['gain']         = (string)$status['volume'];
        $json['position']     = '0'; // TODO Not supported

        return $json;
    }

    /**
     * _getPlaylist_Playlist
     * @return array{
     *     'id': string,
     *     'name': string,
     *     'owner': string,
     *     'public': bool,
     *     'created': string,
     *     'changed': string,
     *     'songCount': string,
     *     'duration': int,
     *     'coverArt'?: string,
     *     'entry'?: list<array<string, mixed>>
     * }
     */
    private static function _getPlaylist_Playlist(Playlist $playlist, bool $songs = false): array
    {
        $sub_id    = Subsonic_Api::getPlaylistSubId($playlist->id);
        $songcount = $playlist->get_media_count('song');
        $duration  = ($songcount > 0) ? $playlist->get_total_duration() : 0;

        $json = [
            'id' => $sub_id,
            'name' => (string)$playlist->get_fullname(),
            'owner' => (string)$playlist->username,
            'public' => ($playlist->type != 'private'),
            'created' => date('c', $playlist->date),
            'changed' => date('c', (int)$playlist->last_update),
            'songCount' => (string)$songcount,
            'duration' => $duration,
        ];

        if ($playlist->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        if ($songs) {
            $json['entry'] = [];
            $allsongs      = $playlist->get_songs();
            foreach ($allsongs as $song_id) {
                $json['entry'][] = self::_getChild($song_id, 'song');
            }
        }

        return $json;
    }

    /**
     * _getPlaylist_Search
     * @return array{
     *     'id': string,
     *     'name': string,
     *     'owner': string,
     *     'public': bool,
     *     'created': string,
     *     'changed': string,
     *     'songCount': int,
     *     'duration': int,
     *     'coverArt'?: string,
     *     'entry'?: list<array<string, mixed>>
     * }
     */
    private static function _getPlaylist_Search(Search $search, bool $songs = false): array
    {
        $sub_id = Subsonic_Api::getSmartPlaylistSubId($search->id);

        $json = [
            'id' => $sub_id,
            'name' => (string)$search->get_fullname(),
            'owner' => (string)$search->username,
            'public' => ($search->type != 'private'),
            'created' => date('c', $search->date),
            'changed' => date('c', time()),
        ];

        $json['songCount'] = (int)$search->last_count;
        $json['duration']  = (int)$search->last_duration;
        $json['coverArt']  = $sub_id;

        if ($songs) {
            $allsongs = $search->get_songs();
            $entries  = [];
            foreach ($allsongs as $song_id) {
                $entries[] = self::_getChild($song_id, 'song');
            }
            $json['entry'] = $entries;
        }

        return $json;
    }

    /**
     * _getPodcastEpisode
     *
     * A Podcast episodeSubsonic
     * @see self::_getChild()
     * @return array{
     *     'id': string,
     *     'parent': string,
     *     'title': string,
     *     'album': string,
     *     'duration': int,
     *     'genre': string,
     *     'isDir': bool,
     *     'parent': string,
     *     'coverArt'?: string,
     *     'starred'?: string,
     *     'size'?: int,
     *     'suffix'?: string,
     *     'contentType'?: string,
     *     'path'?: string,
     *     'streamId'?: string,
     *     'channelId': string,
     *     'description'?: string,
     *     'status': string,
     *     'publishDate'?: string,
     * }
     */
    private static function _getPodcastEpisode(Podcast_Episode $episode): array
    {
        $sub_id    = Subsonic_Api::getPodcastEpisodeSubId($episode->id);
        $subParent = Subsonic_Api::getPodcastSubId($episode->podcast);

        $json = [
            'id' => $sub_id,
            'channelId' => $subParent,
            'title' => (string)$episode->get_fullname(),
            'album' => $episode->getPodcastName(),
            'description' => $episode->get_description(),
            'duration' => $episode->time,
            'genre' => "Podcast",
            'isDir' => false,
            'publishDate' => $episode->getPubDate()->format(DATE_ATOM),
            'status' => (string)$episode->state,
            'parent' => $subParent,
        ];

        if ($episode->has_art()) {
            $json['coverArt'] = $subParent;
        }

        $starred = new Userflag($episode->id, 'podcast_episode');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        if ($episode->file) {
            $json['streamId']    = $sub_id;
            $json['size']        = $episode->size;
            $json['suffix']      = $episode->type;
            $json['contentType'] = (string)$episode->mime;
            // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
            $path         = basename($episode->file);
            $json['path'] = $path;
        }

        return $json;
    }

    /**
     * _getPodcast
     *
     * A Podcast channel.
     * @return array{
     *     'id':string,
     *     'url': string,
     *     'title': string,
     *     'description': string,
     *     'coverArt'?: string,
     *     'status': string,
     *     'episode'?: array<array<string, mixed>>
     * }
     */
    private static function _getPodcast(Podcast $podcast, bool $includeEpisodes): array
    {

        $sub_id = Subsonic_Api::getPodcastSubId($podcast->getId());

        $json = [
            'id' => $sub_id,
            'url' => $podcast->getFeedUrl(),
            'title' => (string)$podcast->get_fullname(),
            'description' => $podcast->get_description(),
        ];

        if ($podcast->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        $json['status'] = 'completed';

        if ($includeEpisodes) {
            $episodes = $podcast->getEpisodeIds();

            $json['episode'] = [];
            foreach ($episodes as $episode_id) {
                $episode           = new Podcast_Episode($episode_id);
                $json['episode'][] = self::_getPodcastEpisode($episode);
            }
        }

        return $json;
    }

    /**
     * _getChatMessage
     *
     * A chatMessage.
     * @return array{
     *     'username': string,
     *     'time': string,
     *     'message': string
     * }
     */
    private static function _getChatMessage(PrivateMsg $message, User $user): array
    {
        return [
            'username' => ($user->fullname_public) ? (string)$user->fullname : (string)$user->username,
            'time' => (string)($message->getCreationDate() * 1000),
            'message' => (string)$message->getMessage(),
        ];
    }

    /**
     * _getChildArray
     * @param array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * } $child
     * @return array{
     *     'id': string,
     *     'parent'?: string,
     *     'isDir': bool,
     *     'title': string,
     *     'artist': string,
     *     'coverArt'?: string
     * }
     */
    private static function _getChildArray(array $child): array
    {
        $sub_id = Subsonic_Api::getArtistSubId($child['id']);
        $json   = ['id' => $sub_id];

        if (array_key_exists('catalog_id', $child)) {
            $json['parent'] = Subsonic_Api::getCatalogSubId($child['catalog_id']);
        }

        $json['isDir']  = true;
        $json['title']  = (string)$child['f_name'];
        $json['artist'] = (string)$child['f_name'];
        if (array_key_exists('has_art', $child) && !empty($child['has_art'])) {
            $json['coverArt'] = $sub_id;
        }

        return $json;
    }

    /**
     * _getChild
     *
     * A media.Subsonic
     * @return array{}|array{
     *     'id': string,
     *     'parent'?: string,
     *     'isDir': bool,
     *     'title': string,
     *     'album'?: string,
     *     'artist'?: string,
     *     'track'?: int,
     *     'year'?: int,
     *     'genre'?: string,
     *     'coverArt'?: string,
     *     'size'?: int,
     *     'contentType'?: string,
     *     'suffix'?: string,
     *     'transcodedContentType'?: string,
     *     'transcodedSuffix'?: string,
     *     'duration'?: int,
     *     'bitRate'?: int,
     *     'bitDepth'?: int,
     *     'samplingRate'?: int,
     *     'channelCount'?: int,
     *     'path'?: string,
     *     'isVideo'?: bool,
     *     'userRating'?: int,
     *     'averageRating'?: float,
     *     'playCount'?: int,
     *     'discNumber'?: int,
     *     'created'?: string,
     *     'starred'?: string,
     *     'albumId'?: string,
     *     'artistId'?: string,
     *     'type'?: string,
     *     'mediaType'?: string,
     *     'bookmarkPosition'?: int,
     *     'originalWidth'?: int,
     *     'originalHeight'?: int,
     *     'played'?: string,
     *     'bpm'?: int,
     *     'comment'?: string,
     *     'sortName'?: string,
     *     'musicBrainzId'?: string,
     *     'isrc'?: string[],
     *     'genres'?: array<'name', string>,
     *     'artists'?: array<int, array{
     *         'id': string,
     *         'name': string,
     *         'coverArt'?: string,
     *         'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: array<string>
     *     }>,
     *     'displayArtist'?: string,
     *     'albumArtists'?: array<int, array{
     *         'id': string,
     *         'name': string,
     *         'coverArt'?: string,
     *         'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: array<string>
     *     }>,
     *     'displayAlbumArtist'?: string,
     *     'contributors'?: array{
     *         'contributor', array{
     *             'role': string,
     *             'subRole': string,
     *             'artist': array<int, array{
     *                 'id': string,
     *                 'name': string,
     *                 'coverArt'?: string,
     *                 'artistImageUrl'?: string,
     *                 'albumCount'?: int,
     *                 'starred'?: string,
     *                 'musicBrainzId'?: string,
     *                 'sortName'?: string,
     *                 'roles'?: array<string>
     *             }>
     *         }
     *     },
     *     'displayComposer'?: string,
     *     'moods'?: string[],
     *     'replayGain'?: array{
     *         'trackGain': float,
     *         'albumGain': float,
     *         'trackPeak': float,
     *         'albumPeak': float,
     *         'baseGain': float
     *     },
     *     'explicitStatus'?: string
     * }
     */
    private static function _getChild(int $object_id, string $object_type): array
    {
        $json = [];
        switch ($object_type) {
            case 'song':
                $song = new Song($object_id);
                if ($song->isNew() === false && $song->enabled) {
                    $json = self::_getChildSong($song);
                }
                break;
            case 'album':
                $album = new Album($object_id);
                if ($album->isNew() === false) {
                    $json = self::_getChildAlbum($album);
                }
                break;
            case 'podcast_episode':
                $episode = new Podcast_Episode($object_id);
                if ($episode->isNew() === false && $episode->enabled) {
                    $json = self::_getChildPodcastEpisode($episode);
                }
                break;
            case 'video':
                $video = new Video($object_id);
                if ($video->isNew() === false && $video->enabled) {
                    $json = self::_getChildVideo($video);
                }
                break;
        }

        return $json;
    }

    /**
     * _getChildAlbum
     *
     * Child media.Subsonic
     * @return array{
     *     'id': string,
     *     'parent'?: string,
     *     'isDir': bool,
     *     'title': string,
     *     'album'?: string,
     *     'artist'?: string,
     *     'track'?: int,
     *     'year'?: int,
     *     'genre'?: string,
     *     'coverArt'?: string,
     *     'size'?: int,
     *     'contentType'?: string,
     *     'suffix'?: string,
     *     'transcodedContentType'?: string,
     *     'transcodedSuffix'?: string,
     *     'duration'?: int,
     *     'bitRate'?: int,
     *     'bitDepth'?: int,
     *     'samplingRate'?: int,
     *     'channelCount'?: int,
     *     'path'?: string,
     *     'isVideo'?: bool,
     *     'userRating'?: int,
     *     'averageRating'?: float,
     *     'playCount'?: int,
     *     'discNumber'?: int,
     *     'created'?: string,
     *     'starred'?: string,
     *     'albumId'?: string,
     *     'artistId'?: string,
     *     'type'?: string,
     *     'mediaType'?: string,
     *     'bookmarkPosition'?: int,
     *     'originalWidth'?: int,
     *     'originalHeight'?: int,
     *     'played'?: string,
     *     'bpm'?: int,
     *     'comment'?: string,
     *     'sortName'?: string,
     *     'musicBrainzId'?: string,
     *     'isrc'?: string[],
     *     'genres'?: array<'name', string>,
     *     'artists'?: array<int, array{
     *         'id': string,
     *         'name': string,
     *         'coverArt'?: string,
     *         'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: array<string>
     * }>,
     *     'displayArtist'?: string,
     *     'albumArtists'?: array<int, array{
     *         'id': string,
     *         'name': string,
     *         'coverArt'?: string,
     *         'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: array<string>
     *     }>,
     *     'displayAlbumArtist'?: string,
     *     'contributors'?: array{
     *         'contributor', array{
     *             'role': string,
     *             'subRole': string,
     *             'artist': array<int, array{
     *                 'id': string,
     *                 'name': string,
     *                 'coverArt'?: string,
     *                 'artistImageUrl'?: string,
     *                 'albumCount'?: int,
     *                 'starred'?: string,
     *                 'musicBrainzId'?: string,
     *                 'sortName'?: string,
     *                 'roles'?: array<string>
     *             }>
     *         }
     *     },
     *     'displayComposer'?: string,
     *     'moods'?: string[],
     *     'replayGain'?: array{
     *         'trackGain': float,
     *         'albumGain': float,
     *         'trackPeak': float,
     *         'albumPeak': float,
     *         'baseGain': float
     *     },
     *     'explicitStatus'?: string
     * }
     */
    private static function _getChildAlbum(Album $album): array
    {
        $sub_id       = Subsonic_Api::getAlbumSubId($album->id);
        $album_artist = $album->findAlbumArtist();
        $subParent    = ($album_artist)
            ? Subsonic_Api::getArtistSubId($album_artist)
            : '';

        $json = [
            'id' => $sub_id,
            'parent' => $subParent,
            'title' => $album->get_fullname(),
            'album' => $album->get_fullname(),
            'isDir' => false,
            'isVideo' => false,
            'type' => 'music',
            'artistId' => $subParent,
            'artist' => (string)$album->get_artist_fullname(),
        ];

        if ($album->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        $json['duration'] = (int)$album->time;

        $rating      = new Rating($album->id, 'album');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $json['userRating'] = (int)ceil($user_rating);
        }

        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $json['averageRating'] = $avg_rating;
        }

        $starred = new Userflag($album->id, 'album');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        if ($album->year > 0) {
            $json['year'] = $album->year;
        }

        $tags = Tag::get_object_tags('album', $album->id);
        if (!empty($tags)) {
            $json['genre'] = implode(',', array_column($tags, 'name'));
        }

        return $json;
    }

    /**
     * _getAlbumID3 [OS]
     *
     * An album from ID3 tags.
     * https://opensubsonic.netlify.app/docs/responses/albumid3/
     * https://opensubsonic.netlify.app/docs/responses/albumid3withsongs/
     * @return array{
     *     'id': string,
     *     'name': string,
     *     'version'?: string,
     *     'artist'?: string,
     *     'artistId'?: string,
     *     'coverArt'?: string,
     *     'songCount': int,
     *     'duration': int,
     *     'playCount'?: int,
     *     'created': string,
     *     'starred'?: string,
     *     'year'?: int,
     *     'genre'?: string,
     *     'played'?: string,
     *     'userRating'?: int,
     *     'recordLabels'?: array{'name': string},
     *     'musicBrainzId'?: string,
     *     'genres'?: array{'name': string},
     *     'artists'?: array<int, array{
     *      'id': string,
     *      'name': string,
     *      'coverArt'?: string,
     *      'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: string[]
     *     }>,
     *     'displayArtist'?: string,
     *     'releaseTypes'?: string[],
     *     'moods'?: string[],
     *     'sortName'?: string,
     *     'originalReleaseDate'?: array{
     *         'year'?: int,
     *         'month'?: int,
     *         'day'?: int
     *     },
     *     'releaseDate'?: array{
     *         'year'?: int,
     *         'month'?: int,
     *         'day'?: int
     *     },
     *     'isCompilation'?: bool,
     *     'explicitStatus'?: string,
     *     'discTitles'?: array{
     *         'disc': int,
     *         'title': string
     *     },
     *     'song'?: array<array<string, mixed>>
     * }
     */
    private static function _getAlbumID3(Album $album, bool $songs = false): array
    {
        $sub_id       = Subsonic_Api::getAlbumSubId($album->id);
        $album_artist = $album->findAlbumArtist();
        $subParent    = ($album_artist) ? Subsonic_Api::getArtistSubId($album_artist) : false;
        $f_name       = (string)$album->get_fullname();

        $json = [
            'id' => $sub_id,
            'parent' => '',
            'album' => $f_name,
            'title' => $f_name,
            'name' => $f_name,
            'isDir' => true,
        ];

        if ($subParent) {
            $json['parent'] = $subParent;
        } else {
            unset($json['parent']);
        }

        if ($album->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        $json['songCount'] = $album->song_count;
        $json['created']   = date('c', (int)$album->addition_time);
        $json['duration']  = (int)$album->time;
        $json['playCount'] = $album->total_count;
        if ($subParent) {
            $json['artistId'] = $subParent;
        }
        $json['artist'] = (string)$album->get_artist_fullname();
        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');
        $year          = ($original_year && $album->original_year)
            ? (int)$album->original_year
            : $album->year;
        if ($year > 0) {
            $json['year'] = $year;
        }

        $tags = Tag::get_object_tags('album', $album->id);
        if (!empty($tags)) {
            $json['genre'] = implode(',', array_column($tags, 'name'));
        }

        $rating      = new Rating($album->id, 'album');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $json['userRating'] = (int)ceil($user_rating);
        }

        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $json['averageRating'] = $avg_rating;
        }

        $starred = new Userflag($album->id, 'album');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        if ($songs) {
            $allsongs = self::getAlbumRepository()->getSongs($album->getId());
            $entries  = [];
            foreach ($allsongs as $song_id) {
                $entries[] = self::_getChild($song_id, 'song');
            }
            $json['song'] = $entries;
        }

        return $json;
    }

    /**
     * _getArtistID3
     *
     * An artist from ID3 tags.
     * https://opensubsonic.netlify.app/docs/responses/artistid3/
     * @return array{
     *     'id': string,
     *     'name': string,
     *     'coverArt'?: string,
     *     'albumCount': int,
     *     'starred'?: string
     * }
     */
    private static function _getArtistID3(Artist $artist): array
    {
        $sub_id = Subsonic_Api::getArtistSubId($artist->id);
        $json   = [
            'id' => $sub_id,
            'name' => (string)$artist->get_fullname(),
        ];

        if ($artist->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        $json['albumCount'] = $artist->album_count;

        $starred = new Userflag($artist->id, 'artist');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        return $json;
    }

    /**
     * _getArtist
     *
     * Artist details.Subsonic
     * @return array{
     *     'id': string,
     *     'name': string,
     *     'coverArt'?: string,
     *     'starred'?: string,
     *     'userRating'?: int,
     *     'averageRating'?: float
     * }
     */
    private static function _getArtist(Artist $artist, bool $AlbumID3 = false): array
    {
        $sub_id = Subsonic_Api::getArtistSubId($artist->id);
        $json   = [
            'id' => $sub_id,
            'name' => (string)$artist->get_fullname(),
        ];

        if ($artist->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        $json['albumCount'] = $artist->album_count;

        $starred = new Userflag($artist->id, 'artist');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }
        $rating      = new Rating($artist->id, 'artist');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $json['userRating'] = (int)ceil($user_rating);
        }

        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $json['averageRating'] = $avg_rating;
        }

        if ($AlbumID3) {
            $allalbums = self::getAlbumRepository()->getAlbumByArtist($artist->id);
            $albumJson = [];
            foreach ($allalbums as $album_id) {
                $album       = new Album($album_id);
                $albumJson[] = self::_getAlbumID3($album);
            }
            if (!empty($albumJson)) {
                $json['album'] = $albumJson;
            }
        }

        return $json;
    }

    /**
     * _getArtistArray
     * @param array<int, array{
     *     'id': string,
     *     'name': string,
     *     'coverArt'?: string,
     *     'albumCount': int,
     *     'starred'?: string
     * }> $artist_list
     * @param array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * } $artist
     * @return array<int, array{
     *     'id': string,
     *     'name': string,
     *     'coverArt'?: string,
     *     'albumCount': int,
     *     'starred'?: string
     * }>
     */
    private static function _getArtistArray(array $artist_list, array $artist): array
    {
        $sub_id = Subsonic_Api::getArtistSubId($artist['id']);

        $json = [
            'id' => $sub_id,
            'name' => (string)$artist['f_name'],
        ];

        if (array_key_exists('has_art', $artist) && !empty($artist['has_art'])) {
            $json['coverArt'] = $sub_id;
        }

        $json['albumCount'] = $artist['album_count'];

        $starred = new Userflag($artist['id'], 'artist');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        $artist_list[] = $json;

        return $artist_list;
    }

    /**
     * _getArtistInfo
     *
     * Artist info.
     * @param Artist $artist
     * @param array{
     *     id: ?int,
     *     summary: ?string,
     *     placeformed: ?string,
     *     yearformed: ?int,
     *     largephoto: ?string,
     *     smallphoto: ?string,
     *     mediumphoto: ?string,
     *     megaphoto: ?string
     * } $info
     * @param list<array{
     *     id: ?int,
     *     name: string,
     *     rel?: ?string,
     *     mbid?: ?string
     * }> $similars
     * @param string $elementName
     *@return array{
     *     'biography'?: string,
     *     'musicBrainzId': string,
     *     'smallImageUrl': string,
     *     'mediumImageUrl': string,
     *     'largeImageUrl': string,
     *     'similarArtist': array<array{
     *         'id': string,
     *         'name': string,
     *         'rel'?: string,
     *         'mbid'?: string
     *     }>
     * }
     */
    private static function _getArtistInfo(Artist $artist, array $info, array $similars, string $elementName): array
    {
        $json      = [];
        $biography = trim((string)$info['summary']);
        if (!empty($biography)) {
            $json['biography'] = htmlspecialchars($biography);
        }

        $json['musicBrainzId']  = (string)$artist->mbid;
        $json['smallImageUrl']  = htmlentities((string)$info['smallphoto']);
        $json['mediumImageUrl'] = htmlentities((string)$info['mediumphoto']);
        $json['largeImageUrl']  = htmlentities((string)$info['largephoto']);
        $json['similarArtist']  = [];

        foreach ($similars as $similar) {
            if (($similar['id'] !== null)) {
                $sim_artist = new Artist($similar['id']);
                switch ($elementName) {
                    case 'artistInfo':
                        $json['similarArtist'][] = self::_getArtist($sim_artist);
                        break;
                    case 'artistInfo2':
                        $json['similarArtist'][] = self::_getArtistID3($sim_artist);
                        break;
                }
            } else {
                // TODO there might be a difference between artistInfo and artistInfo2 for empty data
                $json['similarArtist'][] = [
                    'id' => '-1',
                    'name' => (string)$similar['name'],
                ];
            }
        }

        return $json;
    }

    /**
     * _getBookmark
     *
     * A bookmark.
     * @return array{
     *     'position': string,
     *     'username': string,
     *     'comment': string,
     *     'created': string,
     *     'changed': string,
     *     'entry'?: array{}|array{
     *         'id': string,
     *         'parent'?: string,
     *         'isDir': bool,
     *         'title': string,
     *         'album'?: string,
     *         'artist'?: string,
     *         'track'?: int,
     *         'year'?: int,
     *         'genre'?: string,
     *         'coverArt'?: string,
     *         'size'?: int,
     *         'contentType'?: string,
     *         'suffix'?: string,
     *         'transcodedContentType'?: string,
     *         'transcodedSuffix'?: string,
     *         'duration'?: int,
     *         'bitRate'?: int,
     *         'bitDepth'?: int,
     *         'samplingRate'?: int,
     *         'channelCount'?: int,
     *         'path'?: string,
     *         'isVideo'?: bool,
     *         'userRating'?: int,
     *         'averageRating'?: float,
     *         'playCount'?: int,
     *         'discNumber'?: int,
     *         'created'?: string,
     *         'starred'?: string,
     *         'albumId'?: string,
     *         'artistId'?: string,
     *         'type'?: string,
     *         'mediaType'?: string,
     *         'bookmarkPosition'?: int,
     *         'originalWidth'?: int,
     *         'originalHeight'?: int,
     *         'played'?: string,
     *         'bpm'?: int,
     *         'comment'?: string,
     *         'sortName'?: string,
     *         'musicBrainzId'?: string,
     *         'isrc'?: string[],
     *         'genres'?: array<'name', string>,
     *         'artists'?: array<int, array{
     *             'id': string,
     *             'name': string,
     *             'coverArt'?: string,
     *             'artistImageUrl'?: string,
     *             'albumCount'?: int,
     *             'starred'?: string,
     *             'musicBrainzId'?: string,
     *             'sortName'?: string,
     *             'roles'?: array<string>
     *         }>,
     *         'displayArtist'?: string,
     *         'albumArtists'?: array<int, array{
     *             'id': string,
     *             'name': string,
     *             'coverArt'?: string,
     *             'artistImageUrl'?: string,
     *             'albumCount'?: int,
     *             'starred'?: string,
     *             'musicBrainzId'?: string,
     *             'sortName'?: string,
     *             'roles'?: array<string>
     *         }>,
     *         'displayAlbumArtist'?: string,
     *         'contributors'?: array{
     *             'contributor', array{
     *                 'role': string,
     *                 'subRole': string,
     *                 'artist': array<int, array{
     *                     'id': string,
     *                     'name': string,
     *                     'coverArt'?: string,
     *                     'artistImageUrl'?: string,
     *                     'albumCount'?: int,
     *                     'starred'?: string,
     *                     'musicBrainzId'?: string,
     *                     'sortName'?: string,
     *                     'roles'?: array<string>
     *                 }>
     *             }
     *         },
     *         'displayComposer'?: string,
     *         'moods'?: string[],
     *         'replayGain'?: array{
     *             'trackGain': float,
     *             'albumGain': float,
     *             'trackPeak': float,
     *             'albumPeak': float,
     *             'baseGain': float
     *         },
     *         'explicitStatus'?: string
     *     }
     * }
     */
    private static function _getBookmark(Bookmark $bookmark): array
    {
        $json = [
            'position' => (string)$bookmark->position,
            'username' => $bookmark->getUserName(),
            'comment' => (string)$bookmark->comment,
            'created' => date("c", (int)$bookmark->creation_date),
            'changed' => date("c", (int)$bookmark->update_date),
            'entry' => [],
        ];

        if ($bookmark->object_type == "song") {
            $song          = new Song($bookmark->object_id);
            $json['entry'] = self::_getChildSong($song);
        } elseif ($bookmark->object_type == "video") {
            $video         = new Video($bookmark->object_id);
            $json['entry'] = self::_getChildVideo($video);
        } elseif ($bookmark->object_type == "podcast_episode") {
            $episode       = new Podcast_Episode($bookmark->object_id);
            $json['entry'] = self::_getChildPodcastEpisode($episode);
        }

        return $json;
    }

    /**
     * _getIndex
     *
     * An indexed artist list.
     * @param list<array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     * @return array<int, mixed>
     */
    private static function _getIndex(array $artists): array
    {
        $sharpartists = [];
        $json         = [];
        $index        = [];
        foreach ($artists as $artist) {
            // list Letters
            if (strlen((string)$artist['name']) > 0) {
                $letter = strtoupper((string)$artist['name'][0]);
                if ($letter == 'X' || $letter == 'Y' || $letter == 'Z') {
                    $letter = 'X-Z';
                } elseif (!preg_match("/^[A-W]$/", $letter)) {
                    $sharpartists[] = $artist;
                    continue;
                }

                if (!isset($index[$letter])) {
                    $index[$letter] = [];
                }

                $index[$letter] = self::_getArtistArray($index[$letter], $artist);
            }
        }

        foreach ($index as $letter => $artist) {
            $json[] = [
                'name' => $letter,
                'artist' => $artist,
            ];
        }

        // Always add # index at the end
        if (count($sharpartists) > 0) {
            $index = [];
            foreach ($sharpartists as $artist) {
                $index = self::_getArtistArray($index, $artist);
            }

            if (!empty($index)) {
                $json[] = [
                    'name' => '#',
                    'artist' => $index,
                ];
            }
        }

        return $json;
    }

    /**
     * _getChildSong
     *
     * Child media.Subsonic
     * @return array{
     *     'id': string,
     *     'parent'?: string,
     *     'isDir': bool,
     *     'title': string,
     *     'album'?: string,
     *     'artist'?: string,
     *     'track'?: int,
     *     'year'?: int,
     *     'genre'?: string,
     *     'coverArt'?: string,
     *     'size'?: int,
     *     'contentType'?: string,
     *     'suffix'?: string,
     *     'transcodedContentType'?: string,
     *     'transcodedSuffix'?: string,
     *     'duration'?: int,
     *     'bitRate'?: int,
     *     'bitDepth'?: int,
     *     'samplingRate'?: int,
     *     'channelCount'?: int,
     *     'path'?: string,
     *     'isVideo'?: bool,
     *     'userRating'?: int,
     *     'averageRating'?: float,
     *     'playCount'?: int,
     *     'discNumber'?: int,
     *     'created'?: string,
     *     'starred'?: string,
     *     'albumId'?: string,
     *     'artistId'?: string,
     *     'type'?: string,
     *     'mediaType'?: string,
     *     'bookmarkPosition'?: int,
     *     'originalWidth'?: int,
     *     'originalHeight'?: int,
     *     'played'?: string,
     *     'bpm'?: int,
     *     'comment'?: string,
     *     'sortName'?: string,
     *     'musicBrainzId'?: string,
     *     'isrc'?: string[],
     *     'genres'?: array<'name', string>,
     *     'artists'?: array<int, array{
     *         'id': string,
     *         'name': string,
     *         'coverArt'?: string,
     *         'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: array<string>
     *     }>,
     *     'displayArtist'?: string,
     *     'albumArtists'?: array<int, array{
     *         'id': string,
     *         'name': string,
     *         'coverArt'?: string,
     *         'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: array<string>
     *     }>,
     *     'displayAlbumArtist'?: string,
     *     'contributors'?: array{
     *         'contributor', array{
     *             'role': string,
     *             'subRole': string,
     *             'artist': array<int, array{
     *                 'id': string,
     *                 'name': string,
     *                 'coverArt'?: string,
     *                 'artistImageUrl'?: string,
     *                 'albumCount'?: int,
     *                 'starred'?: string,
     *                 'musicBrainzId'?: string,
     *                 'sortName'?: string,
     *                 'roles'?: array<string>
     *             }>
     *         }
     *     },
     *     'displayComposer'?: string,
     *     'moods'?: string[],
     *     'replayGain'?: array{
     *         'trackGain': float,
     *         'albumGain': float,
     *         'trackPeak': float,
     *         'albumPeak': float,
     *         'baseGain': float
     *     },
     *     'explicitStatus'?: string
     * }
     */
    private static function _getChildSong(Song $song): array
    {
        $sub_id    = Subsonic_Api::getSongSubId($song->id);
        $subParent = Subsonic_Api::getAlbumSubId($song->album);

        $json = [
            'id' => $sub_id,
            'parent' => $subParent,
            'title' => (string)$song->title,
            'isDir' => false,
            'isVideo' => false,
            'type' => 'music',
            'albumId' => $subParent,
            'album' => (string)$song->get_album_fullname(),
            'artistId' => ($song->artist) ? Subsonic_Api::getArtistSubId($song->artist) : '',
            'artist' => (string)$song->get_artist_fullname(),
        ];

        if ($song->has_art()) {
            $art_id           = (AmpConfig::get('show_song_art', false)) ? $sub_id : $subParent;
            $json['coverArt'] = $art_id;
        }

        $json['duration'] = $song->time;
        $json['bitrate']  = ((int)($song->bitrate / 1024));

        $rating      = new Rating($song->id, 'song');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $json['userRating'] = (int)ceil($user_rating);
        }

        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $json['averageRating'] = $avg_rating;
        }

        $starred = new Userflag($song->id, 'song');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        if ($song->track > 0) {
            $json['track'] = $song->track;
        }

        if ($song->year > 0) {
            $json['year'] = $song->year;
        }

        $tags = Tag::get_object_tags('song', $song->id);
        if (!empty($tags)) {
            $json['genre'] = implode(',', array_column($tags, 'name'));
        }

        $json['size'] = $song->size;

        $disk = $song->disk;
        if ($disk > 0) {
            $json['discNumber'] = $disk;
        }

        $json['suffix']      = $song->type;
        $json['contentType'] = (string)$song->mime;
        // Always return the original filename, not the transcoded one
        $json['path'] = (string)$song->file;

        if (AmpConfig::get('transcode', 'default') != 'never') {
            $cache_path     = (string)AmpConfig::get('cache_path', '');
            $cache_target   = (string)AmpConfig::get('cache_target', '');
            $file_target    = Catalog::get_cache_path($song->getId(), $song->getCatalogId(), $cache_path, $cache_target);
            $transcode_type = ($file_target !== null && is_file($file_target))
                ? $cache_target
                : Stream::get_transcode_format($song->type, null, 'api');

            if (!empty($transcode_type) && $song->type !== $transcode_type) {
                // Set transcoding information
                $json['transcodedSuffix']      = $transcode_type;
                $json['transcodedContentType'] = Song::type_to_mime($transcode_type);
            }
        }

        return $json;
    }

    /**
     * _getChildPodcastEpisodeSubsonic
     * @return array{
     *     'id': string,
     *     'parent'?: string,
     *     'isDir': bool,
     *     'title': string,
     *     'album'?: string,
     *     'artist'?: string,
     *     'track'?: int,
     *     'year'?: int,
     *     'genre'?: string,
     *     'coverArt'?: string,
     *     'size'?: int,
     *     'contentType'?: string,
     *     'suffix'?: string,
     *     'transcodedContentType'?: string,
     *     'transcodedSuffix'?: string,
     *     'duration'?: int,
     *     'bitRate'?: int,
     *     'bitDepth'?: int,
     *     'samplingRate'?: int,
     *     'channelCount'?: int,
     *     'path'?: string,
     *     'isVideo'?: bool,
     *     'userRating'?: int,
     *     'averageRating'?: float,
     *     'playCount'?: int,
     *     'discNumber'?: int,
     *     'created'?: string,
     *     'starred'?: string,
     *     'albumId'?: string,
     *     'artistId'?: string,
     *     'type'?: string,
     *     'mediaType'?: string,
     *     'bookmarkPosition'?: int,
     *     'originalWidth'?: int,
     *     'originalHeight'?: int,
     *     'played'?: string,
     *     'bpm'?: int,
     *     'comment'?: string,
     *     'sortName'?: string,
     *     'musicBrainzId'?: string,
     *     'isrc'?: string[],
     *     'genres'?: array<'name', string>,
     *     'artists'?: array<int, array{
     *         'id': string,
     *         'name': string,
     *         'coverArt'?: string,
     *         'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: array<string>
     *     }>,
     *     'displayArtist'?: string,
     *     'albumArtists'?: array<int, array{
     *         'id': string,
     *         'name': string,
     *         'coverArt'?: string,
     *         'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: array<string>
     *     }>,
     *     'displayAlbumArtist'?: string,
     *     'contributors'?: array{
     *         'contributor', array{
     *             'role': string,
     *             'subRole': string,
     *             'artist': array<int, array{
     *                 'id': string,
     *                 'name': string,
     *                 'coverArt'?: string,
     *                 'artistImageUrl'?: string,
     *                 'albumCount'?: int,
     *                 'starred'?: string,
     *                 'musicBrainzId'?: string,
     *                 'sortName'?: string,
     *                 'roles'?: array<string>
     *             }>
     *         }
     *     },
     *     'displayComposer'?: string,
     *     'moods'?: string[],
     *     'replayGain'?: array{
     *         'trackGain': float,
     *         'albumGain': float,
     *         'trackPeak': float,
     *         'albumPeak': float,
     *         'baseGain': float
     *     },
     *     'explicitStatus'?: string
     * }
     */
    private static function _getChildPodcastEpisode(Podcast_Episode $episode): array
    {
        $sub_id    = Subsonic_Api::getPodcastEpisodeSubId($episode->id);
        $subParent = Subsonic_Api::getPodcastSubId($episode->podcast);

        $json = [
            'id' => $sub_id,
            'parent' => $subParent,
            'title' => (string)$episode->get_fullname(),
            'isDir' => false,
            'isVideo' => true,
            'type' => 'podcast',
        ];

        if ($episode->has_art()) {
            $json['coverArt'] = $subParent;
        }

        $json['duration'] = $episode->time;
        $json['bitrate']  = ((int)($episode->bitrate / 1024));

        $rating      = new Rating($episode->id, 'podcast_episode');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $json['userRating'] = (int)ceil($user_rating);
        }

        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $json['averageRating'] = $avg_rating;
        }

        $starred = new Userflag($episode->id, 'podcast_episode');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        if (isset($episode->year) && $episode->year > 0) {
            $json['year'] = $episode->year;
        }

        $tags = Tag::get_object_tags('podcast_episode', $episode->id);
        if (!empty($tags)) {
            $json['genre'] = implode(',', array_column($tags, 'name'));
        }

        $json['size']        = $episode->size;
        $json['suffix']      = $episode->type;
        $json['contentType'] = (string)$episode->mime;

        if (isset($episode->file)) {
            $json['path'] = basename($episode->file);
        }

        return $json;
    }

    /**
     * _getChildVideoSubsonic
     * @return array{
     *     'id': string,
     *     'parent'?: string,
     *     'isDir': bool,
     *     'title': string,
     *     'album'?: string,
     *     'artist'?: string,
     *     'track'?: int,
     *     'year'?: int,
     *     'genre'?: string,
     *     'coverArt'?: string,
     *     'size'?: int,
     *     'contentType'?: string,
     *     'suffix'?: string,
     *     'transcodedContentType'?: string,
     *     'transcodedSuffix'?: string,
     *     'duration'?: int,
     *     'bitRate'?: int,
     *     'bitDepth'?: int,
     *     'samplingRate'?: int,
     *     'channelCount'?: int,
     *     'path'?: string,
     *     'isVideo'?: bool,
     *     'userRating'?: int,
     *     'averageRating'?: float,
     *     'playCount'?: int,
     *     'discNumber'?: int,
     *     'created'?: string,
     *     'starred'?: string,
     *     'albumId'?: string,
     *     'artistId'?: string,
     *     'type'?: string,
     *     'mediaType'?: string,
     *     'bookmarkPosition'?: int,
     *     'originalWidth'?: int,
     *     'originalHeight'?: int,
     *     'played'?: string,
     *     'bpm'?: int,
     *     'comment'?: string,
     *     'sortName'?: string,
     *     'musicBrainzId'?: string,
     *     'isrc'?: string[],
     *     'genres'?: array<'name', string>,
     *     'artists'?: array<int, array{
     *         'id': string,
     *         'name': string,
     *         'coverArt'?: string,
     *         'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: array<string>
     *     }>,
     *     'displayArtist'?: string,
     *     'albumArtists'?: array<int, array{
     *         'id': string,
     *         'name': string,
     *         'coverArt'?: string,
     *         'artistImageUrl'?: string,
     *         'albumCount'?: int,
     *         'starred'?: string,
     *         'musicBrainzId'?: string,
     *         'sortName'?: string,
     *         'roles'?: array<string>
     *     }>,
     *     'displayAlbumArtist'?: string,
     *     'contributors'?: array{
     *         'contributor', array{
     *             'role': string,
     *             'subRole': string,
     *             'artist': array<int, array{
     *                 'id': string,
     *                 'name': string,
     *                 'coverArt'?: string,
     *                 'artistImageUrl'?: string,
     *                 'albumCount'?: int,
     *                 'starred'?: string,
     *                 'musicBrainzId'?: string,
     *                 'sortName'?: string,
     *                 'roles'?: array<string>
     *             }>
     *         }
     *     },
     *     'displayComposer'?: string,
     *     'moods'?: string[],
     *     'replayGain'?: array{
     *         'trackGain': float,
     *         'albumGain': float,
     *         'trackPeak': float,
     *         'albumPeak': float,
     *         'baseGain': float
     *     },
     *     'explicitStatus'?: string
     * }
     */
    private static function _getChildVideo(Video $video): array
    {
        $sub_id    = Subsonic_Api::getVideoSubId($video->id);
        $subParent = Subsonic_Api::getCatalogSubId($video->catalog);

        $json = [
            'id' => $sub_id,
            'parent' => $subParent,
            'title' => $video->getFileName(),
            'isDir' => false,
            'isVideo' => true,
            'type' => 'video',
        ];

        if ($video->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        $json['duration'] = $video->time;
        $json['bitrate']  = ((int)($video->bitrate / 1024));

        $rating      = new Rating($video->id, 'video');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $json['userRating'] = (int)ceil($user_rating);
        }

        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $json['averageRating'] = $avg_rating;
        }

        $starred = new Userflag($video->id, 'video');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        if (isset($video->year) && $video->year > 0) {
            $json['year'] = $video->year;
        }

        $tags = Tag::get_object_tags('video', $video->id);
        if (!empty($tags)) {
            $json['genre'] = implode(',', array_column($tags, 'name'));
        }

        $json['size']        = $video->size;
        $json['suffix']      = $video->type;
        $json['contentType'] = (string)$video->mime;

        // Create a clean fake path instead of real file path to have better offline mode storage on Subsonic clients
        $json['path'] = basename($video->file);

        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode', 'default');
        $valid_types   = Stream::get_stream_types_for_type($video->type, 'api');
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && !in_array('native', $valid_types))) {
            $transcode_settings = $video->get_transcode_settings(null, 'api');
            if (!empty($transcode_settings)) {
                $transcode_type                = $transcode_settings['format'];
                $json['transcodedSuffix']      = $transcode_type;
                $json['transcodedContentType'] = Video::type_to_mime($transcode_type);
            }
        }

        return $json;
    }

    /**
     * _getDirectory_Album
     * @return array{
     *     'id': string,
     *     'parent': string,
     *     'name': string,
     *     'starred'?: string,
     *     'child': array<int, array<string, mixed>>
     * }
     */
    private static function _getDirectory_Album(Album $album): array
    {
        $album_id = $album->id;

        $json = [
            'id' => (string)Subsonic_Api::getAlbumSubId($album_id)
        ];

        $album_artist = $album->findAlbumArtist();
        if ($album_artist) {
            $json['parent'] = Subsonic_Api::getArtistSubId($album_artist);
        } else {
            $json['parent'] = (string)$album->catalog;
        }

        $json['name'] = $album->get_fullname();

        $starred = new Userflag($album_id, 'album');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        $media_ids     = self::getAlbumRepository()->getSongs($album_id);
        $json['child'] = [];
        foreach ($media_ids as $song_id) {
            $song            = new Song($song_id);
            $json['child'][] = self::_getChildSong($song);
        }

        return $json;
    }

    /**
     * _getDirectory_Artist
     * @return array{
     *     'id': string,
     *     'parent'?: string,
     *     'name': string,
     *     'starred'?: string,
     *     'child': array<int, array<string, mixed>>
     * }
     */
    private static function _getDirectory_Artist(Artist $artist): array
    {
        $artist_id = $artist->id;

        $json = [
            'id' => (string)Subsonic_Api::getArtistSubId($artist_id)
        ];

        $data = Artist::get_id_array($artist_id);
        if (array_key_exists('catalog_id', $data)) {
            $json['parent'] = Subsonic_Api::getCatalogSubId($data['catalog_id']);
        }

        $json['name'] = (string)$data['f_name'];

        $starred = new Userflag($artist_id, 'artist');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        $allalbums     = self::getAlbumRepository()->getAlbumByArtist($artist_id);
        $json['child'] = [];
        foreach ($allalbums as $album_id) {
            $album           = new Album($album_id);
            $json['child'][] = self::_getChildAlbum($album);
        }

        return $json;
    }

    /**
     * _getDirectory_Catalog
     * @return array{
     *     'id': string,
     *     'name': string,
     *     'child': array<int, array<string, mixed>>
     * }
     */
    private static function _getDirectory_Catalog(Catalog $catalog): array
    {
        $catalog_id = $catalog->id;

        $json = [
            'id' => Subsonic_Api::getCatalogSubId($catalog_id),
            'name' => (string)$catalog->name,
        ];

        $allartists    = Catalog::get_artist_arrays([$catalog_id]);
        $json['child'] = [];
        foreach ($allartists as $artist) {
            $json['child'][] = self::_getChildArray($artist);
        }

        return $json;
    }

    /**
     * _getGenre
     *
     * A genre.
     * @param array{id: int, name: string, is_hidden: int, count: int} $genre
     * @return array{
     *     'songCount': int,
     *     'albumCount': int,
     *     'value': string
     * }
     */
    private static function _getGenre(array $genre): array
    {
        return [
            'songCount' => $genre['count'],
            'albumCount' => $genre['count'],
            'value' => (string)$genre['name'],
        ];
    }

    /**
     * _addIgnoredArticles
     */
    private static function _getIgnoredArticles(): string
    {
        $ignoredArticles = AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La');
        if (!empty($ignoredArticles)) {
            $ignoredArticles = str_replace('|', ' ', $ignoredArticles);

            return (string)$ignoredArticles;
        }

        return '';
    }

    /**
     * addResponse
     *
     * Generate a subsonic-response
     * https://opensubsonic.netlify.app/docs/responses/subsonic-response/
     * @return array{'subsonic-response': array{'status': string, 'version': string, 'type': string, 'serverVersion': string, 'openSubsonic': bool}}
     */
    public static function addResponse(string $function): array
    {
        return self::_createSuccessResponse($function);
    }

    /**
     * addAlbumID3
     *
     * An album from ID3 tags.
     * https://opensubsonic.netlify.app/docs/responses/albumid3/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addAlbumID3(array $response, Album $album, bool $songs = false, string $elementName = 'album'): array
    {
        if ($album->isNew()) {
            return $response;
        }

        $response['subsonic-response'][$elementName] = self::_getAlbumID3($album, $songs);

        return $response;
    }

    /**
     * addAlbumInfo
     *
     * Album info.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param array{
     *     id: int,
     *     summary: ?string,
     *     largephoto: ?string,
     *     smallphoto: ?string,
     *     mediumphoto: ?string,
     *     megaphoto: ?string
     * } $info
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addAlbumInfo(array $response, array $info, Album $album): array
    {
        $response['subsonic-response']['albumInfo'] = [
            'notes' => htmlspecialchars(trim((string)$info['summary'])),
            'musicBrainzId' => $album->mbid,
            'smallImageUrl' => htmlentities((string)$info['smallphoto']),
            'mediumImageUrl' => htmlentities((string)$info['mediumphoto']),
            'largeImageUrl' => htmlentities((string)$info['largephoto']),
        ];

        return $response;
    }

    /**
     * addAlbumList
     *
     * Album list.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $albums
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addAlbumList(array $response, array $albums): array
    {
        $json = ['album' => []];
        foreach ($albums as $album_id) {
            $json['album'][] = self::_getChild($album_id, 'album');
        }

        $response['subsonic-response']['albumList'] = $json;

        return $response;
    }

    /**
     * addAlbumList2
     *
     * Album list.
     * https://opensubsonic.netlify.app/docs/responses/albumlist2/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $albums
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addAlbumList2(array $response, array $albums): array
    {
        $response['subsonic-response']['albumList2'] = [];

        $json = [];
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            if ($album->isNew()) {
                continue;
            }
            $json[] = self::_getAlbumID3($album);
        }

        $response['subsonic-response']['albumList2']['album'] = $json;

        return $response;
    }

    /**
     * addArtist
     *
     * Artist details.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addArtist(array $response, Artist $artist): array
    {
        if ($artist->isNew()) {
            return $response;
        }

        $response['subsonic-response']['artist'] = self::_getArtist($artist);

        return $response;
    }

    /**
     * addArtistWithAlbumsID3
     *
     * Artist details.
     * https://opensubsonic.netlify.app/docs/responses/artistwithalbumsid3/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addArtistWithAlbumsID3(array $response, Artist $artist): array
    {
        if ($artist->isNew()) {
            return $response;
        }

        $response['subsonic-response']['artist'] = self::_getArtist($artist, true);

        return $response;
    }

    /**
     * addArtistID3
     *
     * An artist from ID3 tags.
     * https://opensubsonic.netlify.app/docs/responses/artistid3/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addArtistID3(array $response, Artist $artist): array
    {
        if ($artist->isNew()) {
            return $response;
        }

        $sub_id = Subsonic_Api::getArtistSubId($artist->id);
        $json   = [
            'id' => $sub_id,
            'name' => $artist->get_fullname(),
        ];

        if ($artist->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        $json['albumCount'] = $artist->album_count;

        $starred = new Userflag($artist->id, 'artist');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        $response['subsonic-response']['artist'] = $json;

        return $response;
    }

    /**
     * addArtistInfo
     *
     * Artist info.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param array{
     *     id: ?int,
     *     summary: ?string,
     *     placeformed: ?string,
     *     yearformed: ?int,
     *     largephoto: ?string,
     *     smallphoto: ?string,
     *     mediumphoto: ?string,
     *     megaphoto: ?string
     * } $info
     * @param list<array{
     *     id: ?int,
     *     name: string,
     *     rel?: ?string,
     *     mbid?: ?string
     * }> $similars
     *@return array{'subsonic-response': array<string, mixed>}
     */
    public static function addArtistInfo(array $response, array $info, Artist $artist, array $similars): array
    {
        $response['subsonic-response']['artistInfo'] = self::_getArtistInfo($artist, $info, $similars, 'artistInfo');

        return $response;
    }

    /**
     * addArtistInfo2
     *
     * Artist info.
     * https://opensubsonic.netlify.app/docs/responses/artistinfo2/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param array{
     *     id: ?int,
     *     summary: ?string,
     *     placeformed: ?string,
     *     yearformed: ?int,
     *     largephoto: ?string,
     *     smallphoto: ?string,
     *     mediumphoto: ?string,
     *     megaphoto: ?string
     * } $info
     * @param list<array{
     *     id: ?int,
     *     name: string,
     *     rel?: ?string,
     *     mbid?: ?string
     * }> $similars
     *@return array{'subsonic-response': array<string, mixed>}
     */
    public static function addArtistInfo2(array $response, array $info, Artist $artist, array $similars): array
    {
        $response['subsonic-response']['artistInfo'] = self::_getArtistInfo($artist, $info, $similars, 'artistInfo2');

        return $response;
    }

    /**
     * addArtistsID3
     *
     * A list of indexed Artists.
     * https://opensubsonic.netlify.app/docs/responses/artistsid3/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param list<array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addArtists(array $response, array $artists): array
    {
        $response['subsonic-response']['artists'] = [];

        $ignored = self::_getIgnoredArticles();
        if (!empty($ignored)) {
            $response['subsonic-response']['artists']['ignoredArticles'] = $ignored;
        }

        $response['subsonic-response']['artists']['index'] = self::_getIndex($artists);

        return $response;
    }


    /**
     * addArtistWithAlbumsID3
     *
     * An extension of ArtistID3 with AlbumID3
     * https://opensubsonic.netlify.app/docs/responses/artistwithalbumsid3/
     * @see self::addArtistID3()
     */


    /**
     * addBookmarks
     *
     * Bookmarks list.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param list<Bookmark> $bookmarks
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addBookmarks(array $response, array $bookmarks): array
    {
        $response['subsonic-response']['bookmarks'] = [];

        $json = [];
        foreach ($bookmarks as $bookmark) {
            $json[] = self::_getBookmark($bookmark);
        }

        $response['subsonic-response']['bookmarks']['bookmark'] = $json;

        return $response;
    }

    /**
    * addChatMessages
    *
    * Chat messages list.Subsonic
    * @param array{'subsonic-response': array<string, mixed>} $response
    * @param int[] $messages
    * @return array{'subsonic-response': array<string, mixed>}
    */
    public static function addChatMessages(array $response, array $messages): array
    {
        if (empty($messages)) {
            return $response;
        }

        $response['subsonic-response']['chatMessages'] = [];

        $json = [];
        foreach ($messages as $message) {
            $chat = new PrivateMsg($message);
            $user = new User($chat->getSenderUserId());
            if ($user->isNew()) {
                continue;
            }
            $json[] = self::_getChatMessage($chat, $user);
        }

        $response['subsonic-response']['chatMessages']['chatMessage'] = $json;

        return $response;
    }

    /**
     * addContributor
     *
     * A contributor artist for a song or an albumSubsonic
     */

    /**
     * addDirectory
     *
     * Directory.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addDirectory(array $response, Artist|Album|Catalog $object): array
    {
        $json = [];
        if ($object instanceof Artist) {
            $json = self::_getDirectory_Artist($object);
        } elseif ($object instanceof Album) {
            $json = self::_getDirectory_Album($object);
        } elseif ($object instanceof Catalog) {
            $json = self::_getDirectory_Catalog($object);
        }

        $response['subsonic-response']['directory'] = $json;

        return $response;
    }

    /**
     * addDiscTitle
     *
     * A disc title for an albumSubsonic
     */


    /**
     * addError
     * Add a failed subsonic-response with error information.Subsonic
     * @return array{
     *     'subsonic-response': array{
     *         'status': string,
     *         'version': string,
     *         'type': string,
     *         'serverVersion': string,
     *         'openSubsonic': bool,
     *         'error': array{
     *             'code': int,
     *             'message': string,
     *             'helpUrl': string
     *         }
     *     }
     * }
     */
    public static function addError(int $code, string $function): array
    {
        $error = self::_createFailedResponse($function);

        switch ($code) {
            case Subsonic_Api::SSERROR_MISSINGPARAM:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_MISSINGPARAM;
                $error['subsonic-response']['error']['message'] = 'Required parameter is missing.';
                break;
            case Subsonic_Api::SSERROR_APIVERSION_CLIENT:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_APIVERSION_CLIENT;
                $error['subsonic-response']['error']['message'] = 'Incompatible Subsonic REST protocol version. Client must upgrade.';
                break;
            case Subsonic_Api::SSERROR_APIVERSION_SERVER:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_APIVERSION_SERVER;
                $error['subsonic-response']['error']['message'] = 'Incompatible Subsonic REST protocol version. Server must upgrade.';
                break;
            case Subsonic_Api::SSERROR_BADAUTH:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_BADAUTH;
                $error['subsonic-response']['error']['message'] = 'Wrong username or password.';
                break;
            case Subsonic_Api::SSERROR_TOKENAUTHNOTSUPPORTED:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_TOKENAUTHNOTSUPPORTED;
                $error['subsonic-response']['error']['message'] = 'Token authentication not supported.';
                break;
            case Subsonic_Api::SSERROR_UNAUTHORIZED:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_UNAUTHORIZED;
                $error['subsonic-response']['error']['message'] = 'User is not authorized for the given operation.';
                break;
            case Subsonic_Api::SSERROR_TRIAL:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_TRIAL;
                $error['subsonic-response']['error']['message'] = 'The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details.';
                break;
            case Subsonic_Api::SSERROR_DATA_NOTFOUND:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_DATA_NOTFOUND;
                $error['subsonic-response']['error']['message'] = 'The requested data was not found.';
                break;
            case Subsonic_Api::SSERROR_AUTHMETHODNOTSUPPORTED:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_AUTHMETHODNOTSUPPORTED;
                $error['subsonic-response']['error']['message'] = 'Provided authentication mechanism not supported.';
                break;
            case Subsonic_Api::SSERROR_AUTHMETHODCONFLICT:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_AUTHMETHODCONFLICT;
                $error['subsonic-response']['error']['message'] = 'Multiple conflicting authentication mechanisms provided.';
                break;
            case Subsonic_Api::SSERROR_BADAPIKEY:
                $error['subsonic-response']['error']['code']    = Subsonic_Api::SSERROR_BADAPIKEY;
                $error['subsonic-response']['error']['message'] = 'Invalid API key.';
                break;
        }

        return $error;
    }

    /**
     * addGenres
     *
     * Genres list.
     * https://opensubsonic.netlify.app/docs/responsesq
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param list<array{id: int, name: string, is_hidden: int, count: int}> $tags
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addGenres(array $response, array $tags): array
    {
        $response['subsonic-response']['genres'] = [];

        $json = [];
        foreach ($tags as $tag) {
            $json[] = self::_getGenre($tag);
        }

        $response['subsonic-response']['genres']['genre'] = $json;

        return $response;
    }

    /**
     * addIndexes
     *
     * Artist list.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param list<array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     * @param int $lastModified
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addIndexes(array $response, array $artists, int $lastModified = 0): array
    {
        $json = [
            'index' => self::_getIndex($artists),
            'lastModified' => number_format($lastModified * 1000, 0, '.', ''),
        ];

        $ignored = self::_getIgnoredArticles();
        if (!empty($ignored)) {
            $json['ignoredArticles'] = $ignored;
        }

        $response['subsonic-response']['indexes'] = $json;

        return $response;
    }

    /**
     * addIndexID3
     *
     * An indexed artist list by ID3 tags.
     * https://opensubsonic.netlify.app/docs/responses/indexid3/
     */


    /**
     * _getInternetRadioStation
     *
     * An internetRadioStation.Subsonic
     * @return array{
     *     'id': string,
     *     'name': string,
     *     'streamUrl': string,
     *     'homepageUrl': string
     * }
     */
    private static function _getInternetRadioStation(Live_Stream $radio): array
    {
        return [
            'id' => Subsonic_Api::getLiveStreamSubId($radio->id),
            'name' => (string)$radio->name,
            'streamUrl' => (string)$radio->url,
            'homepageUrl' => (string)$radio->site_url,
        ];
    }


    /**
     * addInternetRadioStations
     *
     * internetRadioStations.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $radios
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addInternetRadioStations(array $response, array $radios): array
    {
        $response['subsonic-response']['internetRadioStations'] = [];

        $json = [];
        foreach ($radios as $radio_id) {
            $radio  = new Live_Stream($radio_id);
            $json[] = self::_getInternetRadioStation($radio);
        }

        $response['subsonic-response']['internetRadioStations']['internetRadioStation'] = $json;

        return $response;
    }

    /**
     * addItemDate
     *
     * A date for a media item that may be just a year, or year-month, or full date.Subsonic
     */


    /**
     * addItemGenre
     *
     * A genre returned in list of genres for an item.Subsonic
     * @see self::_getChild()
     * @see self::addAlbumID3()
     *
     */



    /**
     * addJukeboxPlaylistSubsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addJukeboxPlaylist(array $response, LocalPlay $localplay): array
    {
        $tracks = $localplay->get();
        $status = self::_getJukeboxStatus($localplay);

        $status['entry'] = [];
        foreach ($tracks as $track) {
            if (array_key_exists('oid', $track)) {
                $song = new Song((int)$track['oid']);
                if ($song->isNew()) {
                    continue;
                }
                $status['entry'][] = self::_getChildSong($song);
            }
            // TODO This can be random play, democratic, podcasts, etc. not just songs
        }

        $response['subsonic-response']['jukeboxPlaylist'] = $status;

        return $response;
    }

    /**
     * addJukeboxStatusSubsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addJukeboxStatus(array $response, LocalPlay $localplay): array
    {
        $status = self::_getJukeboxStatus($localplay);

        $response['subsonic-response']['jukeboxstatus'] = $status;

        return $response;
    }


    /**
     * addLicense
     *
     * getLicense result.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addLicense(array $response): array
    {
        $response['subsonic-response']['license'] = [
            'valid' => true,
            'email' => 'webmaster@ampache.org'
        ];

        return $response;
    }


    /**
     * addLine
     *
     * One line of a song lyricSubsonic
     * @see self::addLyricsList())
     */


    /**
     * addLyrics
     *
     * Lyrics.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addLyrics(array $response, string $artist, string $title, Song $song): array
    {
        if ($song->isNew()) {
            return $response;
        }

        $lyrics = $song->get_lyrics();

        if (!empty($lyrics) && $lyrics['text'] && is_string($lyrics['text'])) {
            $text = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $lyrics['text']);
            $text = preg_replace('/\\n\\n/i', "\n", (string)$text);
            $text = str_replace("\r", '', (string)$text);

            $json = [];
            if ($artist) {
                $json['artist'] = (string)$artist;
            }

            if ($title) {
                $json['title'] = (string)$title;
            }

            $json['value'] = htmlspecialchars($text);

            $response['subsonic-response']['lyrics'] = $json;
        }

        return $response;
    }


    /**
     * addLyricsList
     *
     * List of structured lyricsSubsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addLyricsList(array $response, Song $song): array
    {
        if ($song->isNew()) {
            return $response;
        }

        $response['subsonic-response']['lyricsList'] = [];

        $json = self::_getStructuredLyrics($song);
        if (!empty($json)) {
            $response['subsonic-response']['lyricsList'][] = ['structuredLyrics' => $json];
        }


        return $response;
    }

    /**
     * addMusicFolder
     *
     * MusicFolder.Subsonic
     * @see self::addMusicFolders()
     */


    /**
     * addMusicFolders
     *
     * MusicFolders.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $catalogs
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addMusicFolders(array $response, array $catalogs): array
    {
        $response['subsonic-response']['musicFolders'] = [];

        $json = [];
        foreach ($catalogs as $folder_id) {
            $catalog = Catalog::create_from_id($folder_id);
            if ($catalog instanceof Catalog) {
                $json[] = [
                    'id' => Subsonic_Api::getCatalogSubId($folder_id),
                    'name' => (string)$catalog->name,
                ];
            }

        }

        $response['subsonic-response']['musicFolders']['musicFolder'] = $json;

        return $response;
    }

    /**
     * addNewestPodcasts
     *
     * NewestPodcasts.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param Podcast_Episode[] $episodes
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addNewestPodcasts(array $response, array $episodes): array
    {
        $response['subsonic-response']['newestPodcasts'] = [];

        $json = [];
        foreach ($episodes as $episode) {
            $json[] = self::_getPodcastEpisode($episode);
        }

        $response['subsonic-response']['newestPodcasts']['episode'] = $json;

        return $response;
    }

    /**
     * addNowPlaying
     *
     * nowPlaying.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param list<array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int
     * }> $data
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addNowPlaying(array $response, array $data): array
    {
        $response['subsonic-response']['nowPlaying'] = [];

        $json = [];
        foreach ($data as $row) {
            if (
                $row['media'] instanceof Song &&
                !$row['media']->isNew() &&
                $row['media']->enabled
            ) {
                $track               = self::_getChildSong($row['media']);
                $track['username']   = (string)$row['client']->username;
                $track['minutesAgo'] = (string)(abs((time() - ($row['expire'] - $row['media']->time)) / 60));
                $track['playerId']   = 0;
                $track['playerName'] = (string)$row['agent'];
            }
        }

        $response['subsonic-response']['nowPlaying']['entry'] = $json;

        return $response;
    }

    /**
     * addNowPlayingEntry
     *
     * NowPlayingEntry.Subsonic
     * @see self::_getChild()
     */

    /**
     * addSubsonicExtension
     *
     * A supported Subsonic API extension.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param array<string, int[]> $extensions
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addSubsonicExtensions(array $response, array $extensions): array
    {
        $json = [];
        // https://opensubsonic.netlify.app/docs/responses/opensubsonicextension/
        foreach ($extensions as $name => $versions) {
            $json[] = [
                'name' => $name,
                'versions' => $versions,
            ];
        }

        $response['subsonic-response']['openSubsonicExtensions'] = $json;

        return $response;
    }

    /**
     * addPlaylist
     *
     * Playlist or playlist with songsSubsonicSubsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addPlaylist(array $response, Playlist|Search $playlist, bool $songs = false): array
    {
        $json = [];
        if ($playlist instanceof Playlist) {
            $json = self::_getPlaylist_Playlist($playlist, $songs);
        }
        if ($playlist instanceof Search) {
            $json = self::_getPlaylist_Search($playlist, $songs);
        }

        $response['subsonic-response']['playlist'] = $json;

        return $response;
    }

    /**
     * addPlaylists
     *
     * Playlists.
     * return playlists object with nested playlist itemsSubsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[]|string[] $playlists
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addPlaylists(array $response, ?User $user, array $playlists): array
    {
        $response['subsonic-response']['playlists']['playlist'] = [];
        foreach ($playlists as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist  = 1000000
             */
            if ((int)$playlist_id === 0) {
                $playlist = new Search((int)str_replace('smart_', '', (string)$playlist_id), 'song', $user);
                if ($playlist->isNew()) {
                    continue;
                }
                $response['subsonic-response']['playlists']['playlist'][] = self::_getPlaylist_Search($playlist);
            } else {
                $playlist = new Playlist((int)$playlist_id);
                if ($playlist->isNew()) {
                    continue;
                }
                $response['subsonic-response']['playlists']['playlist'][] = self::_getPlaylist_Playlist($playlist);
            }
        }

        return $response;
    }

    /**
     * addPlaylistWithSongs
     *
     * Playlist with songs.Subsonic
     * @see self::addPlaylist()
     */

    /**
     * addPlayQueue
     *
     * NowPlayingEntry.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addPlayQueue(array $response, User_Playlist $playQueue, string $username): array
    {
        $items = $playQueue->get_items();
        if (!empty($items)) {
            $current   = $playQueue->get_current_object();
            $play_time = date("Y-m-d H:i:s", $playQueue->get_time());
            try {
                $date = new DateTime($play_time);
            } catch (Exception $error) {
                debug_event(self::class, 'DateTime error: ' . $error->getMessage(), 5);

                return $response;
            }

            $date->setTimezone(new DateTimeZone('UTC'));
            $changedBy = $playQueue->client ?? '';

            $json = ($current !== [])
                ? [
                    'current' => Subsonic_Api::getSongSubId($current['object_id']),
                    'position' => (string)($current['current_time'] * 1000),
                    'username' => $username,
                    'changed' => $date->format('c'),
                    'changedBy' => $changedBy,
                    'entry' => [],
                ]
                : [];

            foreach ($items as $row) {
                $song = new Song((int)$row['object_id']);
                if ($song->isNew()) {
                    continue;
                }
                $json['entry'][] = self::_getChildSong($song);
            }

            $response['subsonic-response']['playQueue'] = $json;
        }


        return $response;
    }

    /**
     * addPlayQueueByIndex
     *
     * NowPlayingEntry.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addPlayQueueByIndex(array $response, User_Playlist $playQueue, string $username): array
    {
        $items = $playQueue->get_items();
        if (!empty($items)) {
            $current   = $playQueue->get_current_object();
            $play_time = date("Y-m-d H:i:s", $playQueue->get_time());
            try {
                $date = new DateTime($play_time);
            } catch (Exception $error) {
                debug_event(self::class, 'DateTime error: ' . $error->getMessage(), 5);

                return $response;
            }

            $date->setTimezone(new DateTimeZone('UTC'));
            $changedBy = $playQueue->client ?? '';

            $json = ($current !== [])
                ? [
                    'currentIndex' => Subsonic_Api::getSongSubId($current['object_id']),
                    'position' => (string)($current['current_time'] * 1000),
                    'username' => $username,
                    'changed' => $date->format('c'),
                    'changedBy' => $changedBy,
                    'entry' => [],
                ]
                : [];

            foreach ($items as $row) {
                $song = new Song((int)$row['object_id']);
                if ($song->isNew()) {
                    continue;
                }
                $json['entry'][] = self::_getChildSong($song);
            }

            $response['subsonic-response']['playQueue'] = $json;
        }


        return $response;
    }


    /**
     * addPodcastChannel
     *
     * A Podcast channelSubsonic
     * @see self::addPodcasts()
     */


    /**
     * addPodcastEpside
     *
     * Podcasts.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param Podcast_Episode $episode
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addPodcastEpisode(array $response, Podcast_Episode $episode): array
    {
        $response['subsonic-response']['podcastEpisode'] = self::_getPodcastEpisode($episode);


        return $response;
    }

    /**
     * addPodcasts
     *
     * Podcasts.
     *  https://opensubsonic.netlify.app/docs/responses/podcasts/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param Podcast[] $podcasts
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addPodcasts(array $response, array $podcasts, bool $includeEpisodes = true): array
    {
        $response['subsonic-response']['podcasts'] = [];

        $json = [];
        foreach ($podcasts as $podcast) {
            $json[] = self::_getPodcast($podcast, $includeEpisodes);
        }

        $response['subsonic-response']['podcasts']['channel'] = $json;

        return $response;
    }

    /**
     * addPodcastStatus
     *
     * An enumeration of possible podcast statusesSubsonic
     * @see self::addPodcasts()
     * new
     * downloading
     * completed
     * error
     * deleted
     * skipped
     */


    /**
     * addRandomSongsSubsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $songs
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addRandomSongs(array $response, array $songs): array
    {
        $response['subsonic-response']['randomSongs'] = [];

        $json = [];
        foreach ($songs as $song_id) {
            $song   = new Song($song_id);
            $json[] = self::_getChildSong($song);
        }

        $response['subsonic-response']['randomSongs']['song'] = $json;

        return $response;
    }

    /**
     * addRecordLabel
     *
     * A record label for an album.Subsonic
     * @see self::addAlbumID3()
     */


    /**
     * addReplayGain
     *
     * The replay gain data of a song.Subsonic
     * @see self::_getChild()
     */


    /**
     * addScanStatus
     *
     * Scan status information.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addScanStatus(array $response, User $user): array
    {
        $counts = Catalog::get_server_counts($user->id ?? 0);
        $count  = $counts['artist'] + $counts['album'] + $counts['song'] + $counts['podcast_episode'];

        $response['subsonic-response']['scanStatus'] = [
            'scanning' => false,
            'count' => $count,
        ];

        return $response;
    }

    /**
     * addSearchResult
     *
     * searchResult.
     * https://opensubsonic.netlify.app/docs/responses/searchresult/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $songs
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addSearchResult(array $response, array $songs, int $offset, int $total): array
    {
        $response['subsonic-response']['searchResult'] = [
            'offset' => $offset,
            'totalHits' => $total,
        ];

        $json = [];

        if (!empty($songs)) {
            $json = [];
            foreach ($songs as $song_id) {
                $json[] = self::_getChild($song_id, 'song');
            }
        }

        $response['subsonic-response']['searchResult']['match'] = $json;
    
        return $response;
    }

    /**
     * addSearchResult2
     *
     * searchResult2.
     * https://opensubsonic.netlify.app/docs/responses/searchresult2/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addSearchResult2(array $response, array $artists, array $albums, array $songs): array
    {
        $json = [];

        if (!empty($artists)) {
            $json['artist'] = [];
            foreach ($artists as $artist_id) {
                $artist           = new Artist($artist_id);
                $json['artist'][] = self::_getArtist($artist);
            }
        }
        if (!empty($albums)) {
            $json['album'] = [];
            foreach ($albums as $album_id) {
                $json['album'][] = self::_getChild($album_id, 'album');
            }
        }
        if (!empty($songs)) {
            $json['song'] = [];
            foreach ($songs as $song_id) {
                $json['song'][] = self::_getChild($song_id, 'song');
            }
        }

        $response['subsonic-response']['searchResult2'] = $json;

        return $response;
    }

    /**
     * addSearchResult3
     *
     * search3 result.
     * https://opensubsonic.netlify.app/docs/responses/searchresult3/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addSearchResult3(array $response, array $artists, array $albums, array $songs): array
    {
        $json = [];

        if (!empty($artists)) {
            $output_artists = [];
            foreach ($artists as $artist_id) {
                $artist = new Artist($artist_id);
                if ($artist->isNew()) {
                    continue;
                }
                $output_artists[] = self::_getArtistID3($artist);
            }
            $json['artist'] = $output_artists;
        }
        if (!empty($albums)) {
            $output_albums = [];
            foreach ($albums as $album_id) {
                $album = new Album($album_id);
                if ($album->isNew()) {
                    continue;
                }
                $output_albums[] = self::_getAlbumID3($album);
            }
            $json['album'] = $output_albums;
        }
        if (!empty($songs)) {
            $json['song'] = [];
            foreach ($songs as $song_id) {
                $json['song'][] = self::_getChild($song_id, 'song');
            }
        }

        $response['subsonic-response']['searchResult3'] = $json;

        return $response;
    }

    /**
     * _getShare
     *
     * Share.
     * @return array{
     *     'id': string,
     *     'url': string,
     *     'description': string,
     *     'username': string,
     *     'created': string,
     *     'lastVisited'?: string,
     *     'expires'?: string,
     *     'visitCount': int,
     *     'object_id'?: int|string,
     *     'object_type'?: string,
     *     'entry'?: list<array<string, mixed>>
     * }
     */
    private static function _getShare(Share $share, User $user): array
    {
        $json = [
            'id' => Subsonic_Api::getShareSubId($share->id),
            'url' => (string)$share->public_url,
            'description' => (string)$share->description,
            'username' => (string)(string)$user->username,
            'created' => date('c', (int)$share->creation_date),
        ];

        if ($share->lastvisit_date > 0) {
            $json['lastVisited'] = date('c', $share->lastvisit_date);
        }

        if ($share->expire_days > 0) {
            $json['expires'] = date('c', (int)$share->creation_date + ($share->expire_days * 86400));
        }

        $json['visitCount'] = $share->counter;

        $json['entry'] = [];
        if ($share->object_type == 'song') {
            $json['entry'][] = self::_getChild($share->object_id, 'song');
        } elseif ($share->object_type == 'playlist') {
            $playlist      = new Playlist($share->object_id);
            $songs         = $playlist->get_songs();
            foreach ($songs as $song_id) {
                $json['entry'][] = self::_getChild($song_id, 'song');
            }
        } elseif ($share->object_type == 'album') {
            $songs = self::getSongRepository()->getByAlbum($share->object_id);
            foreach ($songs as $song_id) {
                $json['entry'][] = self::_getChild($song_id, 'song');
            }
        }

        return $json;
    }

    /**
     * addStructuredLyrics
     *
     * Structured lyricsSubsonic
     * @return array{
     *     'displayArtist'?: string,
     *     'displayTitle'?: string,
     *     'lang'?: string,
     *     'synced'?: bool,
     *     'line'?: array<array{'value': string}>
     * }
     */
    private static function _getStructuredLyrics(Song $song): array
    {
        $lyrics = $song->get_lyrics();

        if (!empty($lyrics) && $lyrics['text'] && is_string($lyrics['text'])) {
            $text = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $lyrics['text']);
            $text = preg_replace('/\\n\\n/i', "\n", (string)$text);
            $text = str_replace("\r", '', (string)$text);

            $json = [
                'displayArtist' => (string)$song->get_artist_fullname(),
                'displayTitle' => (string)$song->title,
                'lang' => 'xxx',
                'synced' => false,
                'line' => [],
            ];

            foreach (explode("\n", htmlspecialchars($text)) as $line) {
                if (!empty($line)) {
                    $json['line'][] = ['value' => (string)$line];
                }
            }

            return $json;
        }

        return [];
    }

    /**
     * _getUser
     *
     * user.
     * @return array{
     *     'username': string,
     *     'email': string,
     *     'scrobblingEnabled': bool,
     *     'adminRole': bool,
     *     'settingsRole': bool,
     *     'downloadRole': bool,
     *     'playlistRole': bool,
     *     'coverArtRole': bool,
     *     'commentRole': bool,
     *     'podcastRole': bool,
     *     'streamRole': bool,
     *     'jukeboxRole': bool,
     *     'shareRole': bool,
     *     'videoConversionRole': bool
     * }
     */
    private static function _getUser(User $user): array
    {
        $isManager = ($user->access >= 75);
        $isAdmin   = ($user->access === 100);

        return [
            'username' => (string)$user->username,
            'email' => (string)$user->email,
            'scrobblingEnabled' => true,
            'adminRole' => $isAdmin,
            'settingsRole' => true,
            'downloadRole' => (bool)Preference::get_by_user($user->id, 'download'),
            'playlistRole' => true,
            'coverArtRole' => $isManager,
            'commentRole' => (bool)AmpConfig::get('social'),
            'podcastRole' => (bool)AmpConfig::get('podcast'),
            'streamRole' => true,
            'jukeboxRole' => (AmpConfig::get('allow_localplay_playback') && AmpConfig::get('localplay_controller') && Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::GUEST)),
            'shareRole' => (bool)Preference::get_by_user($user->id, 'share'),
            'videoConversionRole' => false,
        ];
    }

    /**
     * addShares
     *
     * Shares.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param list<int> $shares
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addShares(array $response, array $shares): array
    {
        $response['subsonic-response']['shares'] = [];

        $json = [];
        foreach ($shares as $share_id) {
            $share = new Share($share_id);
            // Don't add share with max counter already reached
            if ($share->max_counter === 0 || $share->counter < $share->max_counter) {
                $user = new User($share->user);
                if ($user->isNew()) {
                    continue;
                }

                $json[] = self::_getShare($share, $user);
            }
        }

        $response['subsonic-response']['shares']['share'] = $json;

        return $response;
    }


    /**
     * addSimilarSongs
     *
     * SimilarSongs list.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param list<array{
     *     id: ?int,
     *     name?: ?string,
     *     rel?: ?string,
     *     mbid?: ?string,
     * }> $similar_songs
     *@return array{'subsonic-response': array<string, mixed>}
     */
    public static function addSimilarSongs(array $response, array $similar_songs): array
    {
        $response['subsonic-response']['similarSongs'] = [];

        $json = [];
        foreach ($similar_songs as $similar_song) {
            if ($similar_song['id'] !== null) {
                $song   = new Song($similar_song['id']);
                $json[] = self::_getChildSong($song);
            }
        }

        $response['subsonic-response']['similarSongs2']['song'] = $json;

        return $response;
    }

    /**
     * addSimilarSongs2
     *
     * SimilarSongs2 list.
     * https://opensubsonic.netlify.app/docs/responses/similarsongs2/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param list<array{
     *     id: ?int,
     *     name?: ?string,
     *     rel?: ?string,
     *     mbid?: ?string,
     * }> $similar_songs
     *@return array{'subsonic-response': array<string, mixed>}
     */
    public static function addSimilarSongs2(array $response, array $similar_songs): array
    {
        $response['subsonic-response']['similarSongs2'] = [];

        $json = [];
        foreach ($similar_songs as $similar_song) {
            if ($similar_song['id'] !== null) {
                $song   = new Song($similar_song['id']);
                $json[] = self::_getChildSong($song);
            }
        }

        $response['subsonic-response']['similarSongs2']['song'] = $json;

        return $response;
    }

    /**
     * addSong
     *
     * song.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int $song_id
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addSong(array $response, int $song_id): array
    {
        $response['subsonic-response']['song'] = self::_getChild($song_id, 'song');

        return $response;
    }

    /**
     * addSongs
     *
     * Songs list.Subsonic
     * @see self::_getChildSong()
     */


    /**
     * addSongsByGenreSubsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $songs
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addSongsByGenre(array $response, array $songs): array
    {
        $response['subsonic-response']['songsByGenre'] = [];

        $json = [];
        foreach ($songs as $song_id) {
            $song   = new Song($song_id);
            if ($song->isNew()) {
                continue;
            }
            $json[] = self::_getChildSong($song);
        }

        $response['subsonic-response']['songsByGenre']['song'] = $json;

        return $response;
    }

    /**
     * addStarred
     *
     * starred.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     *@return array{'subsonic-response': array<string, mixed>}
     */
    public static function addStarred(array $response, array $artists, array $albums, array $songs): array
    {
        $json = [
            'artist' => [],
            'album' => [],
            'song' => [],
        ];

        foreach ($artists as $artist_id) {
            $artist           = new Artist($artist_id);
            $json['artist'][] = self::_getArtist($artist);
        }

        foreach ($albums as $album_id) {
            $album           = new Album($album_id);
            $json['album'][] = self::_getChildAlbum($album);
        }

        foreach ($songs as $song_id) {
            $song           = new Song($song_id);
            $json['song'][] = self::_getChildSong($song);
        }

        $response['subsonic-response']['starred'] = $json;

        return $response;
    }

    /**
     * addStarred2
     *
     * starred2.
     * https://opensubsonic.netlify.app/docs/responses/starred2/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     *@return array{'subsonic-response': array<string, mixed>}
     */
    public static function addStarred2(array $response, array $artists, array $albums, array $songs): array
    {
        $json = [
            'artist' => [],
            'album' => [],
            'song' => [],
        ];

        foreach ($artists as $artist_id) {
            $artist           = new Artist($artist_id);
            $json['artist'][] = self::_getArtistID3($artist);
        }

        foreach ($albums as $album_id) {
            $album           = new Album($album_id);
            $json['album'][] = self::_getAlbumID3($album);
        }

        foreach ($songs as $song_id) {
            $song           = new Song($song_id);
            $json['song'][] = self::_getChildSong($song);
        }

        $response['subsonic-response']['starred2'] = $json;

        return $response;
    }

    /**
     * addTokenInfo
     *
     *  Information about an API keySubsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addTokenInfo(array $response, User $user): array
    {
        $response['subsonic-response']['tokenInfo'] = [
            'username' => $user->username
        ];

        return $response;
    }

    /**
     * addTopSongs
     *
     * TopSongs list.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $songs
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addTopSongs(array $response, array $songs): array
    {
        $response['subsonic-response']['topSongs'] = [];

        $json = [];
        foreach ($songs as $song_id) {
            $song   = new Song($song_id);
            $json[] = self::_getChildSong($song);
        }

        $response['subsonic-response']['topSongs']['song'] = $json;

        return $response;
    }

    /**
     * addUser
     *
     * user.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addUser(array $response, User $user): array
    {
        $response['subsonic-response']['user'] = self::_getUser($user);

        return $response;
    }


    /**
     * addUsers
     *
     * users.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param int[] $users
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addUsers(array $response, array $users): array
    {
        $response['subsonic-response']['users'] = [];

        $json = [];
        foreach ($users as $user_id) {
            $user = new User($user_id);
            if ($user->isNew() === false) {
                $json[] = self::_getUser($user);
            }
        }

        $response['subsonic-response']['users']['user'] = $json;

        return $response;
    }

    /**
     * addVideoInfo
     *
     * videoInfo.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addVideoInfo(array $response, int $video_id): array
    {
        $response['subsonic-response']['videoInfo'] = [
            'id' => Subsonic_Api::getVideoSubId($video_id)
        ];

        return $response;
    }


    /**
     * addVideos
     *
     * videos.Subsonic
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param Video[] $videos
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addVideos(array $response, array $videos): array
    {
        $response['subsonic-response']['videos'] = [];

        $json = [];
        foreach ($videos as $video) {
            $json[] = self::_getChildVideo($video);
        }

        $response['subsonic-response']['videos']['video'] = $json;

        return $response;
    }

    /**
     * @deprecated
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
