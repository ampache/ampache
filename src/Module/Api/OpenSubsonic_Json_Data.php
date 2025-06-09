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
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\AlbumRepositoryInterface;
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
use Ampache\Repository\Model\User_Playlist;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\SongRepositoryInterface;
use DateTime;
use DateTimeZone;
use Exception;

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
     * _addJukeboxStatus
     * @return array{
     *     'currentIndex': string,
     *      'playing': string,
     *      'gain': string,
     *      'position': string
     * }
     */
    private static function _addJukeboxStatus(LocalPlay $localplay): array
    {
        $json   = [];
        $status = $localplay->status();
        $index  = (((int)$status['track']) === 0)
            ? 0
            : $status['track'] - 1;

        $json['currentIndex'] = (string)$index;
        $json['playing']      = ($status['state'] == 'play') ? 'true' : 'false';
        $json['gain']         = (string)$status['volume'];
        $json['position']     = '0'; // TODO Not supported

        return $json;
    }
    /**
     * _addPlaylist_Playlist
     * @return array<string, mixed>
     */
    private static function _addPlaylist_Playlist(Playlist $playlist, bool $songs = false): array
    {
        $sub_id    = OpenSubsonic_Api::getPlaylistSubId($playlist->id);
        $songcount = $playlist->get_media_count('song');
        $duration  = ($songcount > 0) ? $playlist->get_total_duration() : 0;

        $json = [
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
            $json['coverArt'] = $sub_id;
        }

        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $song_id) {
                $json = self::addChild($json, $song_id, 'song', 'entry');
            }
        }

        return $json;
    }

    /**
     * _addPlaylist_Search
     * @return array<string, mixed>
     */
    private static function _addPlaylist_Search(Search $search, bool $songs = false): array
    {
        $sub_id = OpenSubsonic_Api::getSmartPlaylistSubId($search->id);

        $json = [
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

            $json['songCount'] = (string)count($allitems);
            $json['duration']  = (string)$duration;
            $json['coverArt']  = $sub_id;
            foreach ($allitems as $item) {
                $json = self::addChild($json, (int)$item['object_id'], $item['object_type']->value, 'entry');
            }
        } else {
            $json['songCount'] = (string)$search->last_count;
            $json['duration']  = (string)$search->last_duration;
            $json['coverArt']  = $sub_id;
        }

        return $json;
    }

    /**
     * _addAlbum
     *
     * Child media.
     * https://opensubsonic.netlify.app/docs/responses/child/
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private static function _addChildAlbum(array $response, int $object_id, string $elementName): array
    {
        $album = new Album($object_id);
        if ($album->isNew()) {
            return $response;
        }
        $sub_id = OpenSubsonic_Api::getAlbumSubId($album->id);

        // set the elementName if missing
        if (!isset($response[$elementName])) {
            $response[$elementName] = [];
        }

        $album_artist = $album->findAlbumArtist();
        $subParent    = ($album_artist)
            ? OpenSubsonic_Api::getArtistSubId($album_artist)
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

        $json['duration'] = (string)$album->time;

        $rating      = new Rating($album->id, "alnum");
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $json['userRating'] = (string)ceil($user_rating);
        }

        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $json['averageRating'] = (string)$avg_rating;
        }

        $starred = new Userflag($object_id, 'song');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        if ($album->year > 0) {
            $json['year'] = (string)$album->year;
        }

        $tags = Tag::get_object_tags('album', $album->id);
        if (!empty($tags)) {
            $json['genre'] = implode(',', array_column($tags, 'name'));
        }

        $response[$elementName][] = $json;

        return $response;
    }

    /**
     * _addAlbumID3
     *
     * An album from ID3 tags.
     * https://opensubsonic.netlify.app/docs/responses/albumid3/
     * @return array{'id': string, 'parent'?: string, 'album': string, 'title': string, 'name': string, 'isDir': bool, 'coverArt'?: string, 'songCount': string, 'created': string, 'duration': string, 'playCount': string, 'artistId'?: string, 'artist': string, 'year'?: string, 'genre'?: string, 'userRating'?: string, 'averageRating'?: string, 'starred'?: string}
     */
    private static function _addAlbumID3(Album $album): array
    {
        $sub_id       = OpenSubsonic_Api::getAlbumSubId($album->id);
        $album_artist = $album->findAlbumArtist();
        $subParent    = ($album_artist) ? OpenSubsonic_Api::getArtistSubId($album_artist) : false;
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

        $json['songCount'] = (string) $album->song_count;
        $json['created']   = date("c", (int)$album->addition_time);
        $json['duration']  = (string) $album->time;
        $json['playCount'] = (string)$album->total_count;
        if ($subParent) {
            $json['artistId'] = $subParent;
        }
        $json['artist'] = (string)$album->get_artist_fullname();
        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');
        $year          = ($original_year && $album->original_year)
            ? $album->original_year
            : $album->year;
        if ($year > 0) {
            $json['year'] = (string)$year;
        }

        $tags = Tag::get_object_tags('album', $album->id);
        if (!empty($tags)) {
            $json['genre'] = implode(',', array_column($tags, 'name'));
        }

        $rating      = new Rating($album->id, "album");
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $json['userRating'] = (string)ceil($user_rating);
        }

        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $json['averageRating'] = (string)$avg_rating;
        }

        $starred = new Userflag($album->id, 'album');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        return $json;
    }

    /**
     * _addArtistID3
     *
     * An artist from ID3 tags.
     * https://opensubsonic.netlify.app/docs/responses/artistid3/
     * @return array{'id': string, 'name': string, 'coverArt'?: string, 'albumCount': string, 'starred'?: string}
     */
    private static function _addArtistID3(Artist $artist): array
    {
        $sub_id = OpenSubsonic_Api::getArtistSubId($artist->id);
        $json   = [
            'id' => $sub_id,
            'name' => (string)$artist->get_fullname(),
        ];

        if ($artist->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        $json['albumCount'] = (string)$artist->album_count;

        $starred = new Userflag($artist->id, 'artist');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }

        return $json;
    }

    /**
     * _addArtist
     *
     * Artist details.
     * https://opensubsonic.netlify.app/docs/responses/artist/
     * @return array{'id': string, 'name': string, 'coverArt'?: string, 'starred'?: string, 'userRating'?: string, 'averageRating'?: string}
     */
    private static function _addArtist(Artist $artist): array
    {
        $sub_id = OpenSubsonic_Api::getArtistSubId($artist->id);
        $json   = [
            'id' => $sub_id,
            'name' => (string)$artist->get_fullname(),
        ];

        if ($artist->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        $starred = new Userflag($artist->id, 'artist');
        $result  = $starred->get_flag(null, true);
        if (is_array($result)) {
            $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
        }
        $rating      = new Rating($artist->id, "artist");
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $json['userRating'] = (string)ceil($user_rating);
        }

        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $json['averageRating'] = (string)$avg_rating;
        }

        return $json;
    }


    /**
     * _addSong
     *
     * Child media.
     * https://opensubsonic.netlify.app/docs/responses/child/
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private static function _addChildSong(array $response, int $object_id, string $elementName): array
    {
        $song = new Song($object_id);
        if ($song->isNew()) {
            return $response;
        }

        // Don't create entries for disabled songs
        if ($song->enabled) {
            $sub_id    = OpenSubsonic_Api::getSongSubId($song->id);
            $subParent = OpenSubsonic_Api::getAlbumSubId($song->album);

            // set the elementName if missing
            if (!isset($response[$elementName])) {
                $response[$elementName] = [];
            }

            $json = [
                'id' => $sub_id,
                'parent' => $subParent,
                'title' => (string)$song->title,
                'isDir' => false,
                'isVideo' => false,
                'type' => 'music',
                'albumId' => $subParent,
                'album' => (string)$song->get_album_fullname(),
                'artistId' => ($song->artist) ? OpenSubsonic_Api::getArtistSubId($song->artist) : '',
                'artist' => (string)$song->get_artist_fullname(),
            ];

            if ($song->has_art()) {
                $art_id            = (AmpConfig::get('show_song_art', false)) ? $sub_id : $subParent;
                $json['coverArt']  = $art_id;
            }

            $json['duration'] = (string)$song->time;
            $json['bitrate']  = (string)((int)($song->bitrate / 1024));

            $rating      = new Rating($song->id, "song");
            $user_rating = ($rating->get_user_rating() ?? 0);
            if ($user_rating > 0) {
                $json['userRating'] = (string)ceil($user_rating);
            }

            $avg_rating = $rating->get_average_rating();
            if ($avg_rating > 0) {
                $json['averageRating'] = (string)$avg_rating;
            }

            $starred = new Userflag($object_id, 'song');
            $result  = $starred->get_flag(null, true);
            if (is_array($result)) {
                $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
            }

            if ($song->track > 0) {
                $json['track'] = (string)$song->track;
            }

            if ($song->year > 0) {
                $json['year'] = (string)$song->year;
            }

            $tags = Tag::get_object_tags('song', $song->id);
            if (!empty($tags)) {
                $json['genre'] = implode(',', array_column($tags, 'name'));
            }

            $json['size'] = (string)$song->size;

            $disk = $song->disk;
            if ($disk > 0) {
                $json['discNumber'] = (string)$disk;
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

            $response[$elementName][] = $json;
        }

        return $response;
    }

    /**
     * _addVideo
     *  https://opensubsonic.netlify.app/docs/responses/child/
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private static function _addChildVideo(array $response, int $object_id, string $elementName): array
    {
        $video = new Video($object_id);
        if ($video->isNew()) {
            return $response;
        }

        if ($video->enabled) {
            $sub_id    = OpenSubsonic_Api::getVideoSubId($video->id);
            $subParent = OpenSubsonic_Api::getCatalogSubId($video->catalog);

            // set the elementName if missing
            if (!isset($response[$elementName])) {
                $response[$elementName] = [];
            }

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

            $json['duration'] = (string)$video->time;
            $json['bitrate']  = (string)((int)($video->bitrate / 1024));

            $rating      = new Rating($video->id, 'video');
            $user_rating = ($rating->get_user_rating() ?? 0);
            if ($user_rating > 0) {
                $json['userRating'] = (string)ceil($user_rating);
            }

            $avg_rating = $rating->get_average_rating();
            if ($avg_rating > 0) {
                $json['averageRating'] = (string)$avg_rating;
            }

            $starred = new Userflag($object_id, 'video');
            $result  = $starred->get_flag(null, true);
            if (is_array($result)) {
                $json['starred'] = date("Y-m-d\TH:i:s\Z", $result[1]);
            }

            if (isset($video->year) && $video->year > 0) {
                $json['year'] = (string)$video->year;
            }

            $tags = Tag::get_object_tags('video', (int)$video->id);
            if (!empty($tags)) {
                $json['genre'] = implode(',', array_column($tags, 'name'));
            }

            $json['size']        = (string)$video->size;
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
                    $transcode_type                 = $transcode_settings['format'];
                    $json['transcodedSuffix']       = $transcode_type;
                    $json['transcodedContentType']  = Video::type_to_mime($transcode_type);
                }
            }
            $response[$elementName][] = $json;
        }

        return $response;
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
    private static function _getAmpacheIdArrays(array $object_ids): array
    {
        $ampidarrays = [];
        $track       = 1;
        foreach ($object_ids as $object_id) {
            $ampacheId = OpenSubsonic_Api::getAmpacheId((string)$object_id);
            if ($ampacheId) {
                $ampidarrays[] = [
                    'object_id' => $ampacheId,
                    'object_type' => OpenSubsonic_Api::getAmpacheType((string)$object_id),
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
     * https://opensubsonic.netlify.app/docs/responses/albumid3/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addAlbumID3(array $response, Album $album, bool $songs = false, string $elementName = "album"): array
    {
        if ($album->isNew()) {
            return $response;
        }

        $response['subsonic-response'][$elementName] = self::_addAlbumID3($album);

        return $response;
    }

    /**
     * addAlbumID3WithSongs
     *
     * Album with songs.
     * @see self::addAlbumID3()
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
     * https://opensubsonic.netlify.app/docs/responses/artist/
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addArtist(array $response, Artist $artist, bool $extra = false, bool $albums = false, bool $albumsSet = false): array
    {
        if ($artist->isNew()) {
            return $response;
        }

        $response['subsonic-response']['artist'] = self::_addArtist($artist);

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
    public static function addArtistID3(array $response, Artist $artist, bool $songs = false): array
    {
        if ($artist->isNew()) {
            return $response;
        }

        $sub_id = OpenSubsonic_Api::getArtistSubId($artist->id);
        $json   = [
            'id' => $sub_id,
            'name' => $artist->get_fullname(),
        ];

        if ($artist->has_art()) {
            $json['coverArt'] = $sub_id;
        }

        $json['albumCount'] = (string)$artist->album_count;

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
    public static function addChild(array $response, int $object_id, string $object_type, string $elementName): array
    {
        switch ($object_type) {
            case 'song':
                return self::_addChildSong($response, $object_id, $elementName);
            case 'album':
                return self::_addChildAlbum($response, $object_id, $elementName);
            case 'video':
                return self::_addChildVideo($response, $object_id, $elementName);
            default:
                // If the object type is not recognized, return the response unchanged
                return $response;
        }
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
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addJukeboxPlaylist(array $response, LocalPlay $localplay): array
    {
        $status = self::_addJukeboxStatus($localplay);
        $tracks = $localplay->get();
        foreach ($tracks as $track) {
            if (array_key_exists('oid', $track)) {
                self::_addChildSong($status, (int)$track['oid'], 'entry');
            }
            // TODO This can be random play, democratic, podcasts, etc. not just songs
        }

        $response['subsonic-response']['jukeboxPlaylist'] = $status;

        return $response;
    }

    /**
     * addJukeboxStatus
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addJukeboxStatus(array $response, LocalPlay $localplay): array
    {
        $status = self::_addJukeboxStatus($localplay);

        $response['subsonic-response']['jukeboxstatus'] = $status;

        return $response;
    }


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
     * Playlist or playlist with songs
     * https://opensubsonic.netlify.app/docs/responses/playlist/
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
     * return playlists object with nested playlist items
     * https://opensubsonic.netlify.app/docs/responses/playlists/
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
     * https://opensubsonic.netlify.app/docs/responses/playlistwithsongs/
     * @see self::addPlaylist()
     */

    /**
     * addPlayQueue
     *
     * NowPlayingEntry.
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

            $json = [
                'current' => OpenSubsonic_Api::getSongSubId($current['object_id']),
                'position' => (string)($current['current_time'] * 1000),
                'username' => $username,
                'changed' => $date->format("c"),
                'changedBy' => $changedBy,
            ];

            foreach ($items as $row) {
                self::_addChildSong($json, (int)$row['object_id'], "entry");
            }

            $response['subsonic-response']['playQueue'] = $json;
        }


        return $response;
    }


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
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addScanStatus(array $response, User $user): array
    {
        $counts = Catalog::get_server_counts($user->id ?? 0);
        $count  = $counts['artist'] + $counts['album'] + $counts['song'] + $counts['podcast_episode'];

        $response['subsonic-response']['scanStatus'] = [
            'scanning' => false,
            'count' => (string)$count,
        ];

        return $response;
    }


    /**
     * addSearchResult
     *
     * searchResult.
     * Deprecated
     */

    /**
     * addSearchResult2
     *
     * searchResult2.
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
                $json['artist'][] = self::_addArtist($artist);
            }
        }
        if (!empty($albums)) {
            foreach ($albums as $album_id) {
                $json = self::addChild($json, $album_id, 'album', 'album');
            }
        }
        if (!empty($songs)) {
            foreach ($songs as $song_id) {
                $json = self::addChild($json, $song_id, 'song', 'song');
            }
        }

        $response['subsonic-response']['searchResult2'] = $json;

        return $response;
    }

    /**
     * addSearchResult3
     *
     * search3 result.
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
                $output_artists[] = self::_addArtistID3($artist);
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
                $output_albums[] = self::_addAlbumID3($album);
            }
            $json['album'] = $output_albums;
        }
        if (!empty($songs)) {
            foreach ($songs as $song_id) {
                $json = self::addChild($json, $song_id, 'song', 'song');
            }
        }

        $response['subsonic-response']['searchResult2'] = $json;

        return $response;
    }

    /**
     * addShare
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
     *     'visitCount': string,
     *     'object_id'?: int|string,
     *     'object_type'?: string,
     *     'entry'?: array<string, mixed>
     * }
     */
    private static function addShare(Share $share): array
    {
        $user = new User($share->user);
        $json = [
            'id' => OpenSubsonic_Api::getShareSubId($share->id),
            'url' => (string)$share->public_url,
            'description' => (string)$share->description,
            'username' => (string)(string)$user->username,
            'created' => date("c", (int)$share->creation_date),
        ];

        if ($share->lastvisit_date > 0) {
            $json['lastVisited'] = date("c", $share->lastvisit_date);
        }

        if ($share->expire_days > 0) {
            $json['expires'] = date("c", (int)$share->creation_date + ($share->expire_days * 86400));
        }

        $json['visitCount'] = (string)$share->counter;

        if ($share->object_type == 'song') {
            self::addChild($json, $share->object_id, 'song', "entry");
        } elseif ($share->object_type == 'playlist') {
            $playlist = new Playlist($share->object_id);
            $songs    = $playlist->get_songs();
            foreach ($songs as $song_id) {
                self::addChild($json, $song_id, 'song', "entry");
            }
        } elseif ($share->object_type == 'album') {
            $songs = self::getSongRepository()->getByAlbum($share->object_id);
            foreach ($songs as $song_id) {
                self::addChild($json, $song_id, 'song', "entry");
            }
        }

        return $json;
    }


    /**
     * addShares
     *
     * Shares.
     * @param array{'subsonic-response': array<string, mixed>} $response
     * @param list<int> $shares
     * @return array{'subsonic-response': array<string, mixed>}
     */
    public static function addShares(array $response, array $shares): array
    {
        $response['subsonic-response']['shares']['share'] = [];
        foreach ($shares as $share_id) {
            $share = new Share($share_id);
            // Don't add share with max counter already reached
            if ($share->max_counter === 0 || $share->counter < $share->max_counter) {
                $response['subsonic-response']['shares']['share'][] = self::addShare($share);
            }
        }

        return $response;
    }


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
     *  Information about an API key
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

    /**
     * @deprecated
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
