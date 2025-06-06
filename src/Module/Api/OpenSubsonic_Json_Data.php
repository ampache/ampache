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
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

/**
 * OpenSubsonic_Json_Data Class
 *
 * This class takes care of all of the xml document stuff for SubSonic Responses
 */
class OpenSubsonic_Json_Data
{
    /**
     * _createResponse
     * @return array{'subsonic-response': array{'status': string, 'version': string, 'type': string, 'serverVersion': string, 'openSubsonic': bool}}
     */
    private static function _createResponse(): array
    {

        return [
            'subsonic-response' => [
                'status' => 'ok',
                'version' => OpenSubsonic_Api::API_VERSION,
                'type' => 'ampache',
                'serverVersion' => Api::$version,
                'openSubsonic' => true,
            ]
        ];
    }

    /**
     * _createSuccessResponse
     * @return array{'subsonic-response': array{'status': string, 'version': string, 'type': string, 'serverVersion': string, 'openSubsonic': bool}}
     */
    private static function _createSuccessResponse(string $function = ''): array
    {
        debug_event(self::class, 'API success in function ' . $function . '-' . OpenSubsonic_Api::API_VERSION, 5);

        return self::_createResponse();
    }

    /**
     * _createFailedResponse
     * @return array{'subsonic-response': array{'status': string, 'version': string, 'type': string, 'serverVersion': string, 'openSubsonic': bool, 'error': array{'code': int, 'message': string}}}
     */
    private static function _createFailedResponse(string $function = ''): array
    {
        debug_event(self::class, 'API success in function ' . $function . '-' . OpenSubsonic_Api::API_VERSION, 5);

        return [
            'subsonic-response' => [
                'status' => 'failed',
                'version' => OpenSubsonic_Api::API_VERSION,
                'type' => 'ampache',
                'serverVersion' => Api::$version,
                'openSubsonic' => true,
                'error' => [
                    'code' => OpenSubsonic_Api::SSERROR_GENERIC,
                    'message' => "Error creating response."
                ]
            ]
        ];
    }

