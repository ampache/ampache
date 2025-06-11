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
use Ampache\Module\Util\InterfaceImplementationChecker;
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
use SimpleXMLElement;

/**
 * OpenSubsonic_Xml_Data Class
 *
 * This class takes care of all of the xml document stuff for SubSonic Responses
 */
class OpenSubsonic_Xml_Data
{
    /**
     * _createResponse
     */
    private static function _createResponse(string $status = 'ok'): SimpleXMLElement
    {
        $response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>');
        $response->addAttribute('xmlns', 'http://subsonic.org/restapi');
        $response->addAttribute('status', (string)$status);
        $response->addAttribute('version', OpenSubsonic_Api::API_VERSION);
        $response->addAttribute('type', 'ampache');
        $response->addAttribute('serverVersion', AmpConfig::get('version'));
        $response->addAttribute('openSubsonic', "1");

        return $response;
    }

    /**
     * _createSuccessResponse
     */
    private static function _createSuccessResponse(string $function = ''): SimpleXMLElement
    {
        $response = self::_createResponse();
        debug_event(self::class, 'API success in function ' . $function . '-' . OpenSubsonic_Api::API_VERSION, 5);

        return $response;
    }

    /**
     * _createFailedResponse
     */
    private static function _createFailedResponse(string $function = ''): SimpleXMLElement
    {
        $response = self::_createResponse('failed');
        debug_event(self::class, 'API fail in function ' . $function . '-' . OpenSubsonic_Api::API_VERSION, 3);

        return $response;
    }

    /**
     * addResponse
     *
     * Generate a subsonic-response
     * https://opensubsonic.netlify.app/docs/responses/subsonic-response/
     */
    public static function addResponse(string $function): SimpleXMLElement
    {
        return self::_createSuccessResponse($function);
    }

    /**
     * addError
     * Add a failed subsonic-response with error information.
     */
    public static function addError(int $code, string $function): SimpleXMLElement
    {
        $xml  = self::_createFailedResponse($function);
        $xerr = self::_addChildToResultXml($xml, 'error');
        $xerr->addAttribute('code', (string)$code);

        $message = "Error creating response.";
        switch ($code) {
            case OpenSubsonic_Api::SSERROR_MISSINGPARAM:
                $message = "Required parameter is missing.";
                break;
            case OpenSubsonic_Api::SSERROR_APIVERSION_CLIENT:
                $message = "Incompatible Subsonic REST protocol version. Client must upgrade.";
                break;
            case OpenSubsonic_Api::SSERROR_APIVERSION_SERVER:
                $message = "Incompatible Subsonic REST protocol version. Server must upgrade.";
                break;
            case OpenSubsonic_Api::SSERROR_BADAUTH:
                $message = "Wrong username or password.";
                break;
            case OpenSubsonic_Api::SSERROR_TOKENAUTHNOTSUPPORTED:
                $message = "Token authentication not supported.";
                break;
            case OpenSubsonic_Api::SSERROR_UNAUTHORIZED:
                $message = "User is not authorized for the given operation.";
                break;
            case OpenSubsonic_Api::SSERROR_TRIAL:
                $message = "The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details.";
                break;
            case OpenSubsonic_Api::SSERROR_DATA_NOTFOUND:
                $message = "The requested data was not found.";
                break;
            case OpenSubsonic_Api::SSERROR_AUTHMETHODNOTSUPPORTED:
                $message = "Provided authentication mechanism not supported.";
                break;
            case OpenSubsonic_Api::SSERROR_AUTHMETHODCONFLICT:
                $message = "Multiple conflicting authentication mechanisms provided.";
                break;
            case OpenSubsonic_Api::SSERROR_BADAPIKEY:
                $message = "Invalid API key.";
                break;
        }
        $xerr->addAttribute('message', $message);
        $xerr->addAttribute('helpUrl', 'https://ampache.org/api/subsonic');

        return $xml;
    }

    /**
     * addLicense
     */
    public static function addLicense(SimpleXMLElement $xml): SimpleXMLElement
    {
        $xlic = self::_addChildToResultXml($xml, 'license');
        $xlic->addAttribute('valid', 'true');
        $xlic->addAttribute('email', 'webmaster@ampache.org');

        return $xml;
    }

