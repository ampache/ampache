<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

/**
 * XML_Data Class
 *
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls
 *
 */
class Subsonic_XML_Data
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
     * constructor
     *
     * We don't use this, as its really a static class
     */
    private function __construct()
    {
    }

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
        return $videoid + Subsonic_XML_Data::AMPACHEID_VIDEO;
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
        // Remove all al-, ar-, ... prefixs
        $tpos = strpos((string) $object_id, "-");
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
        //       $response->addAttribute('type', 'ampache');
        $response->addAttribute('status', (string) $status);
        $response->addAttribute('version', (string) $version);

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
        self::setError($response, $code, $message);

        return $response;
    }

    /**
     * setError
     * Set error information.
     *
     * @param    SimpleXMLElement   $xml    Parent node
     * @param    integer    $code    Error code
     * @param    string     $message Error message
     */
    public static function setError($xml, $code, $message = '')
    {
        $xerr = $xml->addChild('error');
        $xerr->addAttribute('code', (string) $code);

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

        $xerr->addAttribute('message', (string) $message);
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
            $xfolder->addAttribute('id', (string) $folderid);
            $xfolder->addAttribute('name', (string) $catalog->name);
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
            $xml->addAttribute('ignoredArticles', (string) $ignoredArticles);
        }
    }

    /**
     * addArtistsIndexes
     * @param SimpleXMLElement $xml
     * @param Artist[] $artists
     * @param $lastModified
     */
    public static function addArtistsIndexes($xml, $artists, $lastModified)
    {
        $xindexes = $xml->addChild('indexes');
        self::addIgnoredArticles($xindexes);
        $xindexes->addAttribute('lastModified', number_format($lastModified * 1000, 0, '.', ''));
        self::addArtists($xindexes, $artists);
    }

    /**
     * addArtistsRoot
     * @param SimpleXMLElement $xml
     * @param Artist[] $artists
     * @param boolean $albumsSet
     */
    public static function addArtistsRoot($xml, $artists, $albumsSet = false)
    {
        $xartists        = $xml->addChild('artists');
        self::addIgnoredArticles($xartists);
        self::addArtists($xartists, $artists, true, $albumsSet);
    }

    /**
     * addArtists
     * @param SimpleXMLElement $xml
     * @param Artist[] $artists
     * @param boolean $extra
     * @param boolean $albumsSet
     */
    public static function addArtists($xml, $artists, $extra = false, $albumsSet = false)
    {
        $xlastcat     = null;
        $sharpartists = array();
        $xlastletter  = '';
        foreach ($artists as $artist) {
            if (strlen((string) $artist->name) > 0) {
                $letter = strtoupper((string) $artist->name[0]);
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
                    $xlastcat->addAttribute('name', (string) $xlastletter);
                }
            }

            if ($xlastcat != null) {
                self::addArtist($xlastcat, $artist, $extra, false, $albumsSet);
            }
        }

        // Always add # index at the end
        if (count($sharpartists) > 0) {
            $xsharpcat = $xml->addChild('index');
            $xsharpcat->addAttribute('name', '#');

            foreach ($sharpartists as $artist) {
                self::addArtist($xsharpcat, $artist, $extra, false, $albumsSet);
            }
        }
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
        $xartist = $xml->addChild('artist');
        $xartist->addAttribute('id', (string) self::getArtistId($artist->id));
        $xartist->addAttribute('name', (string) self::checkName($artist->f_full_name));
        $allalbums = array();
        if (($extra && !$albumsSet) || $albums) {
            $allalbums = $artist->get_albums();
        }

        if ($extra) {
            $xartist->addAttribute('coverArt', 'ar-' . (string) self::getArtistId($artist->id));
            if ($albumsSet) {
                $xartist->addAttribute('albumCount', (string) $artist->albums);
            } else {
                $xartist->addAttribute('albumCount', (string) count($allalbums));
            }
        }
        if ($albums) {
            foreach ($allalbums as $albumid) {
                $album = new Album($albumid);
                self::addAlbum($xartist, $album);
            }
        }
    }

    /**
     * addAlbumList
     * @param SimpleXMLElement $xml
     * @param $albums
     * @param string $elementName
     */
    public static function addAlbumList($xml, $albums, $elementName = "albumList")
    {
        $xlist = $xml->addChild(htmlspecialchars($elementName));
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
     * @param boolean $addAmpacheInfo
     * @param string $elementName
     */
    public static function addAlbum($xml, $album, $songs = false, $addAmpacheInfo = false, $elementName = "album")
    {
        $xalbum = $xml->addChild(htmlspecialchars($elementName));
        $xalbum->addAttribute('id', (string) self::getAlbumId($album->id));
        $xalbum->addAttribute('album', (string) self::checkName($album->full_name));
        $xalbum->addAttribute('title', (string) self::formatAlbum($album));
        $xalbum->addAttribute('name', (string) self::checkName($album->full_name));
        $xalbum->addAttribute('isDir', 'true');
        $xalbum->addAttribute('discNumber', (string) $album->disk);

        $album->format();
        $xalbum->addAttribute('coverArt', 'al-' . self::getAlbumId($album->id));
        $xalbum->addAttribute('songCount', (string) $album->song_count);
        $xalbum->addAttribute('duration', (string) $album->total_duration);
        $xalbum->addAttribute('artistId', (string) self::getArtistId($album->artist_id));
        $xalbum->addAttribute('parent', (string) self::getArtistId($album->artist_id));
        $xalbum->addAttribute('artist', (string) self::checkName($album->f_album_artist_name));
        if ($album->year > 0) {
            $xalbum->addAttribute('year', (string) $album->year);
        }
        if (count($album->tags) > 0) {
            $tag_values = array_values($album->tags);
            $tag        = array_shift($tag_values);
            $xalbum->addAttribute('genre', (string) $tag['name']);
        }

        $rating      = new Rating($album->id, "album");
        $user_rating = ($rating->get_user_rating() ?: 0);
        if ($user_rating > 0) {
            $xalbum->addAttribute('userRating', (string) ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xalbum->addAttribute('averageRating', (string) ceil($avg_rating));
        }

        self::setIfStarred($xalbum, 'album', $album->id);

        if ($songs) {
            $disc_ids = $album->get_group_disks_ids();
            foreach ($disc_ids as $discid) {
                $disc     = new Album($discid);
                $allsongs = $disc->get_songs();
                foreach ($allsongs as $songid) {
                    self::addSong($xalbum, $songid, $addAmpacheInfo);
                }
            }
        }
    }

    /**
     * addSong
     * @param SimpleXMLElement $xml
     * @param integer $songId
     * @param boolean $addAmpacheInfo
     * @param string $elementName
     * @return SimpleXMLElement
     */
    public static function addSong($xml, $songId, $addAmpacheInfo = false, $elementName = 'song')
    {
        $songData     = self::getSongData($songId);
        $albumData    = self::getAlbumData($songData['album']);
        $artistData   = self::getArtistData($songData['artist']);
        $catalogData  = self::getCatalogData($songData['catalog'], $songData['file']);
        //$catalog_path = rtrim((string) $catalogData[0], "/");

        return self::createSong($xml, $songData, $albumData, $artistData, $catalogData, $addAmpacheInfo, $elementName);
    }

    /**
     * getSongData
     * @param integer $songId
     * @return array
     */
    public static function getSongData($songId)
    {
        $sql = 'SELECT `song`.`id`, `song`.`file`, `song`.`catalog`, `song`.`album`, `album`.`album_artist` AS `albumartist`, `song`.`year`, `song`.`artist`, ' .
            '`song`.`title`, `song`.`bitrate`, `song`.`rate`, `song`.`mode`, `song`.`size`, `song`.`time`, `song`.`track`, ' .
            '`song`.`played`, `song`.`enabled`, `song`.`update_time`, `song`.`mbid`, `song`.`addition_time`, `song`.`license`, ' .
            '`song`.`composer`, `song`.`user_upload`, `album`.`mbid` AS `album_mbid`, `artist`.`mbid` AS `artist_mbid`, `album_artist`.`mbid` AS `albumartist_mbid` ' .
            'FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` ' .
            'LEFT JOIN `artist` AS `album_artist` ON `album_artist`.`id` = `album`.`album_artist` ' .
            'WHERE `song`.`id` = ?';
        $db_results = Dba::read($sql, array($songId));

        $results = Dba::fetch_assoc($db_results);
        if (isset($results['id'])) {
            if (AmpConfig::get('show_played_times')) {
                $results['object_cnt'] = Stats::get_object_count('song', (string) $results['id']);
            }
        }
        $extension       = pathinfo((string) $results['file'], PATHINFO_EXTENSION);
        $results['type'] = strtolower((string) $extension);
        $results['mime'] = Song::type_to_mime($results['type']);

        return $results;
    }

    /**
     * getAlbumData
     * @param integer $albumId
     * @return array
     */
    public static function getAlbumData($albumId)
    {
        $sql        = "SELECT * FROM `album` WHERE `id`='$albumId'";
        $db_results = Dba::read($sql);

        if (!$db_results) {
            return array();
        }

        return Dba::fetch_assoc($db_results);
    }

    /**
     * getArtistData
     * @param integer $artistId
     * @return array
     */
    public static function getArtistData($artistId)
    {
        $sql        = "SELECT * FROM `artist` WHERE `id`='$artistId'";
        $db_results = Dba::read($sql);

        if (!$db_results) {
            return array();
        }

        $row = Dba::fetch_assoc($db_results);

        $row['f_name']      = trim((string) $row['prefix'] . ' ' . $row['name']);
        $row['f_full_name'] = trim(trim((string) $row['prefix']) . ' ' . trim((string) $row['name']));

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
        $results         = array();
        $sqllook         = 'SELECT `catalog_type` FROM `catalog` WHERE `id` = ?';
        $db_results      = Dba::read($sqllook, [$catalogId]);
        $resultcheck     = Dba::fetch_assoc($db_results);
        if (!empty($resultcheck)) {
            $sql             = 'SELECT `path` FROM ' . 'catalog_' . $resultcheck['catalog_type'] . ' WHERE `catalog_id` = ?';
            $db_results      = Dba::read($sql, [$catalogId]);
            $result          = Dba::fetch_assoc($db_results);
            $catalog_path    = rtrim((string) $result['path'], "/");
            $results['path'] = str_replace($catalog_path . "/", "", $file_Path);
        }

        return $results;
    }

    /**
     * createSong
     * @param SimpleXMLElement $xml
     * @param $songData
     * @param $albumData
     * @param $artistData
     * @param $catalogData
     * @param boolean $addAmpacheInfo
     * @param string $elementName
     * @return SimpleXMLElement
     */
    public static function createSong($xml, $songData, $albumData, $artistData, $catalogData, $addAmpacheInfo = false, $elementName = 'song')
    {
        // Don't create entries for disabled songs
        if (!$songData['enabled']) {
            return null;
        }

        $xsong = $xml->addChild(htmlspecialchars($elementName));
        $xsong->addAttribute('id', (string) self::getSongId($songData['id']));
        $xsong->addAttribute('parent', (string) self::getAlbumId($songData['album']));
        //$xsong->addAttribute('created', );
        $xsong->addAttribute('title', (string) self::checkName($songData['title']));
        $xsong->addAttribute('isDir', 'false');
        $xsong->addAttribute('isVideo', 'false');
        $xsong->addAttribute('type', 'music');
        // $album = new Album(songData->album);
        $xsong->addAttribute('albumId', (string) self::getAlbumId($albumData['id']));
        $albumData['full_name'] = trim(trim((string) $albumData['prefix']) . ' ' . trim((string) $albumData['name']));

        $xsong->addAttribute('album', (string) self::checkName($albumData['full_name']));
        // $artist = new Artist($song->artist);
        // $artist->format();
        $xsong->addAttribute('artistId', (string) self::getArtistId($songData['artist']));
        $xsong->addAttribute('artist', (string) self::checkName($artistData['f_full_name']));
        $art_object = (AmpConfig::get('show_song_art')) ? self::getSongId($songData['id']) : self::getAlbumId($albumData['id']);
        $xsong->addAttribute('coverArt', (string) $art_object);
        $xsong->addAttribute('duration', (string) $songData['time']);
        $xsong->addAttribute('bitRate', (string) ((int) ($songData['bitrate'] / 1000)));
        if ($addAmpacheInfo) {
            $xsong->addAttribute('playCount', (string) $songData['object_cnt']);
        }
        $rating      = new Rating($songData['id'], "song");
        $user_rating = ($rating->get_user_rating() ?: 0);
        if ($user_rating > 0) {
            $xsong->addAttribute('userRating', (string) ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xsong->addAttribute('averageRating', (string) ceil($avg_rating));
        }
        self::setIfStarred($xsong, 'song', $songData['id']);
        if ($songData['track'] > 0) {
            $xsong->addAttribute('track', (string) $songData['track']);
        }
        if ($songData['year'] > 0) {
            $xsong->addAttribute('year', (string) $songData['year']);
        }
        $tags = Tag::get_object_tags('song', (int) $songData['id']);
        if (count($tags) > 0) {
            $xsong->addAttribute('genre', (string) $tags[0]['name']);
        }
        $xsong->addAttribute('size', (string) $songData['size']);
        if (Album::sanitize_disk($albumData['disk']) > 0) {
            $xsong->addAttribute('discNumber', (string) Album::sanitize_disk($albumData['disk']));
        }
        $xsong->addAttribute('suffix', (string) $songData['type']);
        $xsong->addAttribute('contentType', (string) $songData['mime']);
        // Return a file path relative to the catalog root path
        $xsong->addAttribute('path', (string) $catalogData['path']);

        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode');
        $valid_types   = Song::get_stream_types_for_type($songData['type'], 'api');
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && !in_array('native', $valid_types))) {
            // $transcode_settings = Song::get_transcode_settings_for_media(null, null, 'api', 'song');
            $transcode_type     = AmpConfig::get('encode_player_api_target', 'mp3');
            $xsong->addAttribute('transcodedSuffix', (string) $transcode_type);
            $xsong->addAttribute('transcodedContentType', Song::type_to_mime($transcode_type));
        }

        return $xsong;
    }

    /**
     * formatAlbum
     * @param Album $album
     * @return string|null
     */
    private static function formatAlbum($album)
    {
        $name = $album->full_name;
        /*        if ($album->year > 0) {
                    $name .= " [" . $album->year . "]";
                }
        */
        if ($album->disk && !$album->allow_group_disks && count($album->get_album_suite()) > 1) {
            $name .= " [" . T_('Disk') . " " . $album->disk . "]";
        }

        return self::checkName($name);
    }

    /**
     * checkName
     * @param string $name
     * @return string|null
     */
    private static function checkName($name)
    {
        // Ensure to have always a string type
        // This to fix xml=>json which can result to wrong type parsing

        if (self::$enable_json_checks && !empty($name)) {
            if (is_numeric($name)) {
                // Add space character to fail numeric test
                // Yes, it is a trick but visually acceptable
                $name = $name .= " ";
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
        if (Subsonic_XML_Data::isSong($object_id)) {
            return new Song(Subsonic_XML_Data::getAmpacheId($object_id));
        }
        if (Subsonic_XML_Data::isVideo($object_id)) {
            return new Video(Subsonic_XML_Data::getAmpacheId($object_id));
        }
        if (Subsonic_XML_Data::isPodcastEp($object_id)) {
            return new Podcast_Episode(Subsonic_XML_Data::getAmpacheId($object_id));
        }

        return null;
    } // getAmpacheObject

    /**
     * addArtistDirectory
     * @param SimpleXMLElement $xml
     * @param Artist $artist
     */
    public static function addArtistDirectory($xml, $artist)
    {
        $artist->format();
        $xdir = $xml->addChild('directory');
        $xdir->addAttribute('id', (string) self::getArtistId($artist->id));
        $xdir->addAttribute('name', (string) $artist->f_full_name);

        $allalbums = $artist->get_albums();
        foreach ($allalbums as $albumid) {
            $album = new Album($albumid);
            self::addAlbum($xdir, $album, false, false, "child");
        }
    }

    /**
     * addAlbumDirectory
     * @param SimpleXMLElement $xml
     * @param Album $album
     */
    public static function addAlbumDirectory($xml, $album)
    {
        $xdir = $xml->addChild('directory');
        $xdir->addAttribute('id', (string) self::getAlbumId($album->id));
        $xdir->addAttribute('name', (string) self::formatAlbum($album));
        $album->format();
        if ($album->artist_id) {
            $xdir->addAttribute('parent', (string) self::getArtistId($album->artist_id));
        }

        $disc_ids = $album->get_group_disks_ids();
        foreach ($disc_ids as $discid) {
            $disc     = new Album($discid);
            $allsongs = $disc->get_songs();
            foreach ($allsongs as $songid) {
                self::addSong($xdir, $songid, false, "child");
            }
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
            $xgenre->addAttribute('songCount', (string) $counts['song']);
            $xgenre->addAttribute('albumCount', (string) $counts['album']);
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
    public static function addVideo($xml, $video, $elementName = 'video')
    {
        $xvideo = $xml->addChild(htmlspecialchars($elementName));
        $xvideo->addAttribute('id', (string) self::getVideoId($video->id));
        $xvideo->addAttribute('title', (string) $video->f_full_title);
        $xvideo->addAttribute('isDir', 'false');
        $xvideo->addAttribute('coverArt', (string) self::getVideoId($video->id));
        $xvideo->addAttribute('isVideo', 'true');
        $xvideo->addAttribute('type', 'video');
        $xvideo->addAttribute('duration', (string) $video->time);
        if ($video->year > 0) {
            $xvideo->addAttribute('year', (string) $video->year);
        }
        $tags = Tag::get_object_tags('video', (int) $video->id);
        if (count($tags) > 0) {
            $xvideo->addAttribute('genre', (string) $tags[0]['name']);
        }
        $xvideo->addAttribute('size', (string) $video->size);
        $xvideo->addAttribute('suffix', (string) $video->type);
        $xvideo->addAttribute('contentType', (string) $video->mime);
        // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
        $path = basename($video->file);
        $xvideo->addAttribute('path', (string) $path);

        self::setIfStarred($xvideo, 'video', $video->id);
        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode');
        $valid_types   = Song::get_stream_types_for_type($video->type, 'api');
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && !in_array('native', $valid_types))) {
            $transcode_settings = $video->get_transcode_settings(null, 'api');
            if (!empty($transcode_settings)) {
                $transcode_type = $transcode_settings['format'];
                $xvideo->addAttribute('transcodedSuffix', (string) $transcode_type);
                $xvideo->addAttribute('transcodedContentType', Video::type_to_mime($transcode_type));
            }
        }
    }

    /**
     * addPlaylists
     * @param SimpleXMLElement $xml
     * @param $playlists
     * @param array $smartplaylists
     */
    public static function addPlaylists($xml, $playlists, $smartplaylists = array())
    {
        $xplaylists = $xml->addChild('playlists');
        foreach ($playlists as $plistid) {
            $playlist = new Playlist($plistid);
            self::addPlaylist($xplaylists, $playlist);
        }
        foreach ($smartplaylists as $splistid) {
            $smartplaylist = new Search((int) str_replace('smart_', '', (string) $splistid));
            self::addSmartPlaylist($xplaylists, $smartplaylist);
        }
    }

    /**
     * addPlaylist
     * @param SimpleXMLElement $xml
     * @param Playlist $playlist
     * @param boolean $songs
     */
    public static function addPlaylist($xml, $playlist, $songs = false)
    {
        $songcount = $playlist->get_media_count('song');
        $duration  = ($songcount > 0) ? $playlist->get_total_duration() : 0;
        $xplaylist = $xml->addChild('playlist');
        $xplaylist->addAttribute('id', (string) self::getPlaylistId($playlist->id));
        $xplaylist->addAttribute('name', (string) self::checkName($playlist->name));
        $user = new User($playlist->user);
        $xplaylist->addAttribute('owner', (string) $user->username);
        $xplaylist->addAttribute('public', ($playlist->type != "private") ? "true" : "false");
        $xplaylist->addAttribute('created', date("c", (int) $playlist->date));
        $xplaylist->addAttribute('changed', date("c", (int) $playlist->last_update));
        $xplaylist->addAttribute('songCount', (string) $songcount);
        $xplaylist->addAttribute('duration', (string) $duration);

        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $songId) {
                self::addSong($xplaylist, $songId, false, "entry");
            }
        }
    }

    /**
     * addSmartPlaylist
     * @param SimpleXMLElement $xml
     * @param Search $playlist
     * @param boolean $songs
     */
    public static function addSmartPlaylist($xml, $playlist, $songs = false)
    {
        $xplaylist = $xml->addChild('playlist');
        debug_event(self::class, 'addsmartplaylist ' . $playlist->id, 5);
        $xplaylist->addAttribute('id', (string) self::getSmartPlId($playlist->id));
        $xplaylist->addAttribute('name', (string) self::checkName($playlist->name));
        $user = new User($playlist->user);
        $xplaylist->addAttribute('owner', (string) $user->username);
        $xplaylist->addAttribute('public', ($playlist->type != "private") ? "true" : "false");
        $xplaylist->addAttribute('created', date("c", (int) $playlist->date));
        $xplaylist->addAttribute('changed', date("c", time()));

        if ($songs) {
            $allitems = $playlist->get_items();
            $xplaylist->addAttribute('songCount', (string) count($allitems));
            $duration = (count($allitems) > 0) ? Search::get_total_duration($allitems) : 0;
            $xplaylist->addAttribute('duration', (string) $duration);
            foreach ($allitems as $item) {
                $song = new Song($item['object_id']);
                self::addSong($xplaylist, $song->id, false, "entry");
            }
        } else {
            $xplaylist->addAttribute('songCount', (string) $playlist->last_count);
            $xplaylist->addAttribute('duration', (string) $playlist->last_duration);
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
            $track = self::addSong($xplaynow, $d['media'], false, "entry");
            if ($track !== null) {
                $track->addAttribute('username', (string) $d['client']->username);
                $track->addAttribute('minutesAgo', (string) (time() - ($d['expire'] - AmpConfig::get('stream_length')) / 1000));
                $track->addAttribute('playerId', (string) $d['agent']);
            }
        }
    }

    /**
     * addSearchResult
     * @param SimpleXMLElement $xml
     * @param array $artists
     * @param array $albums
     * @param array $songs
     * @param string $elementName
     */
    public static function addSearchResult($xml, $artists, $albums, $songs, $elementName = "searchResult2")
    {
        $xresult = $xml->addChild(htmlspecialchars($elementName));
        foreach ($artists as $artistid) {
            $artist = new Artist($artistid);
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
        if (Core::is_library_item($objectType)) {
            if (AmpConfig::get('userflags')) {
                $starred = new Userflag($object_id, $objectType);
                if ($res = $starred->get_flag(null, true)) {
                    $xml->addAttribute('starred', date("Y-m-d\TH:i:s\Z", (int) $res[1]));
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
     * @param string $elementName
     */
    public static function addStarred($xml, $artists, $albums, $songs, $elementName = "starred")
    {
        $xstarred = $xml->addChild(htmlspecialchars($elementName));

        foreach ($artists as $artistid) {
            $artist = new Artist($artistid);
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
        $xuser->addAttribute('username', (string) $user->username);
        $xuser->addAttribute('email', (string) $user->email);
        $xuser->addAttribute('scrobblingEnabled', 'true');
        $isManager = ($user->access >= 75);
        $isAdmin   = ($user->access >= 100);
        $xuser->addAttribute('adminRole', (string) $isAdmin ? 'true' : 'false');
        $xuser->addAttribute('settingsRole', 'true');
        $xuser->addAttribute('downloadRole', Preference::get_by_user($user->id, 'download') ? 'true' : 'false');
        $xuser->addAttribute('playlistRole', 'true');
        $xuser->addAttribute('coverArtRole', (string) $isManager ? 'true' : 'false');
        $xuser->addAttribute('commentRole', 'false');
        $xuser->addAttribute('podcastRole', 'false');
        $xuser->addAttribute('streamRole', 'true');
        $xuser->addAttribute('jukeboxRole', 'false');
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
     * addRadio
     * @param SimpleXMLElement $xml
     * @param Live_Stream $radio
     */
    public static function addRadio($xml, $radio)
    {
        $xradio = $xml->addChild('internetRadioStation ');
        $xradio->addAttribute('id', (string) $radio->id);
        $xradio->addAttribute('name', (string) self::checkName($radio->name));
        $xradio->addAttribute('streamUrl', (string) $radio->url);
        $xradio->addAttribute('homePageUrl', (string) $radio->site_url);
    }

    /**
     * addRadios
     * @param SimpleXMLElement $xml
     * @param $radios
     */
    public static function addRadios($xml, $radios)
    {
        $xradios = $xml->addChild('internetRadioStations');
        foreach ($radios as $radioid) {
            $radio = new Live_Stream($radioid);
            self::addRadio($xradios, $radio);
        }
    }

    /**
     * addShare
     * @param SimpleXMLElement $xml
     * @param Share $share
     */
    public static function addShare($xml, $share)
    {
        $xshare = $xml->addChild('share');
        $xshare->addAttribute('id', (string) $share->id);
        $xshare->addAttribute('url', (string) $share->public_url);
        $xshare->addAttribute('description', (string) $share->description);
        $user = new User($share->user);
        $xshare->addAttribute('username', (string) $user->username);
        $xshare->addAttribute('created', date("c", (int) $share->creation_date));
        if ($share->lastvisit_date > 0) {
            $xshare->addAttribute('lastVisited', date("c", (int) $share->lastvisit_date));
        }
        if ($share->expire_days > 0) {
            $xshare->addAttribute('expires', date("c", (int) $share->creation_date + ($share->expire_days * 86400)));
        }
        $xshare->addAttribute('visitCount', (string) $share->counter);

        if ($share->object_type == 'song') {
            $song = new Song($share->object_id);
            self::addSong($xshare, $song->id, false, "entry");
        } elseif ($share->object_type == 'playlist') {
            $playlist = new Playlist($share->object_id);
            $songs    = $playlist->get_songs();
            foreach ($songs as $songid) {
                self::addSong($xshare, $songid, false, "entry");
            }
        } elseif ($share->object_type == 'album') {
            $album = new Album($share->object_id);
            $songs = $album->get_songs();
            foreach ($songs as $songid) {
                self::addSong($xshare, $songid, false, "entry");
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
            $share = new Share($share_id);
            // Don't add share with max counter already reached
            if ($share->max_counter == 0 || $share->counter < $share->max_counter) {
                self::addShare($xshares, $share);
            }
        }
    }

    /**
     * addJukeboxPlaylist
     * @param SimpleXMLElement $xml
     * @param Localplay $localplay
     */
    public static function addJukeboxPlaylist($xml, Localplay $localplay)
    {
        $xjbox  = self::createJukeboxStatus($xml, $localplay, 'jukeboxPlaylist');
        $tracks = $localplay->get();
        foreach ($tracks as $track) {
            if ($track['oid']) {
                self::addSong($xjbox, (int) $track['oid'], false, 'entry');
            }
        }
    }

    /**
     * createJukeboxStatus
     * @param SimpleXMLElement $xml
     * @param Localplay $localplay
     * @param string $elementName
     * @return SimpleXMLElement
     */
    public static function createJukeboxStatus($xml, Localplay $localplay, $elementName = 'jukeboxStatus')
    {
        $xjbox  = $xml->addChild($elementName);
        $status = $localplay->status();
        $xjbox->addAttribute('currentIndex', 0);    // Not supported
        $xjbox->addAttribute('playing', ($status['state'] == 'play') ? 'true' : 'false');
        $xjbox->addAttribute('gain', (string) $status['volume']);
        $xjbox->addAttribute('position', 0);    // Not supported

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
            $text    = str_replace("\r", '', (string) $text);
            $xlyrics = $xml->addChild('lyrics', htmlspecialchars($text));
            if ($artist) {
                $xlyrics->addAttribute('artist', (string) $artist);
            }
            if ($title) {
                $xlyrics->addAttribute('title', (string) $title);
            }
        }
    }

    /**
     * addArtistInfo
     * @param SimpleXMLElement $xml
     * @param array $info
     * @param array $similars
     * @param string $child
     */
    public static function addArtistInfo($xml, $info, $similars, $child)
    {
        $artist = new Artist($info['id']);

        $xartist = $xml->addChild($child);
        $xartist->addChild('biography', htmlspecialchars(trim((string) $info['summary'])));
        $xartist->addChild('musicBrainzId', $artist->mbid);
        //$xartist->addChild('lastFmUrl', "");
        $xartist->addChild('smallImageUrl', htmlentities($info['smallphoto']));
        $xartist->addChild('mediumImageUrl', htmlentities($info['mediumphoto']));
        $xartist->addChild('largeImageUrl', htmlentities($info['largephoto']));

        foreach ($similars as $similar) {
            $xsimilar = $xartist->addChild('similarArtist');
            $xsimilar->addAttribute('id', ($similar['id'] !== null ? self::getArtistId($similar['id']) : "-1"));
            $xsimilar->addAttribute('name', (string) self::checkName($similar['name']));
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
        $xsimilar = $xml->addChild($child);
        foreach ($similar_songs as $similar_song) {
            $song = new Song($similar_song['id']);
            $song->format();
            if ($song->id) {
                self::addSong($xsimilar, $song->id);
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
            $xchannel = $xpodcasts->addChild('channel');
            $xchannel->addAttribute('id', (string) self::getPodcastId($podcast->id));
            $xchannel->addAttribute('url', (string) $podcast->feed);
            $xchannel->addAttribute('title', (string) self::checkName($podcast->f_title));
            $xchannel->addAttribute('description', (string) $podcast->f_description);
            if (Art::has_db($podcast->id, 'podcast')) {
                $xchannel->addAttribute('coverArt', 'pod-' . self::getPodcastId($podcast->id));
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
        $xepisode = $xml->addChild($elementName);
        $xepisode->addAttribute('id', (string) self::getPodcastEpId($episode->id));
        $xepisode->addAttribute('channelId', (string) self::getPodcastId($episode->podcast));
        $xepisode->addAttribute('title', (string) self::checkName($episode->f_title));
        $xepisode->addAttribute('album', (string) $episode->f_podcast);
        $xepisode->addAttribute('description', (string) self::checkName($episode->f_description));
        $xepisode->addAttribute('duration', (string) $episode->time);
        $xepisode->addAttribute('genre', "Podcast");
        $xepisode->addAttribute('isDir', "false");
        $xepisode->addAttribute('publishDate', date("c", (int) $episode->pubdate));
        $xepisode->addAttribute('status', (string) $episode->state);
        $xepisode->addAttribute('parent', (string) self::getPodcastId($episode->podcast));
        if (Art::has_db($episode->podcast, 'podcast')) {
            $xepisode->addAttribute('coverArt', (string) self::getPodcastId($episode->podcast));
        }

        self::setIfStarred($xepisode, 'podcast_episode', $episode->id);

        if ($episode->file) {
            $xepisode->addAttribute('streamId', (string) self::getPodcastEpId($episode->id));
            $xepisode->addAttribute('size', (string) $episode->size);
            $xepisode->addAttribute('suffix', (string) $episode->type);
            $xepisode->addAttribute('contentType', (string) $episode->mime);
            // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
            $path = basename($episode->file);
            $xepisode->addAttribute('path', (string) $path);
        }
    }

    /**
     * addNewestPodcastEpisodes
     * @param SimpleXMLElement $xml
     * @param Podcast_Episode[] $episodes
     */
    public static function addNewestPodcastEpisodes($xml, $episodes)
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
     * @param $bookmarks
     */
    public static function addBookmarks($xml, $bookmarks)
    {
        $xbookmarks = $xml->addChild('bookmarks');
        foreach ($bookmarks as $bookmark) {
            $bookmark->format();
            self::addBookmark($xbookmarks, $bookmark);
        }
    }

    /**
     * addBookmark
     * @param SimpleXMLElement $xml
     * @param $bookmark
     */
    private static function addBookmark($xml, $bookmark)
    {
        $xbookmark = $xml->addChild('bookmark');
        $xbookmark->addAttribute('position', (string) $bookmark->position);
        $xbookmark->addAttribute('username', (string) $bookmark->f_user);
        $xbookmark->addAttribute('comment', (string) $bookmark->comment);
        $xbookmark->addAttribute('created', date("c", (int) $bookmark->creation_date));
        $xbookmark->addAttribute('changed', date("c", (int) $bookmark->update_date));
        if ($bookmark->object_type == "song") {
            $song = new Song($bookmark->object_id);
            self::addSong($xbookmark, $song->id, false, 'entry');
        } elseif ($bookmark->object_type == "video") {
            self::addVideo($xbookmark, new Video($bookmark->object_id), 'entry');
        } elseif ($bookmark->object_type == "podcast_episode") {
            self::addPodcastEpisode($xbookmark, new Podcast_Episode($bookmark->object_id), 'entry');
        }
    }

    /**
     * addMessages
     * @param SimpleXMLElement $xml
     * @param integer[] $messages
     */
    public static function addMessages($xml, $messages)
    {
        $xmessages = $xml->addChild('chatMessages');
        if (empty($messages)) {
            return;
        }
        foreach ($messages as $message) {
            $chat = new PrivateMsg($message);
            $chat->format();
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
        $user      = new User($message->from_user);
        $xbookmark = $xml->addChild('chatMessage');
        if ($user->fullname_public) {
            $xbookmark->addAttribute('username', (string) $user->fullname);
        } else {
            $xbookmark->addAttribute('username', (string) $user->username);
        }
        $xbookmark->addAttribute('time', (string) ($message->creation_date * 1000));
        $xbookmark->addAttribute('message', (string) $message->message);
    }
} // end subsonic_xml_data.class