    /**
     * addPlaylist_Playlist
     * @return array<string, mixed>
     */
    private static function _addPlaylist_Playlist(Playlist $playlist, bool $songs = false): array
    {
        $sub_id    = OpenSubsonic_Api::_getPlaylistId($playlist->id);
        $songcount = $playlist->get_media_count('song');
        $duration  = ($songcount > 0) ? $playlist->get_total_duration() : 0;

        $JSON = [
            'id' => $sub_id,
            'name' => (string)$playlist->get_fullname(),
            'owner' => (string)$playlist->username,
            'public' => ($playlist->type != "private") ? "true" : "false",
            'created' => date("c", (int)$playlist->date),
            'changed' => date("c", (int)$playlist->last_update),
            'songCount' => (string)$songcount,
            'duration' => (string)$duration,
        ];

        if ($playlist->has_art()) {
            $JSON['coverArt'] = $sub_id;
        }

        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $song_id) {
                self::addChild($JSON, $song_id, 'entry');
            }
        }

        return $JSON;
    }

    /**
     * addPlaylist_Search
     * @return array<string, mixed>
     */
    private static function _addPlaylist_Search(Search $search, bool $songs = false): array
    {
        $sub_id = OpenSubsonic_Api::_getSmartPlaylistId($search->id);

        $JSON = [
            'id' => $sub_id,
            'name' => (string)$search->get_fullname(),
            'owner' => (string)$search->username,
            'public' => ($search->type != "private") ? "true" : "false",
            'created' => date("c", (int)$search->date),
            'changed' => date("c", time()),
        ];

        if ($songs) {
            $allitems = $search->get_items();
            $duration = (count($allitems) > 0)
                ? Search::get_total_duration($allitems)
                : 0;

            $JSON['songCount'] = (string)count($allitems);
            $JSON['duration']  = (string)$duration;
            $JSON['coverArt']  = $sub_id;
            foreach ($allitems as $item) {
                self::addChild($JSON, (int)$item['object_id'], 'entry');
            }
        } else {
            $JSON['songCount'] = (string)$search->last_count;
            $JSON['duration']  = (string)$search->last_duration;
            $JSON['coverArt']  = $sub_id;
        }

        return $JSON;
    }

    /**
     * _getAmpacheIdArrays
     * @param string[]|int[] $object_ids
     * @return list<array{
     *     object_id: int|null,
     *     object_type: string,
     *     track: int
     * }>
     */
    public static function _getAmpacheIdArrays(array $object_ids): array
    {
        $ampidarrays = [];
        $track       = 1;
        foreach ($object_ids as $object_id) {
            $ampacheId = OpenSubsonic_Api::_getAmpacheId((string)$object_id);
            if ($ampacheId) {
                $ampidarrays[] = [
                    'object_id' => $ampacheId,
                    'object_type' => OpenSubsonic_Api::_getAmpacheType((string)$object_id),
                    'track' => $track
                ];
                $track++;
            }
        }

        return $ampidarrays;
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
     * addError
     * Add a failed subsonic-response with error information.
     * @return array{'subsonic-response': array{'status': string, 'version': string, 'type': string, 'serverVersion': string, 'openSubsonic': bool, 'error': array{'code': int, 'message': string}}}
     */
    public static function addError(int $code, string $function): array
    {
        $error = self::_createFailedResponse($function);

        switch ($code) {
            case OpenSubsonic_Api::SSERROR_MISSINGPARAM:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_MISSINGPARAM;
                $error['subsonic-response']['error']['message'] = "Required parameter is missing.";
                break;
            case OpenSubsonic_Api::SSERROR_APIVERSION_CLIENT:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_APIVERSION_CLIENT;
                $error['subsonic-response']['error']['message'] = "Incompatible Subsonic REST protocol version. Client must upgrade.";
                break;
            case OpenSubsonic_Api::SSERROR_APIVERSION_SERVER:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_APIVERSION_SERVER;
                $error['subsonic-response']['error']['message'] = "Incompatible Subsonic REST protocol version. Server must upgrade.";
                break;
            case OpenSubsonic_Api::SSERROR_BADAUTH:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_BADAUTH;
                $error['subsonic-response']['error']['message'] = "Wrong username or password.";
                break;
            case OpenSubsonic_Api::SSERROR_TOKENAUTHNOTSUPPORTED:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_TOKENAUTHNOTSUPPORTED;
                $error['subsonic-response']['error']['message'] = "Token authentication not supported.";
                break;
            case OpenSubsonic_Api::SSERROR_UNAUTHORIZED:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_UNAUTHORIZED;
                $error['subsonic-response']['error']['message'] = "User is not authorized for the given operation.";
                break;
            case OpenSubsonic_Api::SSERROR_TRIAL:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_TRIAL;
                $error['subsonic-response']['error']['message'] = "The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details.";
                break;
            case OpenSubsonic_Api::SSERROR_DATA_NOTFOUND:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_DATA_NOTFOUND;
                $error['subsonic-response']['error']['message'] = "The requested data was not found.";
                break;
            case OpenSubsonic_Api::SSERROR_AUTHMETHODNOTSUPPORTED:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_AUTHMETHODNOTSUPPORTED;
                $error['subsonic-response']['error']['message'] = "Provided authentication mechanism not supported.";
                break;
            case OpenSubsonic_Api::SSERROR_AUTHMETHODCONFLICT:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_AUTHMETHODCONFLICT;
                $error['subsonic-response']['error']['message'] = "Multiple conflicting authentication mechanisms provided.";
                break;
            case OpenSubsonic_Api::SSERROR_BADAPIKEY:
                $error['subsonic-response']['error']['code']    = OpenSubsonic_Api::SSERROR_BADAPIKEY;
                $error['subsonic-response']['error']['message'] = "Invalid API key.";
                break;
        }

        return $error;
    }

    /**
     * addAlbumID3
     *
     * An album from ID3 tags.
     */


    /**
     * addAlbumID3WithSongs
     *
     * Album with songs.
     */


    /**
     * addAlbumInfo
     *
     * Album info.
     */


    /**
     * addAlbumList
     *
     * Album list.
     */


    /**
     * addAlbumList2
     *
     * Album list.
     */


    /**
     * addArtist
     *
     * Artist details.
     */


    /**
     * addArtistID3
     *
     * An artist from ID3 tags.
     */


    /**
     * addArtistInfo
     *
     * Artist info.
     */


    /**
     * addArtistInfo2
     *
     * Artist info.
     */


    /**
     * addArtistsID3
     *
     * A list of indexed Artists.
     */


    /**
     * addArtistWithAlbumsID3
     *
     * An extension of ArtistID3 with AlbumID3
     */


    /**
     * addBookmark
     *
     * A bookmark.
     */


    /**
     * addBookmarks
     *
     * Bookmarks list.
     */


    /**
     * addChatMessage
     *
     * A chatMessage.
     */


    /**
     * addChatMessages
     *
     * Chat messages list.
     */


    /**
     * addChild
     *
     * A media.
     * https://opensubsonic.netlify.app/docs/responses/child/
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    public static function addChild(array $response, int $object_id, string $elementName): array
    {

        $song = new Song($object_id);
        if ($song->isNew()) {
            return $response;
        }

        // Don't create entries for disabled songs
        if ($song->enabled) {
            $sub_id    = OpenSubsonic_Api::_getSongId($song->id);
            $subParent = OpenSubsonic_Api::_getAlbumId($song->album);

            // set the elementName if missing
            if (!isset($response[$elementName])) {
                $response[$elementName] = [];
            }

            $child = [
                'id' => $sub_id,
                'parent' => $subParent,
                'title' => (string)$song->title,
                'isDir' => 'false',
                'isVideo' => 'false',
                'type' => 'music',
                'albumId' => $subParent,
                'album' => (string)$song->get_album_fullname(),
                'artistId' => ($song->artist) ? OpenSubsonic_Api::_getArtistId($song->artist) : '',
                'artist' => (string)$song->get_artist_fullname(),
            ];

            if ($song->has_art()) {
                $art_id            = (AmpConfig::get('show_song_art', false)) ? $sub_id : $subParent;
                $child['coverArt'] = $art_id;
            }

            $child['duration'] = (string)$song->time;
            $child['bitrate']  = (string)((int)($song->bitrate / 1024));

            $rating      = new Rating($song->id, "song");
            $user_rating = ($rating->get_user_rating() ?? 0);
            if ($user_rating > 0) {
                $child['userRating'] = (string)ceil($user_rating);
            }

            $avg_rating = $rating->get_average_rating();
            if ($avg_rating > 0) {
                $child['averageRating'] = (string)$avg_rating;
            }

            $starred = new Userflag($object_id, 'song');
            $result  = $starred->get_flag(null, true);
            if (is_array($result)) {
                $child['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
            }

            if ($song->track > 0) {
                $child['track'] = (string)$song->track;
            }

            if ($song->year > 0) {
                $child['year'] = (string)$song->year;
            }

            $tags = Tag::get_object_tags('song', $song->id);
            if (!empty($tags)) {
                $child['genre'] = implode(',', array_column($tags, 'name'));
            }

            $child['size'] = (string)$song->size;

            $disk = $song->disk;
            if ($disk > 0) {
                $child['discNumber'] = (string)$disk;
            }

            $child['suffix']      = $song->type;
            $child['contentType'] = (string)$song->mime;
            // Always return the original filename, not the transcoded one
            $child['path'] = (string)$song->file;
            if (AmpConfig::get('transcode', 'default') != 'never') {
                $cache_path     = (string)AmpConfig::get('cache_path', '');
                $cache_target   = (string)AmpConfig::get('cache_target', '');
                $file_target    = Catalog::get_cache_path($song->getId(), $song->getCatalogId(), $cache_path, $cache_target);
                $transcode_type = ($file_target !== null && is_file($file_target))
                    ? $cache_target
                    : Stream::get_transcode_format($song->type, null, 'api');

                if (!empty($transcode_type) && $song->type !== $transcode_type) {
                    // Set transcoding information
                    $child['transcodedSuffix']      = $transcode_type;
                    $child['transcodedContentType'] = Song::type_to_mime($transcode_type);
                }
            }

            $response[$elementName][] = $child;

            return $response;
        }

        return $response;
    }


    /**
     * addContributor
     *
     * A contributor artist for a song or an album
     */


    /**
     * addDirectory
     *
     * Directory.
     */


    /**
     * addDiscTitle
     *
     * A disc title for an album
     */


    /**
     * addError
     *
     * Error.
     */


    /**
     * addGenre
     *
     * A genre.
     */


    /**
     * addGenres
     *
     * Genres list.
     */


    /**
     * addIndex
     *
     * An indexed artist list.
     */


    /**
     * addIndexes
     *
     * Artist list.
     */


    /**
     * addIndexID3
     *
     * An indexed artist list by ID3 tags.
     */


    /**
     * addInternetRadioStation
     *
     * An internetRadioStation.
     */


    /**
     * addInternetRadioStations
     *
     * internetRadioStations.
     */


    /**
     * addItemDate
     *
     * A date for a media item that may be just a year, or year-month, or full date.
     */


    /**
     * addItemGenre
     *
     * A genre returned in list of genres for an item.
     */


    /**
     * addJukeboxPlaylist
     *
     * jukeboxPlaylist.
     */


    /**
     * addJukeboxStatus
     *
     * jukeboxStatus.
     */


    /**
     * addLicense
     *
     * getLicense result.
     */


    /**
     * addLine
     *
     * One line of a song lyric
     */


    /**
     * addLyrics
     *
     * Lyrics.
     */


    /**
     * addLyricsList
     *
     * List of structured lyrics
     */


    /**
     * addMusicFolder
     *
     * MusicFolder.
     */


    /**
     * addMusicFolders
     *
     * MusicFolders.
     */


    /**
     * addNewestPodcasts
     *
     * NewestPodcasts.
     */


    /**
     * addNowPlaying
     *
     * nowPlaying.
     */


    /**
     * addNowPlayingEntry
     *
     * NowPlayingEntry.
     */


    /**
     * addOpenSubsonicExtension
     *
     * A supported OpenSubsonic API extension.
     */


    /**
     * addPlaylist
     *
     * Playlist.
     * https://opensubsonic.netlify.app/docs/responses/playlistwithsongs/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addPlaylist(array $response, Playlist|Search $playlist, bool $songs = false): array
    {
        if ($playlist instanceof Playlist) {
            $response['subsonic-response']['playlist'] = self::_addPlaylist_Playlist($playlist, $songs);
        }
        if ($playlist instanceof Search) {
            $response['subsonic-response']['playlist'] = self::_addPlaylist_Search($playlist, $songs);
        }

        return $response;
    }


    /**
     * addPlaylists
     *
     * Playlists.
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
                $response['subsonic-response']['playlists']['playlist'][] = self::_addPlaylist_Search($playlist);
            } else {
                $playlist = new Playlist((int)$playlist_id);
                if ($playlist->isNew()) {
                    continue;
                }
                $response['subsonic-response']['playlists']['playlist'][] = self::_addPlaylist_Playlist($playlist);
            }
        }

        return $response;
    }

    /**
     * addPlaylistWithSongs
     *
     * Playlist with songs.
     */


    /**
     * addPlayQueue
     *
     * NowPlayingEntry.
     */


    /**
     * addPodcastChannel
     *
     * A Podcast channel
     */


    /**
     * addPodcastEpisode
     *
     * A Podcast episode
     */


    /**
     * addPodcasts
     *
     * Podcasts.
     */


    /**
     * addPodcastStatus
     *
     * An enumeration of possible podcast statuses
     */


    /**
     * addRecordLabel
     *
     * A record label for an album.
     */


    /**
     * addReplayGain
     *
     * The replay gain data of a song.
     */


    /**
     * addScanStatus
     *
     * Scan status information.
     */


    /**
     * addSearchResult
     *
     * searchResult.
     */


    /**
     * addSearchResult2
     *
     * searchResult2.
     */


    /**
     * addSearchResult3
     *
     * search3 result.
     */


    /**
     * addShare
     *
     * Share.
     */


    /**
     * addShares
     *
     * Shares.
     */


    /**
     * addSimilarSongs
     *
     * SimilarSongs list.
     */


    /**
     * addSimilarSongs2
     *
     * SimilarSongs2 list.
     */


    /**
     * addSong
     *
     * song.
     */


    /**
     * addSongs
     *
     * Songs list.
     */


    /**
     * addStarred
     *
     * starred.
     */


    /**
     * addStarred2
     *
     * starred2.
     */


    /**
     * addStructuredLyrics
     *
     * Structured lyrics
     */


    /**
     * addSubsonicResponse
     *
     * Common answer wrapper.
     */


    /**
     * addTokenInfo
     *
     * Information about an API key
     */


    /**
     * addTopSongs
     *
     * TopSongs list.
     */


    /**
     * addUser
     *
     * user.
     */


    /**
     * addUsers
     *
     * users.
     */


    /**
     * addVideoInfo
     *
     * videoInfo.
     */


    /**
     * addVideos
     *
     * videos.
     */
}
