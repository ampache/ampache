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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Catalog;
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
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use DateTime;
use DateTimeZone;
use SimpleXMLElement;

/**
 * Subsonic_Xml_Data Class
 *
 * This class takes care of all of the xml document stuff for SubSonic Responses
 */
class Subsonic_Xml_Data
{
    public const API_VERSION = "1.16.1";

    public const SSERROR_GENERIC               = 0;
    public const SSERROR_MISSINGPARAM          = 10;
    public const SSERROR_APIVERSION_CLIENT     = 20;
    public const SSERROR_APIVERSION_SERVER     = 30;
    public const SSERROR_BADAUTH               = 40;
    public const SSERROR_TOKENAUTHNOTSUPPORTED = 41;
    public const SSERROR_UNAUTHORIZED          = 50;
    public const SSERROR_TRIAL                 = 60;
    public const SSERROR_DATA_NOTFOUND         = 70;

    // Ampache doesn't have a global unique id but each items are unique per category. We use id pattern to identify item category.
    public const AMPACHEID_ARTIST    = 100000000;
    public const AMPACHEID_ALBUM     = 200000000;
    public const AMPACHEID_SONG      = 300000000;
    public const AMPACHEID_SMARTPL   = 400000000;
    public const AMPACHEID_VIDEO     = 500000000;
    public const AMPACHEID_PODCAST   = 600000000;
    public const AMPACHEID_PODCASTEP = 700000000;
    public const AMPACHEID_PLAYLIST  = 800000000;

    public static $enable_json_checks = false;

    /**
     * addSubsonicResponse
     * @param string $function
     * @return SimpleXMLElement
     */
    public static function addSubsonicResponse($function): SimpleXMLElement
    {
        return self::_createSuccessResponse($function);
    }

    /**
     * addError
     * Add a failed subsonic-response with error information.
     *
     * @param int $code Error code
     * @param string $function
     * @return SimpleXMLElement
     */
    public static function addError($code, $function): SimpleXMLElement
    {
        $xml  = self::_createFailedResponse($function);
        /** @var SimpleXMLElement $xerr */
        $xerr = self::addChildToResultXml($xml, 'error');
        $xerr->addAttribute('code', (string)$code);

        $message = "A generic error.";
        switch ($code) {
            case self::SSERROR_MISSINGPARAM:
                $message = "Required parameter is missing.";
                break;
            case self::SSERROR_APIVERSION_CLIENT:
                $message = "Incompatible Subsonic REST protocol version. Client must upgrade.";
                break;
            case self::SSERROR_APIVERSION_SERVER:
                $message = "Incompatible Subsonic REST protocol version. Server must upgrade.";
                break;
            case self::SSERROR_BADAUTH:
                $message = "Wrong username or password.";
                break;
            case self::SSERROR_TOKENAUTHNOTSUPPORTED:
                $message = "Token authentication not supported.";
                break;
            case self::SSERROR_UNAUTHORIZED:
                $message = "User is not authorized for the given operation.";
                break;
            case self::SSERROR_TRIAL:
                $message = "The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details.";
                break;
            case self::SSERROR_DATA_NOTFOUND:
                $message = "The requested data was not found.";
                break;
        }
        $xerr->addAttribute('message', (string)$message);

        return $xml;
    }

    /**
     * addLicense
     * @param SimpleXMLElement $xml
     */
    public static function addLicense($xml): void
    {
        $xlic = self::addChildToResultXml($xml, 'license');
        $xlic->addAttribute('valid', 'true');
        $xlic->addAttribute('email', 'webmaster@ampache.org');
    }

    /**
     * addMusicFolders
     * @param SimpleXMLElement $xml
     * @param int[] $catalogs
     */
    public static function addMusicFolders($xml, $catalogs): void
    {
        $xfolders = self::addChildToResultXml($xml, 'musicFolders');
        foreach ($catalogs as $folder_id) {
            $catalog = Catalog::create_from_id($folder_id);
            if ($catalog === null) {
                break;
            }
            $xfolder = self::addChildToResultXml($xfolders, 'musicFolder');
            $xfolder->addAttribute('id', (string)$folder_id);
            $xfolder->addAttribute('name', (string)$catalog->name);
        }
    }

    /**
     * addIndexes
     * @param SimpleXMLElement $xml
     * @param array $artists
     * @param int|null $lastModified
     */
    public static function addIndexes($xml, $artists, $lastModified = 0): void
    {
        $xindexes = self::addChildToResultXml($xml, 'indexes');
        $xindexes->addAttribute('lastModified', number_format($lastModified * 1000, 0, '.', ''));
        self::addIgnoredArticles($xindexes);
        self::addIndex($xindexes, $artists);
    }

    /**
     * addIgnoredArticles
     * @param SimpleXMLElement $xml
     */
    private static function addIgnoredArticles($xml): void
    {
        $ignoredArticles = AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La');
        if (!empty($ignoredArticles)) {
            $ignoredArticles = str_replace("|", " ", $ignoredArticles);
            $xml->addAttribute('ignoredArticles', (string)$ignoredArticles);
        }
    }

    /**
     * addIndex
     * @param SimpleXMLElement $xml
     * @param array $artists
     */
    private static function addIndex($xml, $artists): void
    {
        $xlastcat     = null;
        $sharpartists = array();
        $xlastletter  = '';
        foreach ($artists as $artist) {
            if (strlen((string)$artist['name']) > 0) {
                $letter = strtoupper((string)$artist['name'][0]);
                if ($letter == "X" || $letter == "Y" || $letter == "Z") {
                    $letter = "X-Z";
                } else {
                    if (!preg_match("/^[A-W]$/", $letter)) {
                        $sharpartists[] = $artist;
                        continue;
                    }
                }

                if ($letter != $xlastletter) {
                    $xlastletter = $letter;
                    $xlastcat    = self::addChildToResultXml($xml, 'index');
                    $xlastcat->addAttribute('name', (string)$xlastletter);
                }
            }

            if ($xlastcat != null) {
                self::addArtistArray($xlastcat, $artist);
            }
        }

        // Always add # index at the end
        if (count($sharpartists) > 0) {
            $xsharpcat = self::addChildToResultXml($xml, 'index');
            $xsharpcat->addAttribute('name', '#');

            foreach ($sharpartists as $artist) {
                self::addArtistArray($xsharpcat, $artist);
            }
        }
    }

    /**
     * addArtists
     * @param SimpleXMLElement $xml
     * @param array $artists
     */
    public static function addArtists($xml, $artists): void
    {
        $xartists = self::addChildToResultXml($xml, 'artists');
        self::addIgnoredArticles($xartists);
        self::addIndex($xartists, $artists);
    }

