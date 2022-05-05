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

declare(strict_types=0);

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Playback\Localplay\LocalPlay;
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
use Ampache\Repository\SongRepositoryInterface;
use SimpleXMLElement;

/**
 * XML_Data Class
 *
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls
 *
 */
class Subsonic_Xml_Data
{
    const API_VERSION = "1.13.0";

    const SSERROR_GENERIC               = 0;
    const SSERROR_MISSINGPARAM          = 10;
    const SSERROR_APIVERSION_CLIENT     = 20;
    const SSERROR_APIVERSION_SERVER     = 30;
    const SSERROR_BADAUTH               = 40;
    const SSERROR_TOKENAUTHNOTSUPPORTED = 41;
    const SSERROR_UNAUTHORIZED          = 50;
    const SSERROR_TRIAL                 = 60;
    const SSERROR_DATA_NOTFOUND         = 70;

    // Ampache doesn't have a global unique id but each items are unique per category. We use id pattern to identify item category.
    const AMPACHEID_ARTIST    = 100000000;
    const AMPACHEID_ALBUM     = 200000000;
    const AMPACHEID_SONG      = 300000000;
    const AMPACHEID_SMARTPL   = 400000000;
    const AMPACHEID_VIDEO     = 500000000;
    const AMPACHEID_PODCAST   = 600000000;
    const AMPACHEID_PODCASTEP = 700000000;
    const AMPACHEID_PLAYLIST  = 800000000;

    public static $enable_json_checks = false;

    /**
     * @param $artistid
     * @return integer
     */
    public static function getArtistId($artistid)
    {
        return $artistid + self::AMPACHEID_ARTIST;
    }

    /**
     * @param $albumid
     * @return integer
     */
    public static function getAlbumId($albumid)
    {
        return $albumid + self::AMPACHEID_ALBUM;
    }

    /**
     * @param $songid
     * @return integer
     */
    public static function getSongId($songid)
    {
        return $songid + self::AMPACHEID_SONG;
    }

    /**
     * @param integer $videoid
     * @return integer
     */
    public static function getVideoId($videoid)
    {
        return $videoid + Subsonic_Xml_Data::AMPACHEID_VIDEO;
    }

    /**
     * @param integer $plistid
     * @return integer
     */
    public static function getSmartPlId($plistid)
    {
        return $plistid + self::AMPACHEID_SMARTPL;
    }

    /**
     * @param integer $podcastid
     * @return integer
     */
    public static function getPodcastId($podcastid)
    {
        return $podcastid + self::AMPACHEID_PODCAST;
    }

    /**
     * @param integer $episode_id
     * @return integer
     */
    public static function getPodcastEpId($episode_id)
    {
        return $episode_id + self::AMPACHEID_PODCASTEP;
    }

    /**
     * @param integer $plist_id
     * @return integer
     */
    public static function getPlaylistId($plist_id)
    {
        return $plist_id + self::AMPACHEID_PLAYLIST;
    }

    /**
     * cleanId
     * @param string $object_id
     * @return integer
     */
    private static function cleanId($object_id)
    {
        // Remove all al-, ar-, ... prefixes
        $tpos = strpos((string)$object_id, "-");
        if ($tpos !== false) {
            $object_id = substr((string) $object_id, $tpos + 1);
        }

        return (int) $object_id;
    }

    /**
     * getAmpacheId
     * @param string $object_id
     * @return integer
     */
    public static function getAmpacheId($object_id)
    {
        return (self::cleanId($object_id) % self::AMPACHEID_ARTIST);
    }

    /**
     * getAmpacheIds
     * @param array $object_ids
     * @return array
     */
    public static function getAmpacheIds($object_ids)
    {
        $ampids = array();
        foreach ($object_ids as $object_id) {
            $ampids[] = self::getAmpacheId($object_id);
        }

        return $ampids;
    }

    /**
     * getAmpacheIdArrays
     * @param array $object_ids
     * @return array
     */
    public static function getAmpacheIdArrays($object_ids)
    {
        $ampidarrays = array();
        foreach ($object_ids as $object_id) {
            $ampidarrays[] = array(
                'object_id' => self::getAmpacheId($object_id),
                'object_type' => self::getAmpacheType($object_id)
            );
        }

        return $ampidarrays;
    }

    /**
     * @param string $artist_id
     * @return boolean
     */
    public static function isArtist($artist_id)
    {
        return (self::cleanId($artist_id) >= self::AMPACHEID_ARTIST && $artist_id < self::AMPACHEID_ALBUM);
    }

    /**
     * @param string $album_id
     * @return boolean
     */
    public static function isAlbum($album_id)
    {
        return (self::cleanId($album_id) >= self::AMPACHEID_ALBUM && $album_id < self::AMPACHEID_SONG);
    }

    /**
     * @param string $song_id
     * @return boolean
     */
    public static function isSong($song_id)
    {
        return (self::cleanId($song_id) >= self::AMPACHEID_SONG && $song_id < self::AMPACHEID_SMARTPL);
    }

    /**
     * @param string $plist_id
     * @return boolean
     */
    public static function isSmartPlaylist($plist_id)
    {
        return (self::cleanId($plist_id) >= self::AMPACHEID_SMARTPL && $plist_id < self::AMPACHEID_VIDEO);
    }

    /**
     * @param string $video_id
     * @return boolean
     */
    public static function isVideo($video_id)
    {
        $video_id = self::cleanId($video_id);

        return (self::cleanId($video_id) >= self::AMPACHEID_VIDEO && $video_id < self::AMPACHEID_PODCAST);
    }

    /**
     * @param string $podcast_id
     * @return boolean
     */
    public static function isPodcast($podcast_id)
    {
        return (self::cleanId($podcast_id) >= self::AMPACHEID_PODCAST && $podcast_id < self::AMPACHEID_PODCASTEP);
    }

    /**
     * @param string $episode_id
     * @return boolean
     */
    public static function isPodcastEp($episode_id)
    {
        return (self::cleanId($episode_id) >= self::AMPACHEID_PODCASTEP && $episode_id < self::AMPACHEID_PLAYLIST);
    }

    /**
     * @param string $plistid
     * @return boolean
     */
    public static function isPlaylist($plistid)
    {
        return (self::cleanId($plistid) >= self::AMPACHEID_PLAYLIST);
    }