    /**
     * addMusicFolders
     * @param int[] $catalogs
     */
    public static function addMusicFolders(SimpleXMLElement $xml, array $catalogs): SimpleXMLElement
    {
        $xfolders = self::_addChildToResultXml($xml, 'musicFolders');
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }
            $xfolder = self::_addChildToResultXml($xfolders, 'musicFolder');
            $xfolder->addAttribute('id', OpenSubsonic_Api::getCatalogSubId($catalog_id));
            $xfolder->addAttribute('name', (string)$catalog->name);
        }

        return $xml;
    }

    /**
     * addIndexes
     * @param list<array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     */
    public static function addIndexes(SimpleXMLElement $xml, array $artists, int $lastModified = 0): SimpleXMLElement
    {
        $xindexes = self::_addChildToResultXml($xml, 'indexes');
        $xindexes->addAttribute('lastModified', number_format($lastModified * 1000, 0, '.', ''));
        self::_addIgnoredArticles($xindexes);
        self::_addIndex($xindexes, $artists);

        return $xml;
    }

    /**
     * addIgnoredArticles
     */
    private static function _addIgnoredArticles(SimpleXMLElement $xml): void
    {
        $ignoredArticles = AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La');
        if (!empty($ignoredArticles)) {
            $ignoredArticles = str_replace('|', ' ', $ignoredArticles);
            $xml->addAttribute('ignoredArticles', (string)$ignoredArticles);
        }
    }

    /**
     * addIndex
     * @param list<array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     */
    private static function _addIndex(SimpleXMLElement $xml, array $artists): void
    {
        $xlastcat     = null;
        $sharpartists = [];
        $xlastletter  = '';
        foreach ($artists as $artist) {
            if (strlen((string)$artist['name']) > 0) {
                $letter = strtoupper((string)$artist['name'][0]);
                if ($letter == 'X' || $letter == 'Y' || $letter == 'Z') {
                    $letter = 'X-Z';
                } elseif (!preg_match("/^[A-W]$/", $letter)) {
                    $sharpartists[] = $artist;
                    continue;
                }

                if ($letter != $xlastletter) {
                    $xlastletter = $letter;
                    $xlastcat    = self::_addChildToResultXml($xml, 'index');
                    $xlastcat->addAttribute('name', (string)$xlastletter);
                }
            }

            if ($xlastcat != null) {
                self::_addArtistArray($xlastcat, $artist);
            }
        }

        // Always add # index at the end
        if (count($sharpartists) > 0) {
            $xsharpcat = self::_addChildToResultXml($xml, 'index');
            $xsharpcat->addAttribute('name', '#');

            foreach ($sharpartists as $artist) {
                self::_addArtistArray($xsharpcat, $artist);
            }
        }
    }

    /**
     * addOpenSubsonicExtension
     * @param array<string, int[]> $extensions
     */
    public static function addOpenSubsonicExtensions(SimpleXMLElement $xml, array $extensions): SimpleXMLElement
    {
        foreach ($extensions as $name => $versions) {
            $xextension = self::_addChildToResultXml($xml, 'openSubsonicExtensions');
            $xextension->addAttribute('name', $name);
            foreach ($versions as $version) {
                $xextension->addChild('versions', (string)$version);
            }
        }

        return $xml;
    }

    /**
     * addArtists
     * @param list<array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     */
    public static function addArtists(SimpleXMLElement $xml, array $artists): SimpleXMLElement
    {
        $xartists = self::_addChildToResultXml($xml, 'artists');
        self::_addIgnoredArticles($xartists);
        self::_addIndex($xartists, $artists);

        return $xml;
    }

    /**
     * addArtist
     */
    public static function addArtist(SimpleXMLElement $xml, Artist $artist, bool $albums = false): SimpleXMLElement
    {
        if ($artist->isNew()) {
            return $xml;
        }

        $sub_id  = OpenSubsonic_Api::getArtistSubId($artist->id);
        $xartist = self::_addChildToResultXml($xml, 'artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', (string)$artist->get_fullname());

        if ($artist->has_art()) {
            $xartist->addAttribute('coverArt', $sub_id);
        }

        $xartist->addAttribute('albumCount', (string)$artist->album_count);

        self::_setIfStarred($xartist, 'artist', $artist->id);
        if ($albums) {
            $allalbums = self::getAlbumRepository()->getAlbumByArtist($artist->id);
            foreach ($allalbums as $album_id) {
                $album = new Album($album_id);
                self::addAlbumID3($xartist, $album);
            }
        }

        return $xml;
    }

    /**
     * addChildArray
     * @param array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * } $child
     */
    private static function _addChildArray(SimpleXMLElement $xml, array $child): void
    {
        $sub_id = OpenSubsonic_Api::getArtistSubId($child['id']);
        $xchild = self::_addChildToResultXml($xml, 'child');
        $xchild->addAttribute('id', $sub_id);
        if (array_key_exists('catalog_id', $child)) {
            $xchild->addAttribute('parent', (string)$child['catalog_id']);
        }
        $xchild->addAttribute('isDir', 'true');
        $xchild->addAttribute('title', $child['f_name']);
        $xchild->addAttribute('artist', $child['f_name']);
        if (array_key_exists('has_art', $child) && !empty($child['has_art'])) {
            $xchild->addAttribute('coverArt', $sub_id);
        }
    }

    /**
     * addArtistArray
     * @param array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * } $artist
     */
    private static function _addArtistArray(SimpleXMLElement $xml, array $artist): void
    {
        $sub_id  = OpenSubsonic_Api::getArtistSubId($artist['id']);
        $xartist = self::_addChildToResultXml($xml, 'artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', $artist['f_name']);
        if (array_key_exists('has_art', $artist) && !empty($artist['has_art'])) {
            $xartist->addAttribute('coverArt', $sub_id);
        }
        $xartist->addAttribute('albumCount', (string)$artist['album_count']);
        self::_setIfStarred($xartist, 'artist', $artist['id']);
    }

    /**
     * addAlbumList
     * https://opensubsonic.netlify.app/docs/responses/albumList/
     * @param int[] $albums
     */
    public static function addAlbumList(SimpleXMLElement $xml, array $albums): SimpleXMLElement
    {
        $xlist = self::_addChildToResultXml($xml, htmlspecialchars('albumList'));
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbumID3($xlist, $album);
        }

        return $xml;
    }

    /**
     * addAlbumList2
     * https://opensubsonic.netlify.app/docs/responses/albumList2/
     * @param int[] $albums
     */
    public static function addAlbumList2(SimpleXMLElement $xml, array $albums): SimpleXMLElement
    {
        $xlist = self::_addChildToResultXml($xml, htmlspecialchars('albumList2'));
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbumID3($xlist, $album);
        }

        return $xml;
    }

    /**
     * addAlbumID3
     *
     *  An album from ID3 tags.
     *  https://opensubsonic.netlify.app/docs/responses/albumid3/
     */
    public static function addAlbumID3(SimpleXMLElement $xml, Album $album, bool $songs = false, string $elementName = 'album'): SimpleXMLElement
    {
        if ($album->isNew()) {
            return $xml;
        }

        $sub_id = OpenSubsonic_Api::getAlbumSubId($album->id);
        $xalbum = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xalbum->addAttribute('id', $sub_id);
        $album_artist = $album->findAlbumArtist();
        if ($album_artist) {
            $xalbum->addAttribute('parent', OpenSubsonic_Api::getArtistSubId($album_artist));
        }
        $f_name = (string)$album->get_fullname();
        $xalbum->addAttribute('album', $f_name);
        $xalbum->addAttribute('title', $f_name);
        $xalbum->addAttribute('name', $f_name);
        $xalbum->addAttribute('isDir', 'true');
        //$xalbum->addAttribute('discNumber', (string)$album->disk);
        if ($album->has_art()) {
            $xalbum->addAttribute('coverArt', $sub_id);
        }
        $xalbum->addAttribute('songCount', (string) $album->song_count);
        $xalbum->addAttribute('created', date('c', (int)$album->addition_time));
        $xalbum->addAttribute('duration', (string) $album->time);
        $xalbum->addAttribute('playCount', (string)$album->total_count);
        if ($album_artist) {
            $xalbum->addAttribute('artistId', OpenSubsonic_Api::getArtistSubId($album_artist));
        }
        $xalbum->addAttribute('artist', (string)$album->get_artist_fullname());
        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');
        $year          = ($original_year && $album->original_year)
            ? $album->original_year
            : $album->year;
        if ($year > 0) {
            $xalbum->addAttribute('year', (string)$year);
        }
        $tags = Tag::get_object_tags('album', $album->id);
        if (!empty($tags)) {
            $xalbum->addAttribute('genre', implode(',', array_column($tags, 'name')));
        }

        $rating      = new Rating($album->id, 'album');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $xalbum->addAttribute('userRating', (string)ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xalbum->addAttribute('averageRating', (string)$avg_rating);
        }
        self::_setIfStarred($xalbum, 'album', $album->id);

        if ($songs) {
            $media_ids = self::getAlbumRepository()->getSongs($album->id);
            foreach ($media_ids as $song_id) {
                self::addSong($xalbum, $song_id);
            }
        }

        return $xml;
    }

    /**
     * addAlbum
     */
    public static function addAlbum(SimpleXMLElement $xml, Album $album, bool $songs = false, string $elementName = 'album'): void
    {
        if ($album->isNew()) {
            return;
        }

        $sub_id = OpenSubsonic_Api::getAlbumSubId($album->id);
        $xalbum = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xalbum->addAttribute('id', $sub_id);
        $album_artist = $album->findAlbumArtist();
        if ($album_artist) {
            $xalbum->addAttribute('parent', OpenSubsonic_Api::getArtistSubId($album_artist));
        }
        $f_name = $album->get_fullname();
        $xalbum->addAttribute('album', $f_name);
        $xalbum->addAttribute('title', $f_name);
        $xalbum->addAttribute('name', $f_name);
        $xalbum->addAttribute('isDir', 'true');
        //$xalbum->addAttribute('discNumber', (string)$album->disk);
        if ($album->has_art()) {
            $xalbum->addAttribute('coverArt', $sub_id);
        }
        $xalbum->addAttribute('songCount', (string) $album->song_count);
        $xalbum->addAttribute('created', date('c', (int)$album->addition_time));
        $xalbum->addAttribute('duration', (string) $album->time);
        $xalbum->addAttribute('playCount', (string)$album->total_count);
        if ($album_artist) {
            $xalbum->addAttribute('artistId', OpenSubsonic_Api::getArtistSubId($album_artist));
        }
        $xalbum->addAttribute('artist', (string)$album->get_artist_fullname());
        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');
        $year          = ($original_year && $album->original_year)
            ? $album->original_year
            : $album->year;
        if ($year > 0) {
            $xalbum->addAttribute('year', (string)$year);
        }
        $tags = Tag::get_object_tags('album', $album->id);
        if (!empty($tags)) {
            $xalbum->addAttribute('genre', implode(',', array_column($tags, 'name')));
        }

        $rating      = new Rating($album->id, 'album');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $xalbum->addAttribute('userRating', (string)ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xalbum->addAttribute('averageRating', (string)$avg_rating);
        }
        self::_setIfStarred($xalbum, 'album', $album->id);

        if ($songs) {
            $media_ids = self::getAlbumRepository()->getSongs($album->id);
            foreach ($media_ids as $song_id) {
                self::addSong($xalbum, $song_id);
            }
        }
    }

    /**
     * addSong
     */
    public static function addSong(SimpleXMLElement $xml, int $song_id, string $elementName = 'song'): SimpleXMLElement
    {
        $song = new Song($song_id);
        if ($song->isNew()) {
            return $xml;
        }

        // Don't create entries for disabled songs
        if ($song->enabled) {
            $sub_id    = OpenSubsonic_Api::getSongSubId($song->id);
            $subParent = OpenSubsonic_Api::getAlbumSubId($song->album);
            $xsong     = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
            $xsong->addAttribute('id', $sub_id);
            $xsong->addAttribute('parent', $subParent);
            //$xsong->addAttribute('created', );
            $xsong->addAttribute('title', (string)$song->title);
            $xsong->addAttribute('isDir', 'false');
            $xsong->addAttribute('isVideo', 'false');
            $xsong->addAttribute('type', 'music');
            $xsong->addAttribute('albumId', $subParent);
            $xsong->addAttribute('album', (string)$song->get_album_fullname());
            $xsong->addAttribute('artistId', ($song->artist) ? OpenSubsonic_Api::getArtistSubId($song->artist) : '');
            $xsong->addAttribute('artist', (string)$song->get_artist_fullname());
            if ($song->has_art()) {
                $art_id = (AmpConfig::get('show_song_art', false)) ? $sub_id : $subParent;
                $xsong->addAttribute('coverArt', $art_id);
            }
            $xsong->addAttribute('duration', (string)$song->time);
            $xsong->addAttribute('bitRate', (string)((int)($song->bitrate / 1024)));
            $rating      = new Rating($song->id, 'song');
            $user_rating = ($rating->get_user_rating() ?? 0);
            if ($user_rating > 0) {
                $xsong->addAttribute('userRating', (string)ceil($user_rating));
            }
            $avg_rating = $rating->get_average_rating();
            if ($avg_rating > 0) {
                $xsong->addAttribute('averageRating', (string)$avg_rating);
            }
            self::_setIfStarred($xsong, 'song', $song->id);
            if ($song->track > 0) {
                $xsong->addAttribute('track', (string)$song->track);
            }
            if ($song->year > 0) {
                $xsong->addAttribute('year', (string)$song->year);
            }
            $tags = Tag::get_object_tags('song', $song->id);
            if (!empty($tags)) {
                $xsong->addAttribute('genre', implode(',', array_column($tags, 'name')));
            }
            $xsong->addAttribute('size', (string)$song->size);
            $disk = $song->disk;
            if ($disk > 0) {
                $xsong->addAttribute('discNumber', (string)$disk);
            }
            $xsong->addAttribute('suffix', (string)$song->type);
            $xsong->addAttribute('contentType', (string)$song->mime);
            // Always return the original filename, not the transcoded one
            $xsong->addAttribute('path', (string)$song->file);
            if (AmpConfig::get('transcode', 'default') != 'never') {
                $cache_path     = (string)AmpConfig::get('cache_path', '');
                $cache_target   = (string)AmpConfig::get('cache_target', '');
                $file_target    = Catalog::get_cache_path($song->getId(), $song->getCatalogId(), $cache_path, $cache_target);
                $transcode_type = ($file_target !== null && is_file($file_target))
                    ? $cache_target
                    : Stream::get_transcode_format($song->type, null, 'api');

                if (!empty($transcode_type) && $song->type !== $transcode_type) {
                    // Set transcoding information
                    $xsong->addAttribute('transcodedSuffix', $transcode_type);
                    $xsong->addAttribute('transcodedContentType', Song::type_to_mime($transcode_type));
                }
            }

            return $xsong;
        }

        return $xml;
    }

    /**
     * addDirectory will create the directory element based on the type
     */
    public static function addDirectory(SimpleXMLElement $xml, Artist|Album|Catalog $object): SimpleXMLElement
    {
        if ($object instanceof Artist) {
            self::_addDirectory_Artist($xml, $object);
        } elseif ($object instanceof Album) {
            self::_addDirectory_Album($xml, $object);
        } elseif ($object instanceof Catalog) {
            self::_addDirectory_Catalog($xml, $object);
        }

        return $xml;
    }

    /**
     * addDirectory_Album for subsonic album id
     */
    private static function _addDirectory_Album(SimpleXMLElement $xml, Album $album): void
    {
        $album_id = $album->id;
        $xdir     = self::_addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', OpenSubsonic_Api::getAlbumSubId($album_id));
        $album_artist = $album->findAlbumArtist();
        if ($album_artist) {
            $xdir->addAttribute('parent', OpenSubsonic_Api::getArtistSubId($album_artist));
        } else {
            $xdir->addAttribute('parent', (string)$album->catalog);
        }
        $xdir->addAttribute('name', (string)$album->get_fullname());
        self::_setIfStarred($xdir, 'album', $album->id);

        $media_ids = self::getAlbumRepository()->getSongs($album->id);
        foreach ($media_ids as $song_id) {
            // TODO addChild || use addChildArray
            self::addSong($xdir, $song_id, 'child');
        }
    }

    /**
     * addDirectory_Artist for subsonic artist id
     */
    private static function _addDirectory_Artist(SimpleXMLElement $xml, Artist $artist): void
    {
        $artist_id = $artist->id;
        $data      = Artist::get_id_array($artist_id);
        $xdir      = self::_addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', OpenSubsonic_Api::getArtistSubId($artist_id));
        if (array_key_exists('catalog_id', $data)) {
            $xdir->addAttribute('parent', (string)$data['catalog_id']);
        }
        $xdir->addAttribute('name', (string)$data['f_name']);
        self::_setIfStarred($xdir, 'artist', $artist_id);
        $allalbums = self::getAlbumRepository()->getAlbumByArtist($artist_id);
        foreach ($allalbums as $album_id) {
            $album = new Album($album_id);
            // TODO addChild || use addChildArray
            self::addAlbumID3($xdir, $album, false, 'child');
        }
    }

    /**
     * addDirectory_Catalog for subsonic artist id
     */
    private static function _addDirectory_Catalog(SimpleXMLElement $xml, Catalog $catalog): void
    {
        $catalog_id = $catalog->id;
        $xdir       = self::_addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', OpenSubsonic_Api::getCatalogSubId($catalog_id));
        $xdir->addAttribute('name', (string)$catalog->name);
        $allartists = Catalog::get_artist_arrays([$catalog_id]);
        foreach ($allartists as $artist) {
            self::_addChildArray($xdir, $artist);
        }
    }

    /**
     * addGenres
     * @param list<array{id: int, name: string, is_hidden: int, count: int}> $tags
     */
    public static function addGenres(SimpleXMLElement $xml, array $tags): SimpleXMLElement
    {
        $xgenres = self::_addChildToResultXml($xml, 'genres');

        foreach ($tags as $tag) {
            $otag   = new Tag($tag['id']);
            $xgenre = self::_addChildToResultXml($xgenres, 'genre', htmlspecialchars((string)$otag->name));
            $xgenre->addAttribute('songCount', (string)($otag->song));
            $xgenre->addAttribute('albumCount', (string)($otag->album));
        }

        return $xml;
    }

    /**
     * addVideos
     * @param Video[] $videos
     */
    public static function addVideos(SimpleXMLElement $xml, array $videos): SimpleXMLElement
    {
        $xvideos = self::_addChildToResultXml($xml, 'videos');
        foreach ($videos as $video) {
            self::_addVideo($xvideos, $video);
        }

        return $xml;
    }

    /**
     * addVideo
     */
    private static function _addVideo(SimpleXMLElement $xml, Video $video, string $elementName = 'video'): void
    {
        $sub_id = OpenSubsonic_Api::getVideoSubId($video->id);
        $xvideo = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xvideo->addAttribute('id', $sub_id);
        $xvideo->addAttribute('title', $video->getFileName());
        $xvideo->addAttribute('isDir', 'false');
        if ($video->has_art()) {
            $xvideo->addAttribute('coverArt', $sub_id);
        }
        $xvideo->addAttribute('isVideo', 'true');
        $xvideo->addAttribute('type', 'video');
        $xvideo->addAttribute('duration', (string)$video->time);
        if (isset($video->year) && $video->year > 0) {
            $xvideo->addAttribute('year', (string)$video->year);
        }
        $tags = Tag::get_object_tags('video', (int)$video->id);
        if (!empty($tags)) {
            $xvideo->addAttribute('genre', implode(',', array_column($tags, 'name')));
        }
        $xvideo->addAttribute('size', (string)$video->size);
        $xvideo->addAttribute('suffix', (string)$video->type);
        $xvideo->addAttribute('contentType', (string)$video->mime);
        // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
        $path = basename($video->file);
        $xvideo->addAttribute('path', (string)$path);

        self::_setIfStarred($xvideo, 'video', $video->id);
        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode', 'default');
        $valid_types   = Stream::get_stream_types_for_type($video->type, 'api');
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && !in_array('native', $valid_types))) {
            $transcode_settings = $video->get_transcode_settings(null, 'api');
            if (!empty($transcode_settings)) {
                $transcode_type = $transcode_settings['format'];
                $xvideo->addAttribute('transcodedSuffix', (string)$transcode_type);
                $xvideo->addAttribute('transcodedContentType', Video::type_to_mime($transcode_type));
            }
        }
    }

    /**
     * addVideoInfo
     */
    public static function addVideoInfo(SimpleXMLElement $xml, int $video_id): SimpleXMLElement
    {
        $xvideoinfo = self::_addChildToResultXml($xml, 'videoinfo');
        $xvideoinfo->addAttribute('id', OpenSubsonic_Api::getVideoSubId($video_id));

        return $xml;
    }

    /**
     * addPlaylists
     * return playlists object with nested playlist items
     * https://opensubsonic.netlify.app/docs/responses/playlists/
     * https://opensubsonic.netlify.app/docs/responses/playlist/
     * @param int[]|string[] $playlists
     */
    public static function addPlaylists(SimpleXMLElement $xml, ?User $user, array $playlists): SimpleXMLElement
    {
        $xplaylists = self::_addChildToResultXml($xml, 'playlists');
        foreach ($playlists as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist  = 1000000
             */
            $playlist = ((int)$playlist_id === 0)
                ? new Search((int) str_replace('smart_', '', (string) $playlist_id), 'song', $user)
                : new Playlist((int)$playlist_id);

            if ($playlist->isNew()) {
                continue;
            }

            self::addPlaylist($xplaylists, $playlist);
        }

        return $xml;
    }

    /**
     * addPlaylist
     * https://opensubsonic.netlify.app/docs/responses/playlist/
     */
    public static function addPlaylist(SimpleXMLElement $xml, Playlist|Search $playlist, bool $songs = false): SimpleXMLElement
    {
        if ($playlist instanceof Playlist) {
            $xml = self::_addPlaylist_Playlist($xml, $playlist, $songs);
        }
        if ($playlist instanceof Search) {
            $xml = self::_addPlaylist_Search($xml, $playlist, $songs);
        }

        return $xml;
    }

    /**
     * addPlaylist_Playlist
     */
    private static function _addPlaylist_Playlist(SimpleXMLElement $xml, Playlist $playlist, bool $songs = false): SimpleXMLElement
    {
        $sub_id    = OpenSubsonic_Api::getPlaylistSubId($playlist->id);
        $songcount = $playlist->get_media_count('song');
        $duration  = ($songcount > 0) ? $playlist->get_total_duration() : 0;
        $xplaylist = self::_addChildToResultXml($xml, 'playlist');
        $xplaylist->addAttribute('id', $sub_id);
        $xplaylist->addAttribute('name', (string)$playlist->get_fullname());
        $xplaylist->addAttribute('owner', (string)$playlist->username);
        $xplaylist->addAttribute('public', ($playlist->type != 'private') ? 'true' : 'false');
        $xplaylist->addAttribute('songCount', (string)$songcount);
        $xplaylist->addAttribute('duration', (string)$duration);
        $xplaylist->addAttribute('created', date('c', (int)$playlist->date));
        $xplaylist->addAttribute('changed', date('c', (int)$playlist->last_update));
        if ($playlist->has_art()) {
            $xplaylist->addAttribute('coverArt', $sub_id);
        }

        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $song_id) {
                // TODO addEntry
                self::addSong($xplaylist, $song_id, 'entry');
            }
        }

        return $xml;
    }

    /**
     * addPlaylist_Search
     */
    private static function _addPlaylist_Search(SimpleXMLElement $xml, Search $search, bool $songs = false): SimpleXMLElement
    {
        $sub_id    = OpenSubsonic_Api::getSmartPlaylistSubId($search->id);
        $xplaylist = self::_addChildToResultXml($xml, 'playlist');
        $xplaylist->addAttribute('id', $sub_id);
        $xplaylist->addAttribute('name', (string)$search->get_fullname());
        $xplaylist->addAttribute('owner', (string)$search->username);
        $xplaylist->addAttribute('public', ($search->type != 'private') ? 'true' : 'false');
        $xplaylist->addAttribute('created', date('c', (int)$search->date));
        $xplaylist->addAttribute('changed', date('c', time()));

        if ($songs) {
            $allitems = $search->get_items();
            $xplaylist->addAttribute('songCount', (string)count($allitems));
            $duration = (count($allitems) > 0) ? Search::get_total_duration($allitems) : 0;
            $xplaylist->addAttribute('duration', (string)$duration);
            $xplaylist->addAttribute('coverArt', $sub_id);
            foreach ($allitems as $item) {
                // TODO addEntry
                self::addSong($xplaylist, (int)$item['object_id'], 'entry');
            }
        } else {
            $xplaylist->addAttribute('songCount', (string)$search->last_count);
            $xplaylist->addAttribute('duration', (string)$search->last_duration);
            $xplaylist->addAttribute('coverArt', $sub_id);
        }

        return $xml;
    }

    /**
     * addPlayQueue
     * current="133" position="45000" username="admin" changed="2015-02-18T15:22:22.825Z" changedBy="android"
     */
    public static function addPlayQueue(SimpleXMLElement $xml, User_Playlist $playQueue, string $username): SimpleXMLElement
    {
        $items = $playQueue->get_items();
        if (!empty($items)) {
            $current   = $playQueue->get_current_object();
            $play_time = date("Y-m-d H:i:s", $playQueue->get_time());
            $date      = new DateTime($play_time);
            $date->setTimezone(new DateTimeZone('UTC'));
            $changedBy  = $playQueue->client ?? '';
            $xplayqueue = self::_addChildToResultXml($xml, 'playQueue');
            if (!empty($current)) {
                $xplayqueue->addAttribute('current', OpenSubsonic_Api::getSongSubId($current['object_id']));
                $xplayqueue->addAttribute('position', (string)($current['current_time'] * 1000));
                $xplayqueue->addAttribute('username', (string)$username);
                $xplayqueue->addAttribute('changed', $date->format('c'));
                $xplayqueue->addAttribute('changedBy', (string)$changedBy);
            }

            foreach ($items as $row) {
                // TODO addEntry
                self::addSong($xplayqueue, (int)$row['object_id'], 'entry');
            }
        }

        return $xml;
    }

    /**
     * addRandomSongs
     * @param int[] $songs
     */
    public static function addRandomSongs(SimpleXMLElement $xml, array $songs): SimpleXMLElement
    {
        $xsongs = self::_addChildToResultXml($xml, 'randomSongs');
        foreach ($songs as $song_id) {
            self::addSong($xsongs, $song_id);
        }

        return $xml;
    }

    /**
     * addSongsByGenre
     * @param int[] $songs
     */
    public static function addSongsByGenre(SimpleXMLElement $xml, array $songs): SimpleXMLElement
    {
        $xsongs = self::_addChildToResultXml($xml, 'songsByGenre');
        foreach ($songs as $song_id) {
            self::addSong($xsongs, $song_id);
        }

        return $xml;
    }

    /**
     * addTopSongs
     * @param int[] $songs
     */
    public static function addTopSongs(SimpleXMLElement $xml, array $songs): SimpleXMLElement
    {
        $xsongs = self::_addChildToResultXml($xml, 'topSongs');
        foreach ($songs as $song_id) {
            self::addSong($xsongs, $song_id);
        }

        return $xml;
    }

    /**
     * addNowPlaying
     * @param list<array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int
     * }> $data
     */
    public static function addNowPlaying(SimpleXMLElement $xml, array $data): SimpleXMLElement
    {
        $xplaynow = self::_addChildToResultXml($xml, 'nowPlaying');
        foreach ($data as $row) {
            // TODO addEntry
            if (
                $row['media'] instanceof Song &&
                !$row['media']->isNew() &&
                $row['media']->enabled
            ) {
                $track = self::addSong($xplaynow, $row['media']->getId(), 'entry');
                $track->addAttribute('username', (string)$row['client']->username);
                $track->addAttribute('minutesAgo', (string)(abs((time() - ($row['expire'] - $row['media']->time)) / 60)));
                $track->addAttribute('playerId', '0');
                $track->addAttribute('playerName', (string)$row['agent']);
            }
        }

        return $xml;
    }

    ///**
    // * addSearchResult
    // * @param int[] $artists
    // * @param int[] $albums
    // * @param int[] $songs
    // */
    //public static function addSearchResult(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    //{
    //    $xresult = self::addChildToResultXml($xml, htmlspecialchars('searchResult'));
    //    foreach ($artists as $artist_id) {
    //        $artist = new Artist((int) $artist_id);
    //        self::addArtist($xresult, $artist);
    //    }
    //    foreach ($albums as $album_id) {
    //        $album = new Album($album_id);
    //        self::addAlbumID3($xresult, $album);
    //    }
    //    foreach ($songs as $song_id) {
    //        self::addSong($xresult, $song_id);
    //    }
    //
    //    return $xml;
    //}

    /**
     * addSearchResult2
     * https://opensubsonic.netlify.app/docs/responses/searchresult2/
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public static function addSearchResult2(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xresult = self::_addChildToResultXml($xml, htmlspecialchars('searchResult2'));
        foreach ($artists as $artist_id) {
            $artist = new Artist((int) $artist_id);
            self::addArtist($xresult, $artist);
        }
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbum($xresult, $album);
        }
        foreach ($songs as $song_id) {
            self::addSong($xresult, $song_id);
        }

        return $xml;
    }

    /**
     * addSearchResult3
     * https://opensubsonic.netlify.app/docs/responses/searchresult3/
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public static function addSearchResult3(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xresult = self::_addChildToResultXml($xml, htmlspecialchars('searchResult3'));
        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            self::addArtist($xresult, $artist);
        }
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbumID3($xresult, $album);
        }
        foreach ($songs as $song_id) {
            self::addSong($xresult, $song_id);
        }

        return $xml;
    }

    /**
     * addStarred
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public static function addStarred(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xstarred = self::_addChildToResultXml($xml, htmlspecialchars('starred'));

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            self::addArtist($xstarred, $artist);
        }

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbumID3($xstarred, $album);
        }

        foreach ($songs as $song_id) {
            self::addSong($xstarred, $song_id);
        }

        return $xml;
    }

    /**
     * addStarred2
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public static function addStarred2(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xstarred = self::_addChildToResultXml($xml, htmlspecialchars('starred2'));

        foreach ($artists as $artist_id) {
            $artist = new Artist((int) $artist_id);
            self::addArtist($xstarred, $artist);
        }

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbumID3($xstarred, $album);
        }

        foreach ($songs as $song_id) {
            self::addSong($xstarred, $song_id);
        }

        return $xml;
    }

    /**
     * addUser
     */
    public static function addUser(SimpleXMLElement $xml, User $user): SimpleXMLElement
    {
        $xuser = self::_addChildToResultXml($xml, 'user');
        $xuser->addAttribute('username', (string)$user->username);
        $xuser->addAttribute('email', (string)$user->email);
        $xuser->addAttribute('scrobblingEnabled', 'true');
        $isManager = ($user->access >= 75);
        $isAdmin   = ($user->access === 100);
        $xuser->addAttribute('adminRole', ($isAdmin) ? 'true' : 'false');
        $xuser->addAttribute('settingsRole', 'true');
        $xuser->addAttribute('downloadRole', Preference::get_by_user($user->id, 'download') ? 'true' : 'false');
        $xuser->addAttribute('playlistRole', 'true');
        $xuser->addAttribute('coverArtRole', ($isManager) ? 'true' : 'false');
        $xuser->addAttribute('commentRole', (AmpConfig::get('social')) ? 'true' : 'false');
        $xuser->addAttribute('podcastRole', (AmpConfig::get('podcast')) ? 'true' : 'false');
        $xuser->addAttribute('streamRole', 'true');
        $xuser->addAttribute('jukeboxRole', (AmpConfig::get('allow_localplay_playback') && AmpConfig::get('localplay_controller') && Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::GUEST)) ? 'true' : 'false');
        $xuser->addAttribute('shareRole', Preference::get_by_user($user->id, 'share') ? 'true' : 'false');
        $xuser->addAttribute('videoConversionRole', 'false');

        return $xml;
    }

    /**
     * addUsers
     * @param int[] $users
     */
    public static function addUsers(SimpleXMLElement $xml, array $users): SimpleXMLElement
    {
        $xusers = self::_addChildToResultXml($xml, 'users');
        foreach ($users as $user_id) {
            $user = new User($user_id);
            if ($user->isNew() === false) {
                self::addUser($xusers, $user);
            }
        }

        return $xml;
    }

    /**
     * addInternetRadioStations
     * @param int[] $radios
     */
    public static function addInternetRadioStations(SimpleXMLElement $xml, array $radios): SimpleXMLElement
    {
        $xradios = self::_addChildToResultXml($xml, 'internetRadioStations');
        foreach ($radios as $radio_id) {
            $radio = new Live_Stream((int)$radio_id);
            self::_addInternetRadioStation($xradios, $radio);
        }

        return $xml;
    }

    /**
     * addInternetRadioStation
     */
    private static function _addInternetRadioStation(SimpleXMLElement $xml, Live_Stream $radio): void
    {
        $xradio = self::_addChildToResultXml($xml, 'internetRadioStation');
        $xradio->addAttribute('id', OpenSubsonic_Api::getLiveStreamSubId($radio->id));
        $xradio->addAttribute('name', (string)$radio->name);
        $xradio->addAttribute('streamUrl', (string)$radio->url);
        $xradio->addAttribute('homepageUrl', (string)$radio->site_url);
    }

    /**
     * addShares
     * @param list<int> $shares
     */
    public static function addShares(SimpleXMLElement $xml, array $shares): SimpleXMLElement
    {
        $xshares = self::_addChildToResultXml($xml, 'shares');
        foreach ($shares as $share_id) {
            $share = new Share($share_id);
            // Don't add share with max counter already reached
            if ($share->max_counter === 0 || $share->counter < $share->max_counter) {
                self::_addShare($xshares, $share);
            }
        }

        return $xml;
    }

    /**
     * addShare
     */
    private static function _addShare(SimpleXMLElement $xml, Share $share): void
    {
        $xshare = self::_addChildToResultXml($xml, 'share');
        $xshare->addAttribute('id', OpenSubsonic_Api::getShareSubId($share->id));
        $xshare->addAttribute('url', (string)$share->public_url);
        $xshare->addAttribute('description', (string)$share->description);
        $user = new User($share->user);
        $xshare->addAttribute('username', (string)$user->username);
        $xshare->addAttribute('created', date('c', (int)$share->creation_date));
        if ($share->lastvisit_date > 0) {
            $xshare->addAttribute('lastVisited', date('c', (int)$share->lastvisit_date));
        }
        if ($share->expire_days > 0) {
            $xshare->addAttribute('expires', date('c', (int)$share->creation_date + ($share->expire_days * 86400)));
        }
        $xshare->addAttribute('visitCount', (string)$share->counter);

        if ($share->object_type == 'song') {
            // TODO addEntry
            self::addSong($xshare, $share->object_id, 'entry');
        } elseif ($share->object_type == 'playlist') {
            $playlist = new Playlist($share->object_id);
            $songs    = $playlist->get_songs();
            foreach ($songs as $song_id) {
                // TODO addEntry
                self::addSong($xshare, $song_id, 'entry');
            }
        } elseif ($share->object_type == 'album') {
            $songs = self::getSongRepository()->getByAlbum($share->object_id);
            foreach ($songs as $song_id) {
                // TODO addEntry
                self::addSong($xshare, $song_id, 'entry');
            }
        }
    }

    /**
     * addJukeboxPlaylist
     */
    public static function addJukeboxPlaylist(SimpleXMLElement $xml, LocalPlay $localplay): SimpleXMLElement
    {
        $xjbox  = self::addJukeboxStatus($xml, $localplay, 'jukeboxPlaylist');
        $tracks = $localplay->get();
        foreach ($tracks as $track) {
            if (array_key_exists('oid', $track)) {
                // TODO addEntry
                self::addSong($xjbox, (int)$track['oid'], 'entry');
            }
            // TODO This can be random play, democratic, podcasts, etc. not just songs
        }

        return $xml;
    }

    /**
     * addJukeboxStatus
     */
    public static function addJukeboxStatus(SimpleXMLElement $xml, LocalPlay $localplay, string $elementName = 'jukeboxStatus'): SimpleXMLElement
    {
        $xjbox  = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $status = $localplay->status();
        $index  = (((int)$status['track']) === 0)
            ? 0
            : $status['track'] - 1;
        $xjbox->addAttribute('currentIndex', (string)$index);
        $xjbox->addAttribute('playing', ($status['state'] == 'play') ? 'true' : 'false');
        $xjbox->addAttribute('gain', (string)$status['volume']);
        $xjbox->addAttribute('position', '0'); // TODO Not supported

        return $xjbox;
    }

    /**
     * addLyrics
     */
    public static function addLyrics(SimpleXMLElement $xml, string $artist, string $title, Song $song): SimpleXMLElement
    {
        if ($song->isNew()) {
            return $xml;
        }

        $lyrics = $song->get_lyrics();

        if (!empty($lyrics) && $lyrics['text'] && is_string($lyrics['text'])) {
            $text    = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $lyrics['text']);
            $text    = preg_replace('/\\n\\n/i', "\n", (string)$text);
            $text    = str_replace("\r", '', (string)$text);
            $xlyrics = self::_addChildToResultXml($xml, 'lyrics', htmlspecialchars($text));
            if ($artist) {
                $xlyrics->addAttribute('artist', (string)$artist);
            }
            if ($title) {
                $xlyrics->addAttribute('title', (string)$title);
            }
        }

        return $xml;
    }

    /**
     * addAlbumInfo
     * @param array{
     *     id: int,
     *     summary: ?string,
     *     largephoto: ?string,
     *     smallphoto: ?string,
     *     mediumphoto: ?string,
     *     megaphoto: ?string
     * } $info
     */
    public static function addAlbumInfo(SimpleXMLElement $xml, array $info): SimpleXMLElement
    {
        $album = new Album((int) $info['id']);

        $xartist = self::_addChildToResultXml($xml, htmlspecialchars('albumInfo'));
        $xartist->addChild('notes', htmlspecialchars(trim((string)$info['summary'])));
        $xartist->addChild('musicBrainzId', $album->mbid);
        //$xartist->addChild('lastFmUrl', "");
        $xartist->addChild('smallImageUrl', htmlentities((string)$info['smallphoto']));
        $xartist->addChild('mediumImageUrl', htmlentities((string)$info['mediumphoto']));
        $xartist->addChild('largeImageUrl', htmlentities((string)$info['largephoto']));

        return $xml;
    }

    /**
     * addArtistInfo
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
     */
    public static function addArtistInfo(SimpleXMLElement $xml, array $info, array $similars, string $elementName = 'artistInfo'): SimpleXMLElement
    {
        $artist = new Artist((int)($info['id'] ?? 0));
        if ($artist->isNew()) {
            return $xml;
        }

        $xartist   = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $biography = trim((string)$info['summary']);
        if (!empty($biography)) {
            $xartist->addChild('biography', htmlspecialchars($biography));
        }
        $xartist->addChild('musicBrainzId', (string)$artist->mbid);
        //$xartist->addChild('lastFmUrl', "");
        $xartist->addChild('smallImageUrl', htmlentities((string)$info['smallphoto']));
        $xartist->addChild('mediumImageUrl', htmlentities((string)$info['mediumphoto']));
        $xartist->addChild('largeImageUrl', htmlentities((string)$info['largephoto']));

        foreach ($similars as $similar) {
            $xsimilar = self::_addChildToResultXml($xartist, 'similarArtist');
            $xsimilar->addAttribute('id', (($similar['id'] !== null) ? OpenSubsonic_Api::getArtistSubId($similar['id']) : "-1"));
            $xsimilar->addAttribute('name', (string)$similar['name']);
        }

        return $xml;
    }

    /**
     * addArtistInfo2
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
     */
    public static function addArtistInfo2(SimpleXMLElement $xml, array $info, array $similars): SimpleXMLElement
    {
        return self::addArtistInfo($xml, $info, $similars, 'artistInfo2');
    }

    /**
     * addSimilarSongs
     * @param list<array{
     *     id: ?int,
     *     name?: ?string,
     *     rel?: ?string,
     *     mbid?: ?string,
     * }> $similar_songs
     */
    public static function addSimilarSongs(SimpleXMLElement $xml, array $similar_songs): SimpleXMLElement
    {
        $xsimilar = self::_addChildToResultXml($xml, 'similarSongs');
        foreach ($similar_songs as $similar_song) {
            if ($similar_song['id'] !== null) {
                self::addSong($xsimilar, $similar_song['id']);
            }
        }

        return $xml;
    }

    /**
     * addSimilarSongs2
     * @param list<array{
     *     id: ?int,
     *     name?: ?string,
     *     rel?: ?string,
     *     mbid?: ?string,
     * }> $similar_songs
     */
    public static function addSimilarSongs2(SimpleXMLElement $xml, array $similar_songs): SimpleXMLElement
    {
        $xsimilar = self::_addChildToResultXml($xml, 'similarSongs2');
        foreach ($similar_songs as $similar_song) {
            if ($similar_song['id'] !== null) {
                self::addSong($xsimilar, $similar_song['id']);
            }
        }

        return $xml;
    }

    /**
     * addPodcasts
     * @param Podcast[] $podcasts
     */
    public static function addPodcasts(SimpleXMLElement $xml, array $podcasts, bool $includeEpisodes = true): SimpleXMLElement
    {
        $xpodcasts = self::_addChildToResultXml($xml, 'podcasts');
        foreach ($podcasts as $podcast) {
            $sub_id   = OpenSubsonic_Api::getPodcastSubId($podcast->getId());
            $xchannel = self::_addChildToResultXml($xpodcasts, 'channel');
            $xchannel->addAttribute('id', $sub_id);
            $xchannel->addAttribute('url', $podcast->getFeedUrl());
            $xchannel->addAttribute('title', (string)$podcast->get_fullname());
            $xchannel->addAttribute('description', $podcast->get_description());
            if ($podcast->has_art()) {
                $xchannel->addAttribute('coverArt', $sub_id);
            }
            $xchannel->addAttribute('status', 'completed');
            if ($includeEpisodes) {
                $episodes = $podcast->getEpisodeIds();

                foreach ($episodes as $episode_id) {
                    $episode = new Podcast_Episode($episode_id);
                    self::_addPodcastEpisode($xchannel, $episode);
                }
            }
        }

        return $xml;
    }

    /**
     * addNewestPodcasts
     * @param Podcast_Episode[] $episodes
     */
    public static function addNewestPodcasts(SimpleXMLElement $xml, array $episodes): SimpleXMLElement
    {
        $xpodcasts = self::_addChildToResultXml($xml, 'newestPodcasts');
        foreach ($episodes as $episode) {
            self::_addPodcastEpisode($xpodcasts, $episode);
        }

        return $xml;
    }

    /**
     * addBookmarks
     * @param list<Bookmark> $bookmarks
     */
    public static function addBookmarks(SimpleXMLElement $xml, array $bookmarks): SimpleXMLElement
    {
        $xbookmarks = self::_addChildToResultXml($xml, 'bookmarks');
        foreach ($bookmarks as $bookmark) {
            self::_addBookmark($xbookmarks, $bookmark);
        }

        return $xml;
    }

    /**
     * addBookmark
     */
    private static function _addBookmark(SimpleXMLElement $xml, Bookmark $bookmark): void
    {
        $xbookmark = self::_addChildToResultXml($xml, 'bookmark');
        $xbookmark->addAttribute('position', (string)$bookmark->position);
        $xbookmark->addAttribute('username', $bookmark->getUserName());
        $xbookmark->addAttribute('comment', (string)$bookmark->comment);
        $xbookmark->addAttribute('created', date("c", (int)$bookmark->creation_date));
        $xbookmark->addAttribute('changed', date("c", (int)$bookmark->update_date));
        if ($bookmark->object_type == "song") {
            // TODO addEntry
            self::addSong($xbookmark, $bookmark->object_id, 'entry');
        } elseif ($bookmark->object_type == "video") {
            // TODO addEntry
            self::_addVideo($xbookmark, new Video($bookmark->object_id), 'entry');
        } elseif ($bookmark->object_type == "podcast_episode") {
            // TODO addEntry
            self::_addPodcastEpisode($xbookmark, new Podcast_Episode($bookmark->object_id), 'entry');
        }
    }

    /**
     * addPodcastEpisode
     */
    private static function _addPodcastEpisode(SimpleXMLElement $xml, Podcast_Episode $episode, string $elementName = 'episode'): void
    {
        $sub_id    = OpenSubsonic_Api::getPodcastEpisodeSubId($episode->id);
        $subParent = OpenSubsonic_Api::getPodcastSubId($episode->podcast);
        $xepisode  = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xepisode->addAttribute('id', $sub_id);
        $xepisode->addAttribute('channelId', $subParent);
        $xepisode->addAttribute('title', (string)$episode->get_fullname());
        $xepisode->addAttribute('album', $episode->getPodcastName());
        $xepisode->addAttribute('description', $episode->get_description());
        $xepisode->addAttribute('duration', (string)$episode->time);
        $xepisode->addAttribute('genre', "Podcast");
        $xepisode->addAttribute('isDir', "false");
        $xepisode->addAttribute('publishDate', $episode->getPubDate()->format(DATE_ATOM));
        $xepisode->addAttribute('status', (string)$episode->state);
        $xepisode->addAttribute('parent', $subParent);
        if ($episode->has_art()) {
            $xepisode->addAttribute('coverArt', $subParent);
        }

        self::_setIfStarred($xepisode, 'podcast_episode', $episode->id);

        if ($episode->file) {
            $xepisode->addAttribute('streamId', $sub_id);
            $xepisode->addAttribute('size', (string)$episode->size);
            $xepisode->addAttribute('suffix', (string)$episode->type);
            $xepisode->addAttribute('contentType', (string)$episode->mime);
            // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
            $path = basename($episode->file);
            $xepisode->addAttribute('path', (string)$path);
        }
    }

    /**
     * addChatMessages
     * @param int[] $messages
     */
    public static function addChatMessages(SimpleXMLElement $xml, array $messages): SimpleXMLElement
    {
        $xmessages = self::_addChildToResultXml($xml, 'chatMessages');
        if (empty($messages)) {
            return $xml;
        }

        foreach ($messages as $message) {
            $chat = new PrivateMsg($message);
            self::_addMessage($xmessages, $chat);
        }

        return $xml;
    }

    /**
     * addScanStatus
     */
    public static function addScanStatus(SimpleXMLElement $xml, User $user): SimpleXMLElement
    {
        $counts = Catalog::get_server_counts($user->id ?? 0);
        $count  = $counts['artist'] + $counts['album'] + $counts['song'] + $counts['podcast_episode'];
        $xscan  = self::_addChildToResultXml($xml, htmlspecialchars('scanStatus'));
        $xscan->addAttribute('scanning', "false");
        $xscan->addAttribute('count', (string)$count);

        return $xml;
    }

    /**
     * addTokenInfo
     */
    public static function addTokenInfo(SimpleXMLElement $xml, User $user): SimpleXMLElement
    {
        $xscan  = self::_addChildToResultXml($xml, htmlspecialchars('tokenInfo'));
        $xscan->addAttribute('username', (string)$user->username);

        return $xml;
    }

    /**
     * addMessage
     */
    private static function _addMessage(SimpleXMLElement $xml, PrivateMsg $message): void
    {
        $user      = new User($message->getSenderUserId());
        $xbookmark = self::_addChildToResultXml($xml, 'chatMessage');
        if ($user->fullname_public) {
            $xbookmark->addAttribute('username', (string)$user->fullname);
        } else {
            $xbookmark->addAttribute('username', (string)$user->username);
        }
        $xbookmark->addAttribute('time', (string)($message->getCreationDate() * 1000));
        $xbookmark->addAttribute('message', (string)$message->getMessage());
    }

    /**
     * _setIfStarred
     */
    private static function _setIfStarred(SimpleXMLElement $xml, string $objectType, int $object_id): void
    {
        if (InterfaceImplementationChecker::is_library_item($objectType)) {
            if (AmpConfig::get('ratings')) {
                $starred = new Userflag($object_id, $objectType);
                $result  = $starred->get_flag(null, true);
                if (is_array($result)) {
                    $xml->addAttribute('starred', date("Y-m-d\TH:i:s\Z", $result[1]));
                }
            }
        }
    }

    /**
     * Adds a child to an existing result xml structure
     */
    private static function _addChildToResultXml(SimpleXMLElement $xml, string $qualifiedName, ?string $value = null): SimpleXMLElement
    {
        /** @var SimpleXMLElement $child */
        $child = $xml->addChild($qualifiedName, $value);

        return $child;
    }

    /**
     * @deprecated
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }
}
