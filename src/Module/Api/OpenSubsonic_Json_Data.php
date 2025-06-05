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

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

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
    private static function _createResponse(string $status = 'ok'): array
    {

        return [
            'subsonic-response' => [
                'status' => $status,
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
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_MISSINGPARAM;
                $error['error']['message'] = "Required parameter is missing.";
                break;
            case OpenSubsonic_Api::SSERROR_APIVERSION_CLIENT:
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_APIVERSION_CLIENT;
                $error['error']['message'] = "Incompatible Subsonic REST protocol version. Client must upgrade.";
                break;
            case OpenSubsonic_Api::SSERROR_APIVERSION_SERVER:
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_APIVERSION_SERVER;
                $error['error']['message'] = "Incompatible Subsonic REST protocol version. Server must upgrade.";
                break;
            case OpenSubsonic_Api::SSERROR_BADAUTH:
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_BADAUTH;
                $error['error']['message'] = "Wrong username or password.";
                break;
            case OpenSubsonic_Api::SSERROR_TOKENAUTHNOTSUPPORTED:
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_TOKENAUTHNOTSUPPORTED;
                $error['error']['message'] = "Token authentication not supported.";
                break;
            case OpenSubsonic_Api::SSERROR_UNAUTHORIZED:
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_UNAUTHORIZED;
                $error['error']['message'] = "User is not authorized for the given operation.";
                break;
            case OpenSubsonic_Api::SSERROR_TRIAL:
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_TRIAL;
                $error['error']['message'] = "The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details.";
                break;
            case OpenSubsonic_Api::SSERROR_DATA_NOTFOUND:
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_DATA_NOTFOUND;
                $error['error']['message'] = "The requested data was not found.";
                break;
            case OpenSubsonic_Api::SSERROR_AUTHMETHODNOTSUPPORTED:
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_AUTHMETHODNOTSUPPORTED;
                $error['error']['message'] = "Provided authentication mechanism not supported.";
                break;
            case OpenSubsonic_Api::SSERROR_AUTHMETHODCONFLICT:
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_AUTHMETHODCONFLICT;
                $error['error']['message'] = "Multiple conflicting authentication mechanisms provided.";
                break;
            case OpenSubsonic_Api::SSERROR_BADAPIKEY:
                $error['error']['code']    = OpenSubsonic_Api::SSERROR_BADAPIKEY;
                $error['error']['message'] = "Invalid API key.";
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
     */


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
     */


    /**
     * addPlaylists
     *
     * Playlists.
     */


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