    /**
     * getAmpacheType
     * @param string $object_id
     * @return string
     */
    public static function getAmpacheType($object_id)
    {
        if (self::isArtist($object_id)) {
            return "artist";
        } elseif (self::isAlbum($object_id)) {
            return "album";
        } elseif (self::isSong($object_id)) {
            return "song";
        } elseif (self::isSmartPlaylist($object_id)) {
            return "search";
        } elseif (self::isVideo($object_id)) {
            return "video";
        } elseif (self::isPodcast($object_id)) {
            return "podcast";
        } elseif (self::isPodcastEp($object_id)) {
            return "podcast_episode";
        } elseif (self::isPlaylist($object_id)) {
            return "playlist";
        }

        return "";
    }

    /**
     * createFailedResponse
     * @param string $function
     * @return SimpleXMLElement
     */
    public static function createFailedResponse($function = '')
    {
        $version  = self::API_VERSION;
        $response = self::createResponse($version, 'failed');
        debug_event(self::class, 'API fail in function ' . $function . '-' . $version, 3);

        return $response;
    }

    /**
     * createSuccessResponse
     * @param string $function
     * @return SimpleXMLElement
     */
    public static function createSuccessResponse($function = '')
    {
        $version  = self::API_VERSION;
        $response = self::createResponse($version);
        debug_event(self::class, 'API success in function ' . $function . '-' . $version, 5);

        return $response;
    }

    /**
     * createResponse
     * @param string $version
     * @param string $status
     * @return SimpleXMLElement
     */
    public static function createResponse($version, $status = 'ok')
    {
        $response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>');
        $response->addAttribute('xmlns', 'http://subsonic.org/restapi');
        $response->addAttribute('status', (string)$status);
        $response->addAttribute('version', (string)$version);

        return $response;
    }

    /**
     * createError
     * @param $code
     * @param string $message
     * @param string $function
     * @return SimpleXMLElement
     */
    public static function createError($code, $message, $function = '')
    {
        $response = self::createFailedResponse($function);
        self::addError($response, $code, $message);

        return $response;
    }