    /**
     * addArtist
     * @param SimpleXMLElement $xml
     * @param Artist $artist
     * @param bool $extra
     * @param bool $albums
     * @param bool $albumsSet
     */
    public static function addArtist($xml, $artist, $extra = false, $albums = false, $albumsSet = false): void
    {
        if ($artist->isNew()) {
            return;
        }
        $artist->format();
        $sub_id  = (string)self::_getArtistId($artist->id);
        $xartist = self::addChildToResultXml($xml, 'artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', (string)self::_checkName($artist->get_fullname()));
        $allalbums = array();
        if (($extra && !$albumsSet) || $albums) {
            $allalbums = static::getAlbumRepository()->getAlbumByArtist($artist->id);
        }

        if ($extra) {
            if ($artist->has_art()) {
                $xartist->addAttribute('coverArt', 'ar-' . $sub_id);
            }
            if ($albumsSet) {
                $xartist->addAttribute('albumCount', (string)$artist->album_count);
            } else {
                $xartist->addAttribute('albumCount', (string)count($allalbums));
            }
            self::_setIfStarred($xartist, 'artist', $artist->id);
        }
        if ($albums) {
            foreach ($allalbums as $album_id) {
                $album = new Album($album_id);
                self::addAlbum($xartist, $album);
            }
        }
    }

    /**
     * addChildArray
     * @param SimpleXMLElement $xml
     * @param array $child
     */
    private static function addChildArray($xml, $child): void
    {
        $sub_id = (string)self::_getArtistId($child['id']);
        $xchild = self::addChildToResultXml($xml, 'child');
        $xchild->addAttribute('id', $sub_id);
        if (array_key_exists('catalog_id', $child)) {
            $xchild->addAttribute('parent', $child['catalog_id']);
        }
        $xchild->addAttribute('isDir', 'true');
        $xchild->addAttribute('title', (string)self::_checkName($child['f_name']));
        $xchild->addAttribute('artist', (string)self::_checkName($child['f_name']));
        if (array_key_exists('has_art', $child) && !empty($child['has_art'])) {
            $xchild->addAttribute('coverArt', 'ar-' . $sub_id);
        }
    }

    /**
     * addArtistArray
     * @param SimpleXMLElement $xml
     * @param array $artist
     */
    private static function addArtistArray($xml, $artist): void
    {
        $sub_id  = (string)self::_getArtistId($artist['id']);
        $xartist = self::addChildToResultXml($xml, 'artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', (string)self::_checkName($artist['f_name']));
        if (array_key_exists('has_art', $artist) && !empty($artist['has_art'])) {
            $xartist->addAttribute('coverArt', 'ar-' . $sub_id);
        }
        $xartist->addAttribute('albumCount', (string)$artist['album_count']);
        self::_setIfStarred($xartist, 'artist', $artist['id']);
    }