    /**
     * addError
     * Set error information.
     *
     * @param SimpleXMLElement $xml Parent node
     * @param integer $code Error code
     * @param string $message Error message
     */
    public static function addError($xml, $code, $message = '')
    {
        $xerr = $xml->addChild('error');
        $xerr->addAttribute('code', (string)$code);

        if (empty($message)) {
            switch ($code) {
                case self::SSERROR_GENERIC:
                    $message = "A generic error.";
                    break;
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
        }

        $xerr->addAttribute('message', (string)$message);
    }

    /**
     * addLicense
     * @param SimpleXMLElement $xml
     */
    public static function addLicense($xml)
    {
        $xlic = $xml->addChild('license');
        $xlic->addAttribute('valid', 'true');
        $xlic->addAttribute('email', 'webmaster@ampache.org');
        $xlic->addAttribute('key', 'ABC123DEF');
        $xlic->addAttribute('date', '2009-09-03T14:46:43');
    }

    /**
     * addMusicFolders
     * @param SimpleXMLElement $xml
     * @param integer[] $catalogs
     */
    public static function addMusicFolders($xml, $catalogs)
    {
        $xfolders = $xml->addChild('musicFolders');
        foreach ($catalogs as $folderid) {
            $catalog = Catalog::create_from_id($folderid);
            $xfolder = $xfolders->addChild('musicFolder');
            $xfolder->addAttribute('id', (string)$folderid);
            $xfolder->addAttribute('name', (string)$catalog->name);
        }
    }

    /**
     * addIgnoredArticles
     * @param SimpleXMLElement $xml
     */
    private static function addIgnoredArticles($xml)
    {
        $ignoredArticles = AmpConfig::get('catalog_prefix_pattern');
        if (!empty($ignoredArticles)) {
            $ignoredArticles = str_replace("|", " ", $ignoredArticles);
            $xml->addAttribute('ignoredArticles', (string)$ignoredArticles);
        }
    }

    /**
     * addIndexes
     * @param SimpleXMLElement $xml
     * @param array $artists
     * @param $lastModified
     */
    public static function addIndexes($xml, $artists, $lastModified)
    {
        $xindexes = $xml->addChild('indexes');
        $xindexes->addAttribute('lastModified', number_format($lastModified * 1000, 0, '.', ''));
        self::addIgnoredArticles($xindexes);
        self::addIndex($xindexes, $artists);
    }

    /**
     * addArtists
     * @param SimpleXMLElement $xml
     * @param array $artists
     */
    public static function addArtists($xml, $artists)
    {
        $xartists = $xml->addChild('artists');
        self::addIgnoredArticles($xartists);
        self::addIndex($xartists, $artists);
    }

    /**
     * addArtist
     * @param SimpleXMLElement $xml
     * @param Artist $artist
     * @param boolean $extra
     * @param boolean $albums
     * @param boolean $albumsSet
     */
    public static function addArtist($xml, $artist, $extra = false, $albums = false, $albumsSet = false)
    {
        $artist->format();
        $sub_id  = (string)self::getArtistId($artist->id);
        $xartist = $xml->addChild('artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', (string)self::checkName($artist->get_fullname()));
        $allalbums = array();
        if (($extra && !$albumsSet) || $albums) {
            $allalbums = static::getAlbumRepository()->getByArtist($artist->id);
        }

        if ($extra) {
            if ($artist->has_art()) {
                $xartist->addAttribute('coverArt', 'ar-' . $sub_id);
            }
            if ($albumsSet) {
                $xartist->addAttribute('albumCount', (string)$artist->albums);
            } else {
                $xartist->addAttribute('albumCount', (string)count($allalbums));
            }
            self::setIfStarred($xartist, 'artist', $artist->id);
        }
        if ($albums) {
            foreach ($allalbums as $albumid) {
                $album = new Album($albumid);
                self::addAlbum($xartist, $album);
            }
        }
    }

    /**
     * addIndex
     * @param SimpleXMLElement $xml
     * @param array $artists
     */
    private static function addIndex($xml, $artists)
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
                    $xlastcat    = $xml->addChild('index');
                    $xlastcat->addAttribute('name', (string)$xlastletter);
                }
            }

            if ($xlastcat != null) {
                self::addArtistArray($xlastcat, $artist);
            }
        }

        // Always add # index at the end
        if (count($sharpartists) > 0) {
            $xsharpcat = $xml->addChild('index');
            $xsharpcat->addAttribute('name', '#');

            foreach ($sharpartists as $artist) {
                self::addArtistArray($xsharpcat, $artist);
            }
        }
    }

    /**
     * addChildArray
     * @param SimpleXMLElement $xml
     * @param array $child
     */
    private static function addChildArray($xml, $child)
    {
        $sub_id = (string)self::getArtistId($child['id']);
        $xchild = $xml->addChild('child');
        $xchild->addAttribute('id', $sub_id);
        if (array_key_exists('catalog_id', $child)) {
            $xchild->addAttribute('parent', $child['catalog_id']);
        }
        $xchild->addAttribute('isDir', 'true');
        $xchild->addAttribute('title', (string)self::checkName($child['f_name']));
        $xchild->addAttribute('artist', (string)self::checkName($child['f_name']));
        if ($child['has_art']) {
            $xchild->addAttribute('coverArt', 'ar-' . $sub_id);
        }
    }

    /**
     * addArtistArray
     * @param SimpleXMLElement $xml
     * @param array $artist
     */
    private static function addArtistArray($xml, $artist)
    {
        $sub_id  = (string)self::getArtistId($artist['id']);
        $xartist = $xml->addChild('artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', (string)self::checkName($artist['f_name']));
        if ($artist['has_art']) {
            $xartist->addAttribute('coverArt', 'ar-' . $sub_id);
        }
        $xartist->addAttribute('albumCount', (string)$artist['album_count']);
        self::setIfStarred($xartist, 'artist', $artist['id']);
    }

    /**
     * addAlbumList
     * @param SimpleXMLElement $xml
     * @param $albums
     */
    public static function addAlbumList($xml, $albums)
    {
        $xlist = $xml->addChild(htmlspecialchars('albumList'));
        foreach ($albums as $albumid) {
            $album = new Album($albumid);
            self::addAlbum($xlist, $album);
        }
    }

    /**
     * addAlbumList2
     * @param SimpleXMLElement $xml
     * @param $albums
     */
    public static function addAlbumList2($xml, $albums)
    {
        $xlist = $xml->addChild(htmlspecialchars('albumList2'));
        foreach ($albums as $albumid) {
            $album = new Album($albumid);
            self::addAlbum($xlist, $album);
        }
    }

    /**
     * addAlbum
     * @param SimpleXMLElement $xml
     * @param Album $album
     * @param boolean $songs
     * @param string $elementName
     */
    public static function addAlbum($xml, $album, $songs = false, $elementName = "album")
    {
        $album->format();
        $sub_id    = (string)self::getAlbumId($album->id);
        $subParent = self::getArtistId($album->album_artist);
        $xalbum    = $xml->addChild(htmlspecialchars($elementName));
        $f_name    = (string)self::checkName($album->get_fullname());
        $xalbum->addAttribute('id', $sub_id);
        $xalbum->addAttribute('parent', $subParent);
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
        $xalbum->addAttribute('artistId', $subParent);
        $xalbum->addAttribute('artist', (string) self::checkName($album->get_album_artist_fullname()));
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
        $user_rating = ($rating->get_user_rating() ?: 0);
        if ($user_rating > 0) {
            $xalbum->addAttribute('userRating', (string)ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xalbum->addAttribute('averageRating', (string)$avg_rating);
        }
        self::setIfStarred($xalbum, 'album', $album->id);

        if ($songs) {
            $disc_ids  = $album->get_group_disks_ids();
            $media_ids = static::getAlbumRepository()->getSongsGrouped($disc_ids);
            foreach ($media_ids as $song_id) {
                self::addSong($xalbum, $song_id, 'song', $album);
            }
        }
    }

    /**
     * addSong
     * @param SimpleXMLElement $xml
     * @param integer $songId
     * @param string $elementName
     * @param Album $album
     * @return SimpleXMLElement
     */
    public static function addSong($xml, $songId, $elementName = 'song', $album = false)
    {
        $song  = new Song($songId);
        $album = (!$album)
            ? new Album($song->album)
            : $album;
        $catalogData = self::getCatalogData($song->catalog, $song->file);

        return self::createSong($xml, $song, $album, $catalogData, $elementName);
    }

    /**
     * getSongData
     * @param integer $songId
     * @return array
     */
    public static function getSongData($songId)
    {
        $sql        = 'SELECT `song`.`id`, `song`.`file`, `song`.`catalog`, `song`.`album`, `album`.`album_artist` AS `albumartist`, `song`.`year`, `song`.`artist`, `song`.`title`, `song`.`bitrate`, `song`.`rate`, `song`.`mode`, `song`.`size`, `song`.`time`, `song`.`track`, `song`.`played`, `song`.`enabled`, `song`.`update_time`, `song`.`mbid`, `song`.`addition_time`, `song`.`license`, `song`.`composer`, `song`.`user_upload`, `song`.`total_count`, `song`.`total_skip`, `album`.`mbid` AS `album_mbid`, `artist`.`mbid` AS `artist_mbid`, `album_artist`.`mbid` AS `albumartist_mbid` FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` LEFT JOIN `artist` AS `album_artist` ON `album_artist`.`id` = `album`.`album_artist` WHERE `song`.`id` = ?';
        $db_results = Dba::read($sql, array($songId));
        $row        = Dba::fetch_assoc($db_results);
        if (empty($row)) {
            debug_event(self::class, 'getSongData failed: ' . $songId, 5);

            return array();
        }
        $extension   = pathinfo((string)$row['file'], PATHINFO_EXTENSION);
        $row['type'] = strtolower((string)$extension);
        $row['mime'] = Song::type_to_mime($row['type']);

        return $row;
    }

    /**
     * getAlbumData
     * @param integer $albumId
     * @return array
     */
    public static function getAlbumData($albumId)
    {
        $sql        = "SELECT * FROM `album` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($albumId));
        $row        = Dba::fetch_assoc($db_results);
        if (empty($row)) {
            debug_event(self::class, 'getAlbumData failed: ' . $albumId, 5);

            return array();
        }
        $row['f_name'] = trim(trim((string)$row['prefix']) . ' ' . trim((string)$row['name']));

        return $row;
    }

    /**
     * getArtistData
     * @param integer $artistId
     * @return array
     */
    public static function getArtistData($artistId)
    {
        $sql        = "SELECT * FROM `artist` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($artistId));
        $row        = Dba::fetch_assoc($db_results);
        if (empty($row)) {
            debug_event(self::class, 'getArtistData failed: ' . $artistId, 5);

            return array();
        }
        $row['f_name'] = trim(trim((string)$row['prefix']) . ' ' . trim((string)$row['name']));

        return $row;
    }

    /**
     * getCatalogData
     * @param integer $catalogId
     * @param string $file_Path
     * @return array
     */
    public static function getCatalogData($catalogId, $file_Path)
    {
        $results     = array();
        $sqllook     = 'SELECT `catalog_type` FROM `catalog` WHERE `id` = ?';
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
     * createSong
     * @param SimpleXMLElement $xml
     * @param Song $song
     * @param Album $album
     * @param array $catalogData
     * @param string $elementName
     * @return SimpleXMLElement
     */
    public static function createSong(
        $xml,
        $song,
        $album,
        $catalogData,
        $elementName = 'song'
    ) {
        // Don't create entries for disabled songs
        if (!$song->enabled) {
            return null;
        }
        $sub_id    = (string)self::getSongId($song->id);
        $subParent = (string)self::getAlbumId($album->id);
        $xsong     = $xml->addChild(htmlspecialchars($elementName));
        $xsong->addAttribute('id', $sub_id);
        $xsong->addAttribute('parent', $subParent);
        //$xsong->addAttribute('created', );
        $xsong->addAttribute('title', (string)self::checkName($song->title));
        $xsong->addAttribute('isDir', 'false');
        $xsong->addAttribute('isVideo', 'false');
        $xsong->addAttribute('type', 'music');
        // $album = new Album(songData->album);
        $xsong->addAttribute('albumId', $subParent);
        $xsong->addAttribute('album', (string)self::checkName($album->get_fullname()));
        // $artist = new Artist($song->artist);
        // $artist->format();
        $xsong->addAttribute('artistId', (string) self::getArtistId($song->artist));
        $xsong->addAttribute('artist', (string) self::checkName($song->get_artist_fullname()));
        if ($song->has_art()) {
            $xsong->addAttribute('coverArt', $sub_id);
        }
        $xsong->addAttribute('duration', (string) $song->time);
        $xsong->addAttribute('bitRate', (string) ((int) ($song->bitrate / 1000)));
        $rating      = new Rating($song->id, "song");
        $user_rating = ($rating->get_user_rating() ?: 0);
        if ($user_rating > 0) {
            $xsong->addAttribute('userRating', (string)ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xsong->addAttribute('averageRating', (string)$avg_rating);
        }
        self::setIfStarred($xsong, 'song', $song->id);
        if ($song->track > 0) {
            $xsong->addAttribute('track', (string)$song->track);
        }
        if ($song->year > 0) {
            $xsong->addAttribute('year', (string)$song->year);
        }
        $tags = Tag::get_object_tags('song', (int) $song->id);
        if (count($tags) > 0) {
            $xsong->addAttribute('genre', (string)$tags[0]['name']);
        }
        $xsong->addAttribute('size', (string)$song->size);
        $disk = $song->get_album_disk();
        if ($disk > 0) {
            $xsong->addAttribute('discNumber', (string)$disk);
        }
        $xsong->addAttribute('suffix', (string)$song->type);
        $xsong->addAttribute('contentType', (string)$song->mime);
        // Return a file path relative to the catalog root path
        $xsong->addAttribute('path', (string)$catalogData['path']);

        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode');
        $valid_types   = Song::get_stream_types_for_type($song->type, 'api');
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && !in_array('native', $valid_types))) {
            // $transcode_settings = Song::get_transcode_settings_for_media(null, null, 'api', 'song');
            $transcode_type = AmpConfig::get('encode_player_api_target', 'mp3');
            $xsong->addAttribute('transcodedSuffix', (string)$transcode_type);
            $xsong->addAttribute('transcodedContentType', Song::type_to_mime($transcode_type));
        }

        return $xsong;
    }

    /**
     * checkName
     * This to fix xml=>json which can result to wrong type parsing
     * @param string $name
     * @return string|null
     */
    private static function checkName($name)
    {
        // Ensure to have always a string type
        if (self::$enable_json_checks && !empty($name)) {
            if (is_numeric($name)) {
                // Add space character to fail numeric test
                $name .= " ";
            }
        }

        return html_entity_decode($name, ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * getAmpacheObject
     * Return the Ampache media object
     * @param integer $object_id
     * @return Song|Video|Podcast_Episode|null
     */
    public static function getAmpacheObject($object_id)
    {
        if (Subsonic_Xml_Data::isSong($object_id)) {
            return new Song(Subsonic_Xml_Data::getAmpacheId($object_id));
        }
        if (Subsonic_Xml_Data::isVideo($object_id)) {
            return new Video(Subsonic_Xml_Data::getAmpacheId($object_id));
        }
        if (Subsonic_Xml_Data::isPodcastEp($object_id)) {
            return new Podcast_Episode(Subsonic_Xml_Data::getAmpacheId($object_id));
        }

        return null;
    } // getAmpacheObject

    /**
     * addDirectory_Artist for subsonic artist id
     * @param SimpleXMLElement $xml
     * @param string $sub_id
     * @param string $dirType
     */
    public static function addDirectory($xml, $sub_id, $dirType)
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
    private static function addDirectory_Artist($xml, $sub_id)
    {
        $artist_id = self::getAmpacheId($sub_id);
        $data      = Artist::get_id_array($artist_id);
        $xdir      = $xml->addChild('directory');
        $xdir->addAttribute('id', (string)$sub_id);
        if (array_key_exists('catalog_id', $data)) {
            $xdir->addAttribute('parent', (string)$data['catalog_id']);
        }
        $xdir->addAttribute('name', (string)$data['f_name']);
        self::setIfStarred($xdir, 'artist', $artist_id);
        $allalbums = static::getAlbumRepository()->getByArtist($artist_id);
        foreach ($allalbums as $album_id) {
            $album = new Album($album_id);
            self::addAlbum($xdir, $album, false, "child");
        }
    }

    /**
     * addDirectory_Album for subsonic album id
     * @param SimpleXMLElement $xml
     * @param string $album_id
     */
    private static function addDirectory_Album($xml, $album_id)
    {
        $album = new Album(self::getAmpacheId($album_id));
        $album->format();
        $xdir = $xml->addChild('directory');
        $xdir->addAttribute('id', (string)$album_id);
        if ($album->album_artist) {
            $xdir->addAttribute('parent', (string)self::getArtistId($album->album_artist));
        } else {
            $xdir->addAttribute('parent', (string)$album->catalog);
        }
        $xdir->addAttribute('name', (string)self::checkName($album->get_fullname()));
        self::setIfStarred($xdir, 'album', $album->id);

        $disc_ids  = $album->get_group_disks_ids();
        $media_ids = static::getAlbumRepository()->getSongsGrouped($disc_ids);
        foreach ($media_ids as $song_id) {
            self::addSong($xdir, $song_id, "child", $album);
        }
    }

    /**
     * addDirectory_Catalog for subsonic artist id
     * @param SimpleXMLElement $xml
     * @param string $catalog_id
     */
    private static function addDirectory_Catalog($xml, $catalog_id)
    {
        $catalog = Catalog::create_from_id($catalog_id);
        $xdir    = $xml->addChild('directory');
        $xdir->addAttribute('id', (string)$catalog_id);
        $xdir->addAttribute('name', $catalog->name);
        $allartists = Catalog::get_artist_arrays(array($catalog_id));
        foreach ($allartists as $artist) {
            self::addChildArray($xdir, $artist);
        }
    }

    /**
     * addGenres
     * @param SimpleXMLElement $xml
     * @param $tags
     */
    public static function addGenres($xml, $tags)
    {
        $xgenres = $xml->addChild('genres');

        foreach ($tags as $tag) {
            $otag   = new Tag($tag['id']);
            $xgenre = $xgenres->addChild('genre', htmlspecialchars($otag->name));
            $counts = $otag->count();
            $xgenre->addAttribute('songCount', (string) $counts['song'] ?? 0);
            $xgenre->addAttribute('albumCount', (string) $counts['album'] ?? 0);
        }
    }

    /**
     * addVideos
     * @param SimpleXMLElement $xml
     * @param Video[] $videos
     */
    public static function addVideos($xml, $videos)
    {
        $xvideos = $xml->addChild('videos');
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
    private static function addVideo($xml, $video, $elementName = 'video')
    {
        $sub_id = (string)self::getVideoId($video->id);
        $xvideo = $xml->addChild(htmlspecialchars($elementName));
        $xvideo->addAttribute('id', $sub_id);
        $xvideo->addAttribute('title', (string)$video->f_full_title);
        $xvideo->addAttribute('isDir', 'false');
        if ($video->has_art()) {
            $xvideo->addAttribute('coverArt', $sub_id);
        }
        $xvideo->addAttribute('isVideo', 'true');
        $xvideo->addAttribute('type', 'video');
        $xvideo->addAttribute('duration', (string)$video->time);
        if ($video->year > 0) {
            $xvideo->addAttribute('year', (string)$video->year);
        }
        $tags = Tag::get_object_tags('video', (int)$video->id);
        if (count($tags) > 0) {
            $xvideo->addAttribute('genre', (string)$tags[0]['name']);
        }
        $xvideo->addAttribute('size', (string)$video->size);
        $xvideo->addAttribute('suffix', (string)$video->type);
        $xvideo->addAttribute('contentType', (string)$video->mime);
        // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
        $path = basename($video->file);
        $xvideo->addAttribute('path', (string)$path);

        self::setIfStarred($xvideo, 'video', $video->id);
        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode');
        $valid_types   = Song::get_stream_types_for_type($video->type, 'api');
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
     * addPlaylists
     * @param SimpleXMLElement $xml
     * @param int $user_id
     * @param array $playlists
     * @param array $smartplaylists
     * @param bool $hide_dupe_searches
     */
    public static function addPlaylists($xml, $user_id, $playlists, $smartplaylists = array(), $hide_dupe_searches = false)
    {
        $playlist_names = array();
        $xplaylists     = $xml->addChild('playlists');
        foreach ($playlists as $plistid) {
            $playlist = new Playlist($plistid);
            if ($hide_dupe_searches && $playlist->user == $user_id) {
                $playlist_names[] = $playlist->name;
            }
            self::addPlaylist($xplaylists, $playlist);
        }
        foreach ($smartplaylists as $splistid) {
            $playlist = new Search((int)str_replace('smart_', '', (string)$splistid), 'song');
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
     * @param boolean $songs
     */
    public static function addPlaylist($xml, $playlist, $songs = false)
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
     * @param boolean $songs
     */
    private static function addPlaylist_Playlist($xml, $playlist, $songs = false)
    {
        $sub_id    = (string)self::getPlaylistId($playlist->id);
        $songcount = $playlist->get_media_count('song');
        $duration  = ($songcount > 0) ? $playlist->get_total_duration() : 0;
        $xplaylist = $xml->addChild('playlist');
        $xplaylist->addAttribute('id', $sub_id);
        $xplaylist->addAttribute('name', (string)self::checkName($playlist->get_fullname()));
        $xplaylist->addAttribute('owner', (string)$playlist->username);
        $xplaylist->addAttribute('public', ($playlist->type != "private") ? "true" : "false");
        $xplaylist->addAttribute('created', date("c", (int)$playlist->date));
        $xplaylist->addAttribute('changed', date("c", (int)$playlist->last_update));
        $xplaylist->addAttribute('songCount', (string)$songcount);
        $xplaylist->addAttribute('duration', (string)$duration);
        if ($playlist->has_art()) {
            $xplaylist->addAttribute('coverArt', $sub_id);
        }

        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $songId) {
                self::addSong($xplaylist, $songId, "entry");
            }
        }
    }

    /**
     * addPlaylist_Search
     * @param SimpleXMLElement $xml
     * @param Search $search
     * @param boolean $songs
     */
    private static function addPlaylist_Search($xml, $search, $songs = false)
    {
        $sub_id    = (string) self::getSmartPlId($search->id);
        $xplaylist = $xml->addChild('playlist');
        $xplaylist->addAttribute('id', $sub_id);
        $xplaylist->addAttribute('name', (string) self::checkName($search->get_fullname()));
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
     * @param int $user_id
     * @param string $username
     */
    public static function addPlayQueue($xml, $user_id, $username)
    {
        $PlayQueue = new User_Playlist($user_id);
        $items     = $PlayQueue->get_items();
        if (!empty($items)) {
            $current    = $PlayQueue->get_current_object();
            $changed    = User::get_user_data($user_id, 'playqueue_date')['playqueue_date'] ?? '';
            $changedBy  = User::get_user_data($user_id, 'playqueue_client')['playqueue_client'] ?? '';
            $xplayqueue = $xml->addChild('playQueue');
            $xplayqueue->addAttribute('current', self::getSongId($current['object_id']));
            $xplayqueue->addAttribute('position', (string)$current['current_time']);
            $xplayqueue->addAttribute('username', (string)$username);
            $xplayqueue->addAttribute('changed', date("c", (int)$changed));
            $xplayqueue->addAttribute('changedBy', (string)$changedBy);

            if ($items) {
                foreach ($items as $row) {
                    self::addSong($xplayqueue, (int)$row['object_id'], "entry");
                }
            }
        }
    }

    /**
     * addRandomSongs
     * @param SimpleXMLElement $xml
     * @param array $songs
     */
    public static function addRandomSongs($xml, $songs)
    {
        $xsongs = $xml->addChild('randomSongs');
        foreach ($songs as $songid) {
            self::addSong($xsongs, $songid);
        }
    }

    /**
     * addSongsByGenre
     * @param SimpleXMLElement $xml
     * @param array $songs
     */
    public static function addSongsByGenre($xml, $songs)
    {
        $xsongs = $xml->addChild('songsByGenre');
        foreach ($songs as $songid) {
            self::addSong($xsongs, $songid);
        }
    }

    /**
     * addTopSongs
     * @param SimpleXMLElement $xml
     * @param array $songs
     */
    public static function addTopSongs($xml, $songs)
    {
        $xsongs = $xml->addChild('topSongs');
        foreach ($songs as $songid) {
            self::addSong($xsongs, $songid);
        }
    }

    /**
     * addNowPlaying
     * @param SimpleXMLElement $xml
     * @param array $data
     */
    public static function addNowPlaying($xml, $data)
    {
        $xplaynow = $xml->addChild('nowPlaying');
        foreach ($data as $d) {
            $track = self::addSong($xplaynow, $d['media']->getId(), "entry");
            if ($track !== null) {
                $track->addAttribute('username', (string)$d['client']->username);
                $track->addAttribute('minutesAgo', (string)(abs((time() - ($d['expire'] - $d['media']->time)) / 60)));
                $track->addAttribute('playerId', (string)$d['agent']);
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
    public static function addSearchResult2($xml, $artists, $albums, $songs)
    {
        $xresult = $xml->addChild(htmlspecialchars('searchResult2'));
        foreach ($artists as $artistid) {
            $artist = new Artist((int) $artistid);
            self::addArtist($xresult, $artist);
        }
        foreach ($albums as $albumid) {
            $album = new Album($albumid);
            self::addAlbum($xresult, $album);
        }
        foreach ($songs as $songid) {
            self::addSong($xresult, $songid);
        }
    }

    /**
     * addSearchResult3
     * @param SimpleXMLElement $xml
     * @param array $artists
     * @param array $albums
     * @param array $songs
     */
    public static function addSearchResult3($xml, $artists, $albums, $songs)
    {
        $xresult = $xml->addChild(htmlspecialchars('searchResult3'));
        foreach ($artists as $artistid) {
            $artist = new Artist((int) $artistid);
            self::addArtist($xresult, $artist);
        }
        foreach ($albums as $albumid) {
            $album = new Album($albumid);
            self::addAlbum($xresult, $album);
        }
        foreach ($songs as $songid) {
            self::addSong($xresult, $songid);
        }
    }

    /**
     * setIfStarred
     * @param SimpleXMLElement $xml
     * @param string $objectType
     * @param integer $object_id
     */
    private static function setIfStarred($xml, $objectType, $object_id)
    {
        if (InterfaceImplementationChecker::is_library_item($objectType)) {
            if (AmpConfig::get('ratings')) {
                $starred = new Userflag($object_id, $objectType);
                if ($res = $starred->get_flag(null, true)) {
                    $xml->addAttribute('starred', date("Y-m-d\TH:i:s\Z", (int)$res[1]));
                }
            }
        }
    }

    /**
     * addStarred
     * @param SimpleXMLElement $xml
     * @param array $artists
     * @param array $albums
     * @param array $songs
     */
    public static function addStarred($xml, $artists, $albums, $songs)
    {
        $xstarred = $xml->addChild(htmlspecialchars('starred'));

        foreach ($artists as $artistid) {
            $artist = new Artist((int) $artistid);
            self::addArtist($xstarred, $artist);
        }

        foreach ($albums as $albumid) {
            $album = new Album($albumid);
            self::addAlbum($xstarred, $album);
        }

        foreach ($songs as $songid) {
            self::addSong($xstarred, $songid);
        }
    }

    /**
     * addStarred2
     * @param SimpleXMLElement $xml
     * @param array $artists
     * @param array $albums
     * @param array $songs
     */
    public static function addStarred2($xml, $artists, $albums, $songs)
    {
        $xstarred = $xml->addChild(htmlspecialchars('starred2'));

        foreach ($artists as $artistid) {
            $artist = new Artist((int) $artistid);
            self::addArtist($xstarred, $artist);
        }

        foreach ($albums as $albumid) {
            $album = new Album($albumid);
            self::addAlbum($xstarred, $album);
        }

        foreach ($songs as $songid) {
            self::addSong($xstarred, $songid);
        }
    }

    /**
     * addUser
     * @param SimpleXMLElement $xml
     * @param User $user
     */
    public static function addUser($xml, $user)
    {
        $xuser = $xml->addChild('user');
        $xuser->addAttribute('username', (string)$user->username);
        $xuser->addAttribute('email', (string)$user->email);
        $xuser->addAttribute('scrobblingEnabled', 'true');
        $isManager = ($user->access >= 75);
        $isAdmin   = ($user->access >= 100);
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
    }

    /**
     * addUsers
     * @param SimpleXMLElement $xml
     * @param array $users
     */
    public static function addUsers($xml, $users)
    {
        $xusers = $xml->addChild('users');
        foreach ($users as $userid) {
            $user = new User($userid);
            self::addUser($xusers, $user);
        }
    }

    /**
     * addInternetRadioStation
     * @param SimpleXMLElement $xml
     * @param Live_Stream $radio
     */
    private static function addInternetRadioStation($xml, $radio)
    {
        $xradio = $xml->addChild('internetRadioStation');
        $xradio->addAttribute('id', (string)$radio->id);
        $xradio->addAttribute('name', (string)self::checkName($radio->name));
        $xradio->addAttribute('streamUrl', (string)$radio->url);
        $xradio->addAttribute('homePageUrl', (string)$radio->site_url);
    }

    /**
     * addInternetRadioStations
     * @param SimpleXMLElement $xml
     * @param $radios
     */
    public static function addInternetRadioStations($xml, $radios)
    {
        $xradios = $xml->addChild('internetRadioStations');
        foreach ($radios as $radioid) {
            $radio = new Live_Stream((int)$radioid);
            self::addInternetRadioStation($xradios, $radio);
        }
    }

    /**
     * addShare
     * @param SimpleXMLElement $xml
     * @param Share $share
     */
    private static function addShare($xml, $share)
    {
        $xshare = $xml->addChild('share');
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
            self::addSong($xshare, $share->object_id, "entry");
        } elseif ($share->object_type == 'playlist') {
            $playlist = new Playlist($share->object_id);
            $songs    = $playlist->get_songs();
            foreach ($songs as $songid) {
                self::addSong($xshare, $songid, "entry");
            }
        } elseif ($share->object_type == 'album') {
            $songs = static::getSongRepository()->getByAlbum($share->object_id);
            foreach ($songs as $songid) {
                self::addSong($xshare, $songid, "entry");
            }
        }
    }

    /**
     * addShares
     * @param SimpleXMLElement $xml
     * @param array $shares
     */
    public static function addShares($xml, $shares)
    {
        $xshares = $xml->addChild('shares');
        foreach ($shares as $share_id) {
            $share = new Share((int)$share_id);
            // Don't add share with max counter already reached
            if ($share->max_counter == 0 || $share->counter < $share->max_counter) {
                self::addShare($xshares, $share);
            }
        }
    }

    /**
     * addJukeboxPlaylist
     * @param SimpleXMLElement $xml
     * @param LocalPlay $localplay
     */
    public static function addJukeboxPlaylist($xml, LocalPlay $localplay)
    {
        $xjbox  = self::addJukeboxStatus($xml, $localplay, 'jukeboxPlaylist');
        $tracks = $localplay->get();
        foreach ($tracks as $track) {
            if ($track['oid']) {
                self::addSong($xjbox, (int)$track['oid'], 'entry');
            }
        }
    }

    /**
     * addJukeboxStatus
     * @param SimpleXMLElement $xml
     * @param LocalPlay $localplay
     * @param string $elementName
     * @return SimpleXMLElement
     */
    public static function addJukeboxStatus($xml, LocalPlay $localplay, $elementName = 'jukeboxStatus')
    {
        $xjbox  = $xml->addChild(htmlspecialchars($elementName));
        $status = $localplay->status();
        $index  = (AmpConfig::get('localplay_controller') == 'mpd') // TODO a way for this to support all localplay types
            ? $status['track'] - 1
            : 0;
        $xjbox->addAttribute('currentIndex', $index);
        $xjbox->addAttribute('playing', ($status['state'] == 'play') ? 'true' : 'false');
        $xjbox->addAttribute('gain', (string)$status['volume']);
        $xjbox->addAttribute('position', 0); // TODO Not supported

        return $xjbox;
    }

    /**
     * addLyrics
     * @param SimpleXMLElement $xml
     * @param $artist
     * @param $title
     * @param $song_id
     */
    public static function addLyrics($xml, $artist, $title, $song_id)
    {
        $song = new Song($song_id);
        $song->fill_ext_info('lyrics');
        $lyrics = $song->get_lyrics();

        if (!empty($lyrics) && $lyrics['text']) {
            $text    = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $lyrics['text']);
            $text    = str_replace("\r", '', (string)$text);
            $xlyrics = $xml->addChild('lyrics', htmlspecialchars($text));
            if ($artist) {
                $xlyrics->addAttribute('artist', (string)$artist);
            }
            if ($title) {
                $xlyrics->addAttribute('title', (string)$title);
            }
        }
    }

    /**
     * addArtistInfo
     * @param SimpleXMLElement $xml
     * @param array $info
     * @param array $similars
     */
    public static function addArtistInfo($xml, $info, $similars)
    {
        $artist = new Artist((int) $info['id']);

        $xartist = $xml->addChild(htmlspecialchars('artistInfo'));
        $xartist->addChild('biography', htmlspecialchars(trim((string)$info['summary'])));
        $xartist->addChild('musicBrainzId', $artist->mbid);
        //$xartist->addChild('lastFmUrl', "");
        $xartist->addChild('smallImageUrl', htmlentities($info['smallphoto']));
        $xartist->addChild('mediumImageUrl', htmlentities($info['mediumphoto']));
        $xartist->addChild('largeImageUrl', htmlentities($info['largephoto']));

        foreach ($similars as $similar) {
            $xsimilar = $xartist->addChild('similarArtist');
            $xsimilar->addAttribute('id', ($similar['id'] !== null ? self::getArtistId($similar['id']) : "-1"));
            $xsimilar->addAttribute('name', (string)self::checkName($similar['name']));
        }
    }

    /**
     * addArtistInfo2
     * @param SimpleXMLElement $xml
     * @param array $info
     * @param array $similars
     */
    public static function addArtistInfo2($xml, $info, $similars)
    {
        $artist = new Artist((int) $info['id']);

        $xartist = $xml->addChild(htmlspecialchars('artistInfo2'));
        $xartist->addChild('biography', htmlspecialchars(trim((string)$info['summary'])));
        $xartist->addChild('musicBrainzId', $artist->mbid);
        //$xartist->addChild('lastFmUrl', "");
        $xartist->addChild('smallImageUrl', htmlentities($info['smallphoto']));
        $xartist->addChild('mediumImageUrl', htmlentities($info['mediumphoto']));
        $xartist->addChild('largeImageUrl', htmlentities($info['largephoto']));

        foreach ($similars as $similar) {
            $xsimilar = $xartist->addChild('similarArtist');
            $xsimilar->addAttribute('id', ($similar['id'] !== null ? self::getArtistId($similar['id']) : "-1"));
            $xsimilar->addAttribute('name', (string)self::checkName($similar['name']));
        }
    }

    /**
     * addSimilarSongs
     * @param SimpleXMLElement $xml
     * @param array $similar_songs
     * @param string $child
     */
    public static function addSimilarSongs($xml, $similar_songs, $child)
    {
        $xsimilar = $xml->addChild(htmlspecialchars($child));
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
    public static function addSimilarSongs2($xml, $similar_songs, $child)
    {
        $xsimilar = $xml->addChild(htmlspecialchars($child));
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
     * @param boolean $includeEpisodes
     */
    public static function addPodcasts($xml, $podcasts, $includeEpisodes = true)
    {
        $xpodcasts = $xml->addChild('podcasts');
        foreach ($podcasts as $podcast) {
            $podcast->format();
            $sub_id   =(string)self::getPodcastId($podcast->id);
            $xchannel = $xpodcasts->addChild('channel');
            $xchannel->addAttribute('id', $sub_id);
            $xchannel->addAttribute('url', (string)$podcast->feed);
            $xchannel->addAttribute('title', (string)self::checkName($podcast->get_fullname()));
            $xchannel->addAttribute('description', (string)$podcast->f_description);
            if ($podcast->has_art()) {
                $xchannel->addAttribute('coverArt', 'pod-' . $sub_id);
            }
            $xchannel->addAttribute('status', 'completed');
            if ($includeEpisodes) {
                $episodes = $podcast->get_episodes();
                foreach ($episodes as $episode_id) {
                    $episode = new Podcast_Episode($episode_id);
                    self::addPodcastEpisode($xchannel, $episode);
                }
            }
        }
    }

    /**
     * addPodcastEpisode
     * @param SimpleXMLElement $xml
     * @param Podcast_Episode $episode
     * @param string $elementName
     */
    private static function addPodcastEpisode($xml, $episode, $elementName = 'episode')
    {
        $episode->format();
        $sub_id    = (string)self::getPodcastEpId($episode->id);
        $subParent = (string)self::getPodcastId($episode->podcast);
        $xepisode  = $xml->addChild(htmlspecialchars($elementName));
        $xepisode->addAttribute('id', $sub_id);
        $xepisode->addAttribute('channelId', $subParent);
        $xepisode->addAttribute('title', (string)self::checkName($episode->get_fullname()));
        $xepisode->addAttribute('album', (string)$episode->f_podcast);
        $xepisode->addAttribute('description', (string)self::checkName($episode->f_description));
        $xepisode->addAttribute('duration', (string)$episode->time);
        $xepisode->addAttribute('genre', "Podcast");
        $xepisode->addAttribute('isDir', "false");
        $xepisode->addAttribute('publishDate', $episode->f_pubdate);
        $xepisode->addAttribute('status', (string)$episode->state);
        $xepisode->addAttribute('parent', $subParent);
        if ($episode->has_art()) {
            $xepisode->addAttribute('coverArt', $subParent);
        }

        self::setIfStarred($xepisode, 'podcast_episode', $episode->id);

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
     * addNewestPodcasts
     * @param SimpleXMLElement $xml
     * @param Podcast_Episode[] $episodes
     */
    public static function addNewestPodcasts($xml, $episodes)
    {
        $xpodcasts = $xml->addChild('newestPodcasts');
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
    public static function addBookmarks($xml, $bookmarks)
    {
        $xbookmarks = $xml->addChild('bookmarks');
        foreach ($bookmarks as $bookmark) {
            self::addBookmark($xbookmarks, $bookmark);
        }
    }

    /**
     * addBookmark
     * @param SimpleXMLElement $xml
     * @param Bookmark $bookmark
     */
    private static function addBookmark($xml, $bookmark)
    {
        $xbookmark = $xml->addChild('bookmark');
        $xbookmark->addAttribute('position', (string)$bookmark->position);
        $xbookmark->addAttribute('username', $bookmark->getUserName());
        $xbookmark->addAttribute('comment', (string)$bookmark->comment);
        $xbookmark->addAttribute('created', date("c", (int)$bookmark->creation_date));
        $xbookmark->addAttribute('changed', date("c", (int)$bookmark->update_date));
        if ($bookmark->object_type == "song") {
            self::addSong($xbookmark, $bookmark->object_id, 'entry');
        } elseif ($bookmark->object_type == "video") {
            self::addVideo($xbookmark, new Video($bookmark->object_id), 'entry');
        } elseif ($bookmark->object_type == "podcast_episode") {
            self::addPodcastEpisode($xbookmark, new Podcast_Episode($bookmark->object_id), 'entry');
        }
    }

    /**
     * addChatMessages
     * @param SimpleXMLElement $xml
     * @param integer[] $messages
     */
    public static function addChatMessages($xml, $messages)
    {
        $xmessages = $xml->addChild('chatMessages');
        if (empty($messages)) {
            return;
        }
        foreach ($messages as $message) {
            $chat = new PrivateMsg($message);
            self::addMessage($xmessages, $chat);
        }
    }

    /**
     * addMessage
     * @param SimpleXMLElement $xml
     * @param PrivateMsg $message
     */
    private static function addMessage($xml, $message)
    {
        $user      = new User($message->getSenderUserId());
        $xbookmark = $xml->addChild('chatMessage');
        if ($user->fullname_public) {
            $xbookmark->addAttribute('username', (string)$user->fullname);
        } else {
            $xbookmark->addAttribute('username', (string)$user->username);
        }
        $xbookmark->addAttribute('time', (string)($message->getCreationDate() * 1000));
        $xbookmark->addAttribute('message', (string)$message->getMessage());
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