    /**
     * addAlbumList
     * @param SimpleXMLElement $xml
     * @param array $albums
     */
    public static function addAlbumList($xml, $albums): void
    {
        $xlist = self::addChildToResultXml($xml, htmlspecialchars('albumList'));
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbum($xlist, $album);
        }
    }

    /**
     * addAlbumList2
     * @param SimpleXMLElement $xml
     * @param array $albums
     */
    public static function addAlbumList2($xml, $albums): void
    {
        $xlist = self::addChildToResultXml($xml, htmlspecialchars('albumList2'));
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbum($xlist, $album);
        }
    }

    /**
     * addAlbum
     * @param SimpleXMLElement $xml
     * @param Album $album
     * @param bool $songs
     * @param string $elementName
     */
    public static function addAlbum($xml, $album, $songs = false, $elementName = "album"): void
    {
        if ($album->isNew()) {
            return;
        }
        $album->format();
        $sub_id = (string)self::_getAlbumId($album->id);
        $xalbum = self::addChildToResultXml($xml, htmlspecialchars($elementName));
        $xalbum->addAttribute('id', $sub_id);
        if ($album->album_artist) {
            $xalbum->addAttribute('parent', (string)self::_getArtistId($album->album_artist));
        }
        $f_name = (string)self::_checkName($album->get_fullname());
        $xalbum->addAttribute('album', $f_name);
        $xalbum->addAttribute('title', $f_name);
        $xalbum->addAttribute('name', $f_name);
        $xalbum->addAttribute('isDir', 'true');
        //$xalbum->addAttribute('discNumber', (string)$album->disk);
        if ($album->has_art()) {
            $xalbum->addAttribute('coverArt', 'al-' . $sub_id);
        }
        $xalbum->addAttribute('songCount', (string) $album->song_count);
        $xalbum->addAttribute('created', date("c", (int)$album->addition_time));
        $xalbum->addAttribute('duration', (string) $album->total_duration);
        $xalbum->addAttribute('playCount', (string)$album->total_count);
        if ($album->album_artist) {
            $xalbum->addAttribute('artistId', (string)self::_getArtistId($album->album_artist));
        }
        $xalbum->addAttribute('artist', (string) self::_checkName($album->get_artist_fullname()));
        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');
        $year          = ($original_year && $album->original_year)
            ? $album->original_year
            : $album->year;
        if ($year > 0) {
            $xalbum->addAttribute('year', (string)$year);
        }
        if (count($album->tags) > 0) {
            $tag_values = array_values($album->tags);
            $tag        = array_shift($tag_values);
            $xalbum->addAttribute('genre', (string)$tag['name']);
        }

        $rating      = new Rating($album->id, "album");
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
            $media_ids = static::getAlbumRepository()->getSongs($album->id);
            foreach ($media_ids as $song_id) {
                self::addSong($xalbum, $song_id);
            }
        }
    }

    /**
     * addSong
     * @param SimpleXMLElement $xml
     * @param int $song_id
     * @param string $elementName
     * @return SimpleXMLElement
     */
    public static function addSong($xml, $song_id, $elementName = 'song'): SimpleXMLElement
    {
        $song = new Song($song_id);
        if ($song->isNew()) {
            return $xml;
        }
        $catalogData = self::_getCatalogData($song->getCatalogId(), (string)$song->file);

        // Don't create entries for disabled songs
        if ($song->enabled) {
            $sub_id    = (string)self::_getSongId($song->id);
            $subParent = (string)self::_getAlbumId($song->album);
            $xsong     = self::addChildToResultXml($xml, htmlspecialchars($elementName));
            $xsong->addAttribute('id', $sub_id);
            $xsong->addAttribute('parent', $subParent);
            //$xsong->addAttribute('created', );
            $xsong->addAttribute('title', (string)self::_checkName($song->title));
            $xsong->addAttribute('isDir', 'false');
            $xsong->addAttribute('isVideo', 'false');
            $xsong->addAttribute('type', 'music');
            $xsong->addAttribute('albumId', $subParent);
            $xsong->addAttribute('album', (string)self::_checkName($song->get_album_fullname()));
            // $artist = new Artist($song->artist);
            // $artist->format();
            $xsong->addAttribute('artistId', ($song->artist) ? (string)self::_getArtistId($song->artist) : '');
            $xsong->addAttribute('artist', (string)self::_checkName($song->get_artist_fullname()));
            if ($song->has_art()) {
                $art_id = (AmpConfig::get('show_song_art', false)) ? $sub_id : $subParent;
                $xsong->addAttribute('coverArt', $art_id);
            }
            $xsong->addAttribute('duration', (string)$song->time);
            $xsong->addAttribute('bitRate', (string)((int)($song->bitrate / 1024)));
            $rating      = new Rating($song->id, "song");
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
            $tags = Tag::get_object_tags('song', (int)$song->id);
            if (is_array($tags) && count($tags) > 0) {
                $xsong->addAttribute('genre', (string)$tags[0]['name']);
            }
            $xsong->addAttribute('size', (string)$song->size);
            $disk = $song->disk;
            if ($disk > 0) {
                $xsong->addAttribute('discNumber', (string)$disk);
            }
            $xsong->addAttribute('suffix', (string)$song->type);
            $xsong->addAttribute('contentType', (string)$song->mime);
            $file_path = (string)$catalogData['path'];
            if (AmpConfig::get('transcode') != 'never') {
                $cache_path     = (string)AmpConfig::get('cache_path', '');
                $cache_target   = (string)AmpConfig::get('cache_target', '');
                $file_target    = Catalog::get_cache_path($song->getId(), $song->getCatalogId(), $cache_path, $cache_target);
                $transcode_type = ($file_target !== null && is_file($file_target))
                    ? $cache_target
                    : Stream::get_transcode_format($song->type, null, 'api');

                if (!empty($transcode_type) && $song->type !== $transcode_type) {
                    // Return a file path relative to the catalog root path
                    $xsong->addAttribute('path', (string)preg_replace('"\.' . $song->type . '$"', '.' . $transcode_type, $file_path));
                    // Set transcoding information
                    $xsong->addAttribute('transcodedSuffix', $transcode_type);
                    $xsong->addAttribute('transcodedContentType', Song::type_to_mime($transcode_type));

                    return $xsong;
                }
            }
            $xsong->addAttribute('path', $file_path);

            return $xsong;
        }

        return $xml;
    }

    /**
     * addDirectory will create the directory element based on the type
     * @param SimpleXMLElement $xml
     * @param string $sub_id
     * @param string $dirType
     */
    public static function addDirectory($xml, $sub_id, $dirType): void
    {
        switch ($dirType) {
            case 'artist':
                self::addDirectory_Artist($xml, $sub_id);
                break;
            case 'album':
                self::addDirectory_Album($xml, $sub_id);
                break;
            case 'catalog':
                self::addDirectory_Catalog($xml, $sub_id);
                break;
        }
    }

    /**
     * addDirectory_Artist for subsonic artist id
     * @param SimpleXMLElement $xml
     * @param string $sub_id
     */
    private static function addDirectory_Artist($xml, $sub_id): void
    {
        $artist_id = self::_getAmpacheId($sub_id);
        $data      = Artist::get_id_array($artist_id);
        $xdir      = self::addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', (string)$sub_id);
        if (array_key_exists('catalog_id', $data)) {
            $xdir->addAttribute('parent', (string)$data['catalog_id']);
        }
        $xdir->addAttribute('name', (string)$data['f_name']);
        self::_setIfStarred($xdir, 'artist', $artist_id);
        $allalbums = static::getAlbumRepository()->getAlbumByArtist($artist_id);
        foreach ($allalbums as $album_id) {
            $album = new Album($album_id);
            // TODO addChild || use addChildArray
            self::addAlbum($xdir, $album, false, "child");
        }
    }

    /**
     * addDirectory_Album for subsonic album id
     * @param SimpleXMLElement $xml
     * @param string $album_id
     */
    private static function addDirectory_Album($xml, $album_id): void
    {
        $album = new Album(self::_getAmpacheId($album_id));
        $album->format();
        /** @var SimpleXMLElement $xdir */
        $xdir = self::addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', (string)$album_id);
        if ($album->album_artist) {
            $xdir->addAttribute('parent', (string)self::_getArtistId($album->album_artist));
        } else {
            $xdir->addAttribute('parent', (string)$album->catalog);
        }
        $xdir->addAttribute('name', (string)self::_checkName($album->get_fullname()));
        self::_setIfStarred($xdir, 'album', $album->id);

        $media_ids = static::getAlbumRepository()->getSongs($album->id);
        foreach ($media_ids as $song_id) {
            // TODO addChild || use addChildArray
            self::addSong($xdir, $song_id, "child");
        }
    }

    /**
     * addDirectory_Catalog for subsonic artist id
     * @param SimpleXMLElement $xml
     * @param string $catalog_id
     */
    private static function addDirectory_Catalog($xml, $catalog_id): void
    {
        $catalog = Catalog::create_from_id((int)$catalog_id);
        if ($catalog === null) {
            return;
        }
        $xdir = self::addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', (string)$catalog_id);
        $xdir->addAttribute('name', (string)$catalog->name);
        $allartists = Catalog::get_artist_arrays(array($catalog_id));
        foreach ($allartists as $artist) {
            self::addChildArray($xdir, $artist);
        }
    }

    /**
     * addGenres
     * @param SimpleXMLElement $xml
     * @param array $tags
     */
    public static function addGenres($xml, $tags): void
    {
        $xgenres = self::addChildToResultXml($xml, 'genres');

        foreach ($tags as $tag) {
            $otag   = new Tag($tag['id']);
            $counts = $otag->count();

            $xgenre = self::addChildToResultXml($xgenres, 'genre', htmlspecialchars((string)$otag->name));
            $xgenre->addAttribute('songCount', (string)($counts['song'] ?? 0));
            $xgenre->addAttribute('albumCount', (string)($counts['album'] ?? 0));
        }
    }

    /**
     * addVideos
     * @param SimpleXMLElement $xml
     * @param Video[] $videos
     */
    public static function addVideos($xml, $videos): void
    {
        $xvideos = self::addChildToResultXml($xml, 'videos');
        foreach ($videos as $video) {
            $video->format();
            self::addVideo($xvideos, $video);
        }
    }

    /**
     * addVideo
     * @param SimpleXMLElement $xml
     * @param Video $video
     * @param string $elementName
     */
    private static function addVideo($xml, $video, $elementName = 'video'): void
    {
        $sub_id = (string)self::_getVideoId($video->id);
        $xvideo = self::addChildToResultXml($xml, htmlspecialchars($elementName));
        $xvideo->addAttribute('id', $sub_id);
        $xvideo->addAttribute('title', (string)$video->f_full_title);
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
        if (is_array($tags) && count($tags) > 0) {
            $xvideo->addAttribute('genre', (string)$tags[0]['name']);
        }
        $xvideo->addAttribute('size', (string)$video->size);
        $xvideo->addAttribute('suffix', (string)$video->type);
        $xvideo->addAttribute('contentType', (string)$video->mime);
        // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
        $path = basename($video->file);
        $xvideo->addAttribute('path', (string)$path);

        self::_setIfStarred($xvideo, 'video', $video->id);
        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode');
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
     * @param SimpleXMLElement $xml
     * @param int $video_id
     */
    public static function addVideoInfo($xml, $video_id): void
    {
        $xvideoinfo = self::addChildToResultXml($xml, 'videoinfo');
        $xvideoinfo->addAttribute('id', (string)$video_id);
    }

    /**
     * addPlaylists
     * @param SimpleXMLElement $xml
     * @param int $user_id
     * @param array $playlists
     * @param array $smartplaylists
     * @param bool $hide_dupe_searches
     */
    public static function addPlaylists($xml, $user_id, $playlists, $smartplaylists = array(), $hide_dupe_searches = false): void
    {
        $playlist_names = array();
        $xplaylists     = self::addChildToResultXml($xml, 'playlists');
        foreach ($playlists as $plist_id) {
            $playlist = new Playlist($plist_id);
            if ($hide_dupe_searches && $playlist->user == $user_id) {
                $playlist_names[] = $playlist->name;
            }
            self::addPlaylist($xplaylists, $playlist);
        }
        foreach ($smartplaylists as $plist_id) {
            $playlist = new Search((int)str_replace('smart_', '', (string)$plist_id), 'song');
            if ($hide_dupe_searches && $playlist->user == $user_id && in_array($playlist->name, $playlist_names)) {
                continue;
            }
            self::addPlaylist($xplaylists, $playlist);
        }
    }

    /**
     * addPlaylist
     * @param SimpleXMLElement $xml
     * @param Playlist|Search $playlist
     * @param bool $songs
     */
    public static function addPlaylist($xml, $playlist, $songs = false): void
    {
        if ($playlist instanceof Playlist) {
            self::addPlaylist_Playlist($xml, $playlist, $songs);
        }
        if ($playlist instanceof Search) {
            self::addPlaylist_Search($xml, $playlist, $songs);
        }
    }

    /**
     * addPlaylist_Playlist
     * @param SimpleXMLElement $xml
     * @param Playlist $playlist
     * @param bool $songs
     */
    private static function addPlaylist_Playlist($xml, $playlist, $songs = false): void
    {
        $sub_id    = (string)self::_getPlaylistId($playlist->id);
        $songcount = $playlist->get_media_count('song');
        $duration  = ($songcount > 0) ? $playlist->get_total_duration() : 0;
        $xplaylist = self::addChildToResultXml($xml, 'playlist');
        $xplaylist->addAttribute('id', $sub_id);
        $xplaylist->addAttribute('name', (string)self::_checkName($playlist->get_fullname()));
        $xplaylist->addAttribute('owner', (string)$playlist->username);
        $xplaylist->addAttribute('public', ($playlist->type != "private") ? "true" : "false");
        $xplaylist->addAttribute('songCount', (string)$songcount);
        $xplaylist->addAttribute('duration', (string)$duration);
        $xplaylist->addAttribute('created', date("c", (int)$playlist->date));
        $xplaylist->addAttribute('changed', date("c", (int)$playlist->last_update));
        if ($playlist->has_art()) {
            $xplaylist->addAttribute('coverArt', $sub_id);
        }

        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $song_id) {
                // TODO addEntry
                self::addSong($xplaylist, $song_id, "entry");
            }
        }
    }

    /**
     * addPlaylist_Search
     * @param SimpleXMLElement $xml
     * @param Search $search
     * @param bool $songs
     */
    private static function addPlaylist_Search($xml, $search, $songs = false): void
    {
        $sub_id    = (string) self::_getSmartPlaylistId($search->id);
        $xplaylist = self::addChildToResultXml($xml, 'playlist');
        $xplaylist->addAttribute('id', $sub_id);
        $xplaylist->addAttribute('name', (string) self::_checkName($search->get_fullname()));
        $xplaylist->addAttribute('owner', (string)$search->username);
        $xplaylist->addAttribute('public', ($search->type != "private") ? "true" : "false");
        $xplaylist->addAttribute('created', date("c", (int)$search->date));
        $xplaylist->addAttribute('changed', date("c", time()));

        if ($songs) {
            $allitems = $search->get_items();
            $xplaylist->addAttribute('songCount', (string)count($allitems));
            $duration = (count($allitems) > 0) ? Search::get_total_duration($allitems) : 0;
            $xplaylist->addAttribute('duration', (string)$duration);
            $xplaylist->addAttribute('coverArt', $sub_id);
            foreach ($allitems as $item) {
                // TODO addEntry
                self::addSong($xplaylist, (int)$item['object_id'], "entry");
            }
        } else {
            $xplaylist->addAttribute('songCount', (string)$search->last_count);
            $xplaylist->addAttribute('duration', (string)$search->last_duration);
            $xplaylist->addAttribute('coverArt', $sub_id);
        }
    }

    /**
     * addPlayQueue
     * current="133" position="45000" username="admin" changed="2015-02-18T15:22:22.825Z" changedBy="android"
     * @param SimpleXMLElement $xml
     * @param User_Playlist $playQueue
     * @param string $username
     */
    public static function addPlayQueue($xml, $playQueue, $username): void
    {
        $items = $playQueue->get_items();
        if (!empty($items)) {
            $current   = $playQueue->get_current_object();
            $play_time = date("Y-m-d H:i:s", $playQueue->get_time());
            $date      = new DateTime($play_time);
            $date->setTimezone(new DateTimeZone('UTC'));
            $changedBy  = $playQueue->client ?? '';
            $xplayqueue = self::addChildToResultXml($xml, 'playQueue');
            $xplayqueue->addAttribute('current', (string)self::_getSongId($current['object_id']));
            $xplayqueue->addAttribute('position', (string)($current['current_time'] * 1000));
            $xplayqueue->addAttribute('username', (string)$username);
            $xplayqueue->addAttribute('changed', $date->format("c"));
            $xplayqueue->addAttribute('changedBy', (string)$changedBy);

            foreach ($items as $row) {
                // TODO addEntry
                self::addSong($xplayqueue, (int)$row['object_id'], "entry");
            }
        }
    }

    /**
     * addRandomSongs
     * @param SimpleXMLElement $xml
     * @param array $songs
     */
    public static function addRandomSongs($xml, $songs): void
    {
        $xsongs = self::addChildToResultXml($xml, 'randomSongs');
        foreach ($songs as $song_id) {
            self::addSong($xsongs, $song_id);
        }
    }

    /**
     * addSongsByGenre
     * @param SimpleXMLElement $xml
     * @param array $songs
     */
    public static function addSongsByGenre($xml, $songs): void
    {
        $xsongs = self::addChildToResultXml($xml, 'songsByGenre');
        foreach ($songs as $song_id) {
            self::addSong($xsongs, $song_id);
        }
    }

    /**
     * addTopSongs
     * @param SimpleXMLElement $xml
     * @param array $songs
     */
    public static function addTopSongs($xml, $songs): void
    {
        $xsongs = self::addChildToResultXml($xml, 'topSongs');
        foreach ($songs as $song_id) {
            self::addSong($xsongs, $song_id);
        }
    }

    /**
     * addNowPlaying
     * @param SimpleXMLElement $xml
     * @param array $data
     */
    public static function addNowPlaying($xml, $data): void
    {
        $xplaynow = self::addChildToResultXml($xml, 'nowPlaying');
        foreach ($data as $row) {
            // TODO addEntry
            $track = self::addSong($xplaynow, $row['media']->getId(), "entry");
            if ($track !== null) {
                $track->addAttribute('username', (string)$row['client']->username);
                $track->addAttribute('minutesAgo', (string)(abs((time() - ($row['expire'] - $row['media']->time)) / 60)));
                $track->addAttribute('playerId', (string)$row['agent']);
            }
        }
    }

    /**
     * addSearchResult2
     * @param SimpleXMLElement $xml
     * @param array $artists
     * @param array $albums
     * @param array $songs
     */
    public static function addSearchResult2($xml, $artists, $albums, $songs): void
    {
        $xresult = self::addChildToResultXml($xml, htmlspecialchars('searchResult2'));
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
    }

    /**
     * addSearchResult3
     * @param SimpleXMLElement $xml
     * @param array $artists
     * @param array $albums
     * @param array $songs
     */
    public static function addSearchResult3($xml, $artists, $albums, $songs): void
    {
        $xresult = self::addChildToResultXml($xml, htmlspecialchars('searchResult3'));
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
    }

    /**
     * addStarred
     * @param SimpleXMLElement $xml
     * @param array $artists
     * @param array $albums
     * @param array $songs
     */
    public static function addStarred($xml, $artists, $albums, $songs): void
    {
        $xstarred = self::addChildToResultXml($xml, htmlspecialchars('starred'));

        foreach ($artists as $artist_id) {
            $artist = new Artist((int) $artist_id);
            self::addArtist($xstarred, $artist);
        }

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbum($xstarred, $album);
        }

        foreach ($songs as $song_id) {
            self::addSong($xstarred, $song_id);
        }
    }

    /**
     * addStarred2
     * @param SimpleXMLElement $xml
     * @param array $artists
     * @param array $albums
     * @param array $songs
     */
    public static function addStarred2($xml, $artists, $albums, $songs): void
    {
        $xstarred = self::addChildToResultXml($xml, htmlspecialchars('starred2'));

        foreach ($artists as $artist_id) {
            $artist = new Artist((int) $artist_id);
            self::addArtist($xstarred, $artist);
        }

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbum($xstarred, $album);
        }

        foreach ($songs as $song_id) {
            self::addSong($xstarred, $song_id);
        }
    }

    /**
     * addUser
     * @param SimpleXMLElement $xml
     * @param User $user
     */
    public static function addUser($xml, $user): void
    {
        $xuser = self::addChildToResultXml($xml, 'user');
        $xuser->addAttribute('username', (string)$user->username);
        $xuser->addAttribute('email', (string)$user->email);
        $xuser->addAttribute('scrobblingEnabled', 'true');
        $isManager = ($user->access >= 75);
        $isAdmin   = ($user->access === 100);
        $xuser->addAttribute('adminRole', $isAdmin ? 'true' : 'false');
        $xuser->addAttribute('settingsRole', 'true');
        $xuser->addAttribute('downloadRole', Preference::get_by_user($user->id, 'download') ? 'true' : 'false');
        $xuser->addAttribute('playlistRole', 'true');
        $xuser->addAttribute('coverArtRole', $isManager ? 'true' : 'false');
        $xuser->addAttribute('commentRole', (AmpConfig::get('social')) ? 'true' : 'false');
        $xuser->addAttribute('podcastRole', (AmpConfig::get('podcast')) ? 'true' : 'false');
        $xuser->addAttribute('streamRole', 'true');
        $xuser->addAttribute('jukeboxRole', (AmpConfig::get('allow_localplay_playback') && AmpConfig::get('localplay_controller') && Access::check('localplay', 5)) ? 'true' : 'false');
        $xuser->addAttribute('shareRole', Preference::get_by_user($user->id, 'share') ? 'true' : 'false');
        $xuser->addAttribute('videoConversionRole', 'false');
    }

    /**
     * addUsers
     * @param SimpleXMLElement $xml
     * @param array $users
     */
    public static function addUsers($xml, $users): void
    {
        $xusers = self::addChildToResultXml($xml, 'users');
        foreach ($users as $user_id) {
            $user = new User($user_id);
            if ($user->isNew() === false) {
                self::addUser($xusers, $user);
            }
        }
    }

    /**
     * addInternetRadioStations
     * @param SimpleXMLElement $xml
     * @param array $radios
     */
    public static function addInternetRadioStations($xml, $radios): void
    {
        $xradios = self::addChildToResultXml($xml, 'internetRadioStations');
        foreach ($radios as $radio_id) {
            $radio = new Live_Stream((int)$radio_id);
            self::addInternetRadioStation($xradios, $radio);
        }
    }

    /**
     * addInternetRadioStation
     * @param SimpleXMLElement $xml
     * @param Live_Stream $radio
     */
    private static function addInternetRadioStation($xml, $radio): void
    {
        $xradio = self::addChildToResultXml($xml, 'internetRadioStation');
        $xradio->addAttribute('id', (string)$radio->id);
        $xradio->addAttribute('name', (string)self::_checkName($radio->name));
        $xradio->addAttribute('streamUrl', (string)$radio->url);
        $xradio->addAttribute('homepageUrl', (string)$radio->site_url);
    }

    /**
     * addShares
     * @param SimpleXMLElement $xml
     * @param array $shares
     */
    public static function addShares($xml, $shares): void
    {
        $xshares = self::addChildToResultXml($xml, 'shares');
        foreach ($shares as $share_id) {
            $share = new Share((int)$share_id);
            // Don't add share with max counter already reached
            if ($share->max_counter == 0 || $share->counter < $share->max_counter) {
                self::addShare($xshares, $share);
            }
        }
    }

    /**
     * addShare
     * @param SimpleXMLElement $xml
     * @param Share $share
     */
    private static function addShare($xml, $share): void
    {
        $xshare = self::addChildToResultXml($xml, 'share');
        $xshare->addAttribute('id', (string)$share->id);
        $xshare->addAttribute('url', (string)$share->public_url);
        $xshare->addAttribute('description', (string)$share->description);
        $user = new User($share->user);
        $xshare->addAttribute('username', (string)$user->username);
        $xshare->addAttribute('created', date("c", (int)$share->creation_date));
        if ($share->lastvisit_date > 0) {
            $xshare->addAttribute('lastVisited', date("c", (int)$share->lastvisit_date));
        }
        if ($share->expire_days > 0) {
            $xshare->addAttribute('expires', date("c", (int)$share->creation_date + ($share->expire_days * 86400)));
        }
        $xshare->addAttribute('visitCount', (string)$share->counter);

        if ($share->object_type == 'song') {
            // TODO addEntry
            self::addSong($xshare, $share->object_id, "entry");
        } elseif ($share->object_type == 'playlist') {
            $playlist = new Playlist($share->object_id);
            $songs    = $playlist->get_songs();
            foreach ($songs as $song_id) {
                // TODO addEntry
                self::addSong($xshare, $song_id, "entry");
            }
        } elseif ($share->object_type == 'album') {
            $songs = static::getSongRepository()->getByAlbum($share->object_id);
            foreach ($songs as $song_id) {
                // TODO addEntry
                self::addSong($xshare, $song_id, "entry");
            }
        }
    }

    /**
     * addJukeboxPlaylist
     * @param SimpleXMLElement $xml
     * @param LocalPlay $localplay
     */
    public static function addJukeboxPlaylist($xml, LocalPlay $localplay): void
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
    }

    /**
     * addJukeboxStatus
     * @param SimpleXMLElement $xml
     * @param LocalPlay $localplay
     * @param string $elementName
     */
    public static function addJukeboxStatus($xml, LocalPlay $localplay, $elementName = 'jukeboxStatus'): SimpleXMLElement
    {
        $xjbox  = self::addChildToResultXml($xml, htmlspecialchars($elementName));
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
     * @param SimpleXMLElement $xml
     * @param string $artist
     * @param string $title
     * @param int $song_id
     */
    public static function addLyrics($xml, $artist, $title, $song_id): void
    {
        $song = new Song($song_id);
        $song->fill_ext_info('lyrics');
        $lyrics = $song->get_lyrics();

        if (!empty($lyrics) && $lyrics['text']) {
            $text    = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $lyrics['text']);
            $text    = preg_replace('/\\n\\n/i', "\n", $text);
            $text    = str_replace("\r", '', (string)$text);
            $xlyrics = self::addChildToResultXml($xml, 'lyrics', htmlspecialchars($text));
            if ($artist) {
                $xlyrics->addAttribute('artist', (string)$artist);
            }
            if ($title) {
                $xlyrics->addAttribute('title', (string)$title);
            }
        }
    }

    /**
     * addAlbumInfo
     * @param SimpleXMLElement $xml
     * @param array $info
     */
    public static function addAlbumInfo($xml, $info): void
    {
        $album = new Album((int) $info['id']);

        $xartist = self::addChildToResultXml($xml, htmlspecialchars('albumInfo'));
        $xartist->addChild('notes', htmlspecialchars(trim((string)$info['summary'])));
        $xartist->addChild('musicBrainzId', $album->mbid);
        //$xartist->addChild('lastFmUrl', "");
        $xartist->addChild('smallImageUrl', htmlentities($info['smallphoto']));
        $xartist->addChild('mediumImageUrl', htmlentities($info['mediumphoto']));
        $xartist->addChild('largeImageUrl', htmlentities($info['largephoto']));
    }

    /**
     * addArtistInfo
     * @param SimpleXMLElement $xml
     * @param array $info
     * @param array $similars
     */
    public static function addArtistInfo($xml, $info, $similars, $elementName = 'artistInfo'): void
    {
        $artist = new Artist((int) $info['id']);

        $xartist   = self::addChildToResultXml($xml, htmlspecialchars($elementName));
        $biography = trim((string)$info['summary']);
        if (!empty($biography)) {
            $xartist->addChild('biography', htmlspecialchars($biography));
        }
        $xartist->addChild('musicBrainzId', (string)$artist->mbid);
        //$xartist->addChild('lastFmUrl', "");
        $xartist->addChild('smallImageUrl', htmlentities($info['smallphoto']));
        $xartist->addChild('mediumImageUrl', htmlentities($info['mediumphoto']));
        $xartist->addChild('largeImageUrl', htmlentities($info['largephoto']));

        foreach ($similars as $similar) {
            $xsimilar = self::addChildToResultXml($xartist, 'similarArtist');
            $xsimilar->addAttribute('id', ($similar['id'] !== null ? (string)self::_getArtistId($similar['id']) : "-1"));
            $xsimilar->addAttribute('name', (string)self::_checkName($similar['name']));
        }
    }

    /**
     * addArtistInfo2
     * @param SimpleXMLElement $xml
     * @param array $info
     * @param array $similars
     */
    public static function addArtistInfo2($xml, $info, $similars): void
    {
        self::addArtistInfo($xml, $info, $similars, 'artistInfo2');
    }

    /**
     * addSimilarSongs
     * @param SimpleXMLElement $xml
     * @param array $similar_songs
     * @param string $child
     */
    public static function addSimilarSongs($xml, $similar_songs, $child): void
    {
        $xsimilar = self::addChildToResultXml($xml, htmlspecialchars($child));
        foreach ($similar_songs as $similar_song) {
            if ($similar_song['id'] !== null) {
                self::addSong($xsimilar, $similar_song['id']);
            }
        }
    }

    /**
     * addSimilarSongs2
     * @param SimpleXMLElement $xml
     * @param array $similar_songs
     * @param string $child
     */
    public static function addSimilarSongs2($xml, $similar_songs, $child): void
    {
        $xsimilar = self::addChildToResultXml($xml, htmlspecialchars($child));
        foreach ($similar_songs as $similar_song) {
            if ($similar_song['id'] !== null) {
                self::addSong($xsimilar, $similar_song['id']);
            }
        }
    }

    /**
     * addPodcasts
     * @param SimpleXMLElement $xml
     * @param Podcast[] $podcasts
     * @param bool $includeEpisodes
     */
    public static function addPodcasts($xml, $podcasts, $includeEpisodes = true): void
    {
        $podcastRepository = self::getPodcastRepository();

        $xpodcasts = self::addChildToResultXml($xml, 'podcasts');
        foreach ($podcasts as $podcast) {
            $podcast->format();
            $sub_id   = (string)self::_getPodcastId($podcast->getId());
            $xchannel = self::addChildToResultXml($xpodcasts, 'channel');
            $xchannel->addAttribute('id', $sub_id);
            $xchannel->addAttribute('url', $podcast->getFeedUrl());
            $xchannel->addAttribute('title', self::_checkName($podcast->get_fullname()));
            $xchannel->addAttribute('description', $podcast->get_description());
            if ($podcast->has_art()) {
                $xchannel->addAttribute('coverArt', 'pod-' . $sub_id);
            }
            $xchannel->addAttribute('status', 'completed');
            if ($includeEpisodes) {
                $episodes = $podcast->getEpisodeIds();

                foreach ($episodes as $episode_id) {
                    $episode = new Podcast_Episode($episode_id);
                    self::addPodcastEpisode($xchannel, $episode);
                }
            }
        }
    }

    /**
     * addNewestPodcasts
     * @param SimpleXMLElement $xml
     * @param Podcast_Episode[] $episodes
     */
    public static function addNewestPodcasts($xml, $episodes): void
    {
        $xpodcasts = self::addChildToResultXml($xml, 'newestPodcasts');
        foreach ($episodes as $episode) {
            $episode->format();
            self::addPodcastEpisode($xpodcasts, $episode);
        }
    }

    /**
     * addBookmarks
     * @param SimpleXMLElement $xml
     * @param Bookmark[] $bookmarks
     */
    public static function addBookmarks($xml, $bookmarks): void
    {
        $xbookmarks = self::addChildToResultXml($xml, 'bookmarks');
        foreach ($bookmarks as $bookmark) {
            self::addBookmark($xbookmarks, $bookmark);
        }
    }

    /**
     * addBookmark
     * @param SimpleXMLElement $xml
     * @param Bookmark $bookmark
     */
    private static function addBookmark($xml, $bookmark): void
    {
        $xbookmark = self::addChildToResultXml($xml, 'bookmark');
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
            self::addVideo($xbookmark, new Video($bookmark->object_id), 'entry');
        } elseif ($bookmark->object_type == "podcast_episode") {
            // TODO addEntry
            self::addPodcastEpisode($xbookmark, new Podcast_Episode($bookmark->object_id), 'entry');
        }
    }

    /**
     * addPodcastEpisode
     * @param SimpleXMLElement $xml
     * @param Podcast_Episode $episode
     * @param string $elementName
     */
    private static function addPodcastEpisode($xml, $episode, $elementName = 'episode'): void
    {
        $episode->format();
        $sub_id    = (string)self::_getPodcastEpisodeId($episode->id);
        $subParent = (string)self::_getPodcastId($episode->podcast);
        $xepisode  = self::addChildToResultXml($xml, htmlspecialchars($elementName));
        $xepisode->addAttribute('id', $sub_id);
        $xepisode->addAttribute('channelId', $subParent);
        $xepisode->addAttribute('title', (string)self::_checkName($episode->get_fullname()));
        $xepisode->addAttribute('album', (string)$episode->f_podcast);
        $xepisode->addAttribute('description', (string)self::_checkName($episode->f_description));
        $xepisode->addAttribute('duration', (string)$episode->time);
        $xepisode->addAttribute('genre', "Podcast");
        $xepisode->addAttribute('isDir', "false");
        $xepisode->addAttribute('publishDate', $episode->f_pubdate);
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
     * @param SimpleXMLElement $xml
     * @param int[] $messages
     */
    public static function addChatMessages($xml, $messages): void
    {
        $xmessages = self::addChildToResultXml($xml, 'chatMessages');
        if (empty($messages)) {
            return;
        }
        foreach ($messages as $message) {
            $chat = new PrivateMsg($message);
            self::addMessage($xmessages, $chat);
        }
    }

    /**
     * addScanStatus
     * @param SimpleXMLElement $xml
     * @param User $user
     */
    public static function addScanStatus($xml, $user): void
    {
        $counts = Catalog::get_server_counts($user->id ?? 0);
        $count  = $counts['artist'] + $counts['album'] + $counts['song'] + $counts['podcast_episode'];
        $xscan  = self::addChildToResultXml($xml, htmlspecialchars('scanStatus'));
        $xscan->addAttribute('scanning', "false");
        $xscan->addAttribute('count', (string)$count);
    }

    /**
     * addMessage
     * @param SimpleXMLElement $xml
     * @param PrivateMsg $message
     */
    private static function addMessage($xml, $message): void
    {
        $user      = new User($message->getSenderUserId());
        $xbookmark = self::addChildToResultXml($xml, 'chatMessage');
        if ($user->fullname_public) {
            $xbookmark->addAttribute('username', (string)$user->fullname);
        } else {
            $xbookmark->addAttribute('username', (string)$user->username);
        }
        $xbookmark->addAttribute('time', (string)($message->getCreationDate() * 1000));
        $xbookmark->addAttribute('message', (string)$message->getMessage());
    }

    /**
     * _createResponse
     * @param string $version
     * @param string $status
     * @return SimpleXMLElement
     */
    private static function _createResponse($version, $status = 'ok'): SimpleXMLElement
    {
        $response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>');
        $response->addAttribute('xmlns', 'http://subsonic.org/restapi');
        $response->addAttribute('status', (string)$status);
        $response->addAttribute('version', (string)$version);
        $response->addAttribute('type', 'ampache');
        $response->addAttribute('serverVersion', Api::$version);

        return $response;
    }

    /**
     * _createSuccessResponse
     * @param string $function
     */
    private static function _createSuccessResponse($function = ''): SimpleXMLElement
    {
        $version  = self::API_VERSION;
        $response = self::_createResponse($version);
        debug_event(self::class, 'API success in function ' . $function . '-' . $version, 5);

        return $response;
    }

    /**
     * _createFailedResponse
     * @param string $function
     * @return SimpleXMLElement
     */
    private static function _createFailedResponse($function = ''): SimpleXMLElement
    {
        $version  = self::API_VERSION;
        $response = self::_createResponse($version, 'failed');
        debug_event(self::class, 'API fail in function ' . $function . '-' . $version, 3);

        return $response;
    }

    /**
     * @param int|string $artist_id
     * @return int
     */
    private static function _getArtistId($artist_id): int
    {
        return ((int)$artist_id) + self::AMPACHEID_ARTIST;
    }

    /**
     * @param int|string $album_id
     * @return int
     */
    private static function _getAlbumId($album_id): int
    {
        return ((int)$album_id) + self::AMPACHEID_ALBUM;
    }

    /**
     * @param int|string $song_id
     * @return int
     */
    private static function _getSongId($song_id): int
    {
        return ((int)$song_id) + self::AMPACHEID_SONG;
    }

    /**
     * @param int $video_id
     */
    private static function _getVideoId($video_id): int
    {
        return $video_id + Subsonic_Xml_Data::AMPACHEID_VIDEO;
    }

    /**
     * @param int $podcast_id
     */
    private static function _getPodcastId($podcast_id): int
    {
        return $podcast_id + self::AMPACHEID_PODCAST;
    }

    /**
     * @param int $episode_id
     */
    private static function _getPodcastEpisodeId($episode_id): int
    {
        return $episode_id + self::AMPACHEID_PODCASTEP;
    }

    /**
     * @param int $plist_id
     */
    private static function _getPlaylistId($plist_id): int
    {
        return $plist_id + self::AMPACHEID_PLAYLIST;
    }

    /**
     * @param int $plist_id
     */
    private static function _getSmartPlaylistId($plist_id): int
    {
        return $plist_id + self::AMPACHEID_SMARTPL;
    }

    /**
     * _cleanId
     * @param string $object_id
     */
    private static function _cleanId($object_id): int
    {
        // Remove all al-, ar-, ... prefixes
        $tpos = strpos((string)$object_id, "-");
        if ($tpos !== false) {
            $object_id = substr((string) $object_id, $tpos + 1);
        }

        return (int)$object_id;
    }

    /**
     * _checkName
     * This to fix xml=>json which can result to wrong type parsing
     * @param null|string $name
     */
    private static function _checkName($name): string
    {
        // Ensure to have always a string type
        if (self::$enable_json_checks && !empty($name)) {
            if (is_numeric($name)) {
                // Add space character to fail numeric test
                $name .= " ";
            }
        }

        return html_entity_decode((string)$name, ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * _getAmpacheObject
     * Return the Ampache media object
     * @param string $object_id
     * @return Song|Video|Podcast_Episode|null
     */
    public static function _getAmpacheObject($object_id)
    {
        if (Subsonic_Xml_Data::_isSong($object_id)) {
            return new Song(Subsonic_Xml_Data::_getAmpacheId($object_id));
        }
        if (Subsonic_Xml_Data::_isVideo($object_id)) {
            return new Video(Subsonic_Xml_Data::_getAmpacheId($object_id));
        }
        if (Subsonic_Xml_Data::_isPodcastEpisode($object_id)) {
            return new Podcast_Episode(Subsonic_Xml_Data::_getAmpacheId($object_id));
        }

        return null;
    }

    /**
     * _getAmpacheId
     * @param string $object_id
     */
    public static function _getAmpacheId($object_id): int
    {
        return (self::_cleanId($object_id) % self::AMPACHEID_ARTIST);
    }

    /**
     * _getAmpacheIdArrays
     * @param array $object_ids
     * @return array
     */
    public static function _getAmpacheIdArrays($object_ids): array
    {
        $ampidarrays = array();
        $track       = 1;
        foreach ($object_ids as $object_id) {
            $ampidarrays[] = array(
                'object_id' => self::_getAmpacheId((string)$object_id),
                'object_type' => self::_getAmpacheType((string)$object_id),
                'track' => $track
            );
            $track++;
        }

        return $ampidarrays;
    }

    /**
     * _getAmpacheType
     * @param string $object_id
     */
    public static function _getAmpacheType($object_id): string
    {
        if (self::_isArtist($object_id)) {
            return "artist";
        } elseif (self::_isAlbum($object_id)) {
            return "album";
        } elseif (self::_isSong($object_id)) {
            return "song";
        } elseif (self::_isSmartPlaylist($object_id)) {
            return "search";
        } elseif (self::_isVideo($object_id)) {
            return "video";
        } elseif (self::_isPodcast($object_id)) {
            return "podcast";
        } elseif (self::_isPodcastEpisode($object_id)) {
            return "podcast_episode";
        } elseif (self::_isPlaylist($object_id)) {
            return "playlist";
        }

        return "";
    }

    /**
     * @param string $artist_id
     */
    public static function _isArtist($artist_id): bool
    {
        $artist_id = self::_cleanId($artist_id);

        return ($artist_id >= self::AMPACHEID_ARTIST && $artist_id < self::AMPACHEID_ALBUM);
    }

    /**
     * @param string $album_id
     */
    public static function _isAlbum($album_id): bool
    {
        $album_id = self::_cleanId($album_id);

        return ($album_id >= self::AMPACHEID_ALBUM && $album_id < self::AMPACHEID_SONG);
    }

    /**
     * @param string $song_id
     */
    public static function _isSong($song_id): bool
    {
        $song_id = self::_cleanId($song_id);

        return ($song_id >= self::AMPACHEID_SONG && $song_id < self::AMPACHEID_SMARTPL);
    }

    /**
     * @param string $video_id
     */
    public static function _isVideo($video_id): bool
    {
        $video_id = self::_cleanId($video_id);

        return ($video_id >= self::AMPACHEID_VIDEO && $video_id < self::AMPACHEID_PODCAST);
    }

    /**
     * @param string $podcast_id
     */
    public static function _isPodcast($podcast_id): bool
    {
        $podcast_id = self::_cleanId($podcast_id);

        return ($podcast_id >= self::AMPACHEID_PODCAST && $podcast_id < self::AMPACHEID_PODCASTEP);
    }

    /**
     * @param string $episode_id
     */
    public static function _isPodcastEpisode($episode_id): bool
    {
        $episode_id = self::_cleanId($episode_id);

        return ($episode_id >= self::AMPACHEID_PODCASTEP && $episode_id < self::AMPACHEID_PLAYLIST);
    }

    /**
     * @param string $plist_id
     */
    public static function _isPlaylist($plist_id): bool
    {
        $plist_id = self::_cleanId($plist_id);

        return ($plist_id >= self::AMPACHEID_PLAYLIST);
    }

    /**
     * @param string $plist_id
     */
    public static function _isSmartPlaylist($plist_id): bool
    {
        $plist_id = self::_cleanId($plist_id);

        return ($plist_id >= self::AMPACHEID_SMARTPL && $plist_id < self::AMPACHEID_VIDEO);
    }

    /**
     * _setIfStarred
     * @param SimpleXMLElement $xml
     * @param string $objectType
     * @param int $object_id
     */
    private static function _setIfStarred($xml, $objectType, $object_id): void
    {
        if (InterfaceImplementationChecker::is_library_item($objectType)) {
            if (AmpConfig::get('ratings')) {
                $starred = new Userflag($object_id, $objectType);
                $result  = $starred->get_flag(null, true);
                if (is_array($result) && isset($result[1])) {
                    $xml->addAttribute('starred', date("Y-m-d\TH:i:s\Z", (int)$result[1]));
                }
            }
        }
    }

    /**
     * _getCatalogData
     * @param int $catalogId
     * @param string $file_Path
     * @return array
     */
    private static function _getCatalogData($catalogId, $file_Path): array
    {
        $results     = array();
        $sqllook     = 'SELECT `catalog_type` FROM `catalog` WHERE `id` = ?;';
        $db_results  = Dba::read($sqllook, [$catalogId]);
        $resultcheck = Dba::fetch_assoc($db_results);
        if (!empty($resultcheck)) {
            if ($resultcheck['catalog_type'] == 'seafile') {
                $results['path'] = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . $file_Path;

                return $results;
            }
            $sql             = 'SELECT `path` FROM `catalog_' . $resultcheck['catalog_type'] . '` WHERE `catalog_id` = ?';
            $db_results      = Dba::read($sql, [$catalogId]);
            $result          = Dba::fetch_assoc($db_results);
            $catalog_path    = rtrim((string)$result['path'], "/");
            $results['path'] = str_replace($catalog_path . "/", "", $file_Path);
        }

        return $results;
    }

    /**
     * Adds a child to an existing result xml structure
     */
    private static function addChildToResultXml(SimpleXMLElement $xml, string $qualifiedName, ?string $value = null): SimpleXMLElement
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

    /**
     * @deprecated Inject by constructor
     */
    private static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }
}
