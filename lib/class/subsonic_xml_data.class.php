<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
    const API_VERSION = "1.11.0";

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
     * @return integer
     */
    public static function getArtistId($artistid)
    {
        return $artistid + self::AMPACHEID_ARTIST;
    }

    /**
     * @return integer
     */
    public static function getAlbumId($albumid)
    {
        return $albumid + self::AMPACHEID_ALBUM;
    }

    public static function getSongId($songid)
    {
        return $songid + self::AMPACHEID_SONG;
    }

    /**
     * @param integer $videoid
     */
    public static function getVideoId($videoid)
    {
        return $videoid + Subsonic_XML_Data::AMPACHEID_VIDEO;
    }

    /**
     * @param integer $plistid
     */
    public static function getSmartPlId($plistid)
    {
        return $plistid + self::AMPACHEID_SMARTPL;
    }

    /**
     * @return integer
     */
    public static function getPodcastId($podcastid)
    {
        return $podcastid + self::AMPACHEID_PODCAST;
    }

    /**
     * @param integer|null $episodeid
     */
    public static function getPodcastEpId($episodeid)
    {
        return $episodeid + self::AMPACHEID_PODCASTEP;
    }

    private static function cleanId($objectid)
    {
        // Remove all al-, ar-, ... prefixs
        $tpos = strpos($objectid, "-");
        if ($tpos !== false) {
            $objectid = (int) (substr($objectid, $tpos + 1));
        }

        return $objectid;
    }

    public static function getAmpacheId($objectid)
    {
        return (self::cleanId($objectid) % self::AMPACHEID_ARTIST);
    }

    public static function getAmpacheIds($ids)
    {
        $ampids = array();
        foreach ($ids as $objectid) {
            $ampids[] = self::getAmpacheId($objectid);
        }

        return $ampids;
    }

    public static function isArtist($artistid)
    {
        return (self::cleanId($artistid) >= self::AMPACHEID_ARTIST && $artistid < self::AMPACHEID_ALBUM);
    }

    public static function isAlbum($albumid)
    {
        return (self::cleanId($albumid) >= self::AMPACHEID_ALBUM && $albumid < self::AMPACHEID_SONG);
    }

    public static function isSong($songid)
    {
        return (self::cleanId($songid) >= self::AMPACHEID_SONG && $songid < self::AMPACHEID_SMARTPL);
    }

    public static function isSmartPlaylist($plistid)
    {
        return (self::cleanId($plistid) >= self::AMPACHEID_SMARTPL && $plistid < self::AMPACHEID_VIDEO);
    }

    public static function isVideo($videoid)
    {
        $videoid = self::cleanId($videoid);

        return (self::cleanId($videoid) >= self::AMPACHEID_VIDEO && $videoid < self::AMPACHEID_PODCAST);
    }

    public static function isPodcast($podcastid)
    {
        return (self::cleanId($podcastid) >= self::AMPACHEID_PODCAST && $podcastid < self::AMPACHEID_PODCASTEP);
    }

    public static function isPodcastEp($episodeid)
    {
        return (self::cleanId($episodeid) >= self::AMPACHEID_PODCASTEP);
    }

    public static function getAmpacheType($objectid)
    {
        if (self::isArtist($objectid)) {
            return "artist";
        } elseif (self::isAlbum($objectid)) {
            return "album";
        } elseif (self::isSong($objectid)) {
            return "song";
        } elseif (self::isSmartPlaylist($objectid)) {
            return "search";
        } elseif (self::isVideo($objectid)) {
            return "video";
        } elseif (self::isPodcast($objectid)) {
            return "podcast";
        } elseif (self::isPodcastEp($objectid)) {
            return "podcast_episode";
        }

        return "";
    }

    public static function createFailedResponse($version = '')
    {
        $response = self::createResponse($version);
        $response->addAttribute('status', 'failed');
        debug_event('subsonic_xml_data.class', 'API auth fail ' . $version, 3);

        return $response;
    }

    public static function createSuccessResponse($version = '')
    {
        $response = self::createResponse($version);
        debug_event('subsonic_xml_data.class', 'API auth success: ' . $version, 5);

        return $response;
    }

    public static function createResponse($version = '')
    {
        if (empty($version)) {
            $version = self::API_VERSION;
        }
        $response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>');
        $response->addAttribute('xmlns', 'http://subsonic.org/restapi');
        //       $response->addAttribute('type', 'ampache');
        $response->addAttribute('status', 'ok');
        $response->addAttribute('version', $version);

        return $response;
    }

    public static function createError($code, $message = '', $version = '')
    {
        if (empty($version)) {
            $version = self::API_VERSION;
        }
        $response = self::createFailedResponse($version);
        self::setError($response, $code, $message);

        return $response;
    }

    /**
     * Set error information.
     *
     * @param    SimpleXMLElement   $xml    Parent node
     * @param    integer    $code    Error code
     * @param    string     $message Error message
     */
    public static function setError($xml, $code, $message = '')
    {
        $xerr = $xml->addChild('error');
        $xerr->addAttribute('code', $code);

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

        $xerr->addAttribute("message", $message);
    }

    /**
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
     * @param SimpleXMLElement $xml
     * @param integer[] $catalogs
     */
    public static function addMusicFolders($xml, $catalogs)
    {
        $xfolders = $xml->addChild('musicFolders');
        foreach ($catalogs as $folderid) {
            $catalog = Catalog::create_from_id($folderid);
            $xfolder = $xfolders->addChild('musicFolder');
            $xfolder->addAttribute('id', $folderid);
            $xfolder->addAttribute('name', $catalog->name);
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    private static function addIgnoredArticles($xml)
    {
        $ignoredArticles = AmpConfig::get('catalog_prefix_pattern');
        if (!empty($ignoredArticles)) {
            $ignoredArticles = str_replace("|", " ", $ignoredArticles);
            $xml->addAttribute('ignoredArticles', $ignoredArticles);
        }
    }

    /**
     * @param SimpleXMLElement $xml
     * @param Artist[] $artists
     */
    public static function addArtistsIndexes($xml, $artists, $lastModified)
    {
        $xindexes = $xml->addChild('indexes');
        self::addIgnoredArticles($xindexes);
        $xindexes->addAttribute('lastModified', number_format($lastModified * 1000, 0, '.', ''));
        self::addArtists($xindexes, $artists);
    }

    /**
     * @param SimpleXMLElement $xml
     * @param Artist[] $artists
     */
    public static function addArtistsRoot($xml, $artists, $albumsSet = false)
    {
        $xartists        = $xml->addChild('artists');
        self::addIgnoredArticles($xartists);
        self::addArtists($xartists, $artists, true, $albumsSet);
    }

    /**
     * @param SimpleXMLElement $xml
     * @param Artist[] $artists
     */
    public static function addArtists($xml, $artists, $extra = false, $albumsSet = false)
    {
        $xlastcat     = null;
        $sharpartists = array();
        $xlastletter  = '';
        foreach ($artists as $artist) {
            if (strlen($artist->name) > 0) {
                $letter = strtoupper($artist->name[0]);
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
                    $xlastcat->addAttribute('name', $xlastletter);
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
     * @param SimpleXMLElement $xml
     */
    public static function addArtist($xml, $artist, $extra = false, $albums = false, $albumsSet = false)
    {
        $artist->format();
        $xartist = $xml->addChild('artist');
        $xartist->addAttribute('id', self::getArtistId($artist->id));
        $xartist->addAttribute('name', self::checkName($artist->f_full_name));
        $allalbums = array();
        if (($extra && !$albumsSet) || $albums) {
            $allalbums = $artist->get_albums(null, true);
        }

        if ($extra) {
            $xartist->addAttribute('coverArt', 'ar-' . self::getArtistId($artist->id));
            if ($albumsSet) {
                $xartist->addAttribute('albumCount', $artist->albums);
            } else {
                $xartist->addAttribute('albumCount', count($allalbums));
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
     * @param SimpleXMLElement $xml
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
     * @param Album $album
     * @param SimpleXMLElement $xml
     */
    public static function addAlbum($xml, $album, $songs = false, $addAmpacheInfo = false, $elementName = "album")
    {
        $xalbum = $xml->addChild(htmlspecialchars($elementName));
        $xalbum->addAttribute('id', self::getAlbumId($album->id));
        $xalbum->addAttribute('album', self::checkName($album->full_name));
        $xalbum->addAttribute('title', self::formatAlbum($album, $elementName === "album"));
        $xalbum->addAttribute('name', self::checkName($album->full_name));
        $xalbum->addAttribute('isDir', 'true');
        $xalbum->addAttribute('discNumber', $album->disk);

        $album->format();
        $xalbum->addAttribute('coverArt', 'al-' . self::getAlbumId($album->id));
        $xalbum->addAttribute('songCount', $album->song_count);
        //FIXME total_duration on Album doesn't exist
        $xalbum->addAttribute('duration', $album->total_duration);
        $xalbum->addAttribute('artistId', self::getArtistId($album->artist_id));
        $xalbum->addAttribute('parent', self::getArtistId($album->artist_id));
        $xalbum->addAttribute('artist', self::checkName($album->f_album_artist_name));
        if ($album->year > 0) {
            $xalbum->addAttribute('year', $album->year);
        }
        if (count($album->tags) > 0) {
            $tag_values = array_values($album->tags);
            $tag        = array_shift($tag_values);
            $xalbum->addAttribute('genre', $tag['name']);
        }

        $rating      = new Rating($album->id, "album");
        $user_rating = $rating->get_user_rating();
        if ($user_rating > 0) {
            $xalbum->addAttribute('userRating', ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xalbum->addAttribute('averageRating', ceil($avg_rating));
        }

        self::setIfStarred($xalbum, 'album', $album->id);

        if ($songs) {
            $allsongs = $album->get_songs();
            foreach ($allsongs as $songid) {
                self::addSong($xalbum, $songid, $addAmpacheInfo);
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function addSong($xml, $songId, $addAmpacheInfo = false, $elementName = 'song')
    {
        $songData     = self::getSongData($songId);
        $albumData    = self::getAlbumData($songData['album']);
        $artistData   = self::getArtistData($songData['artist']);
        $catalogData  = self::getCatalogData($songData['catalog'], $songData['file']);
        //$catalog_path = rtrim($catalogData[0], "/");

        return self::createSong($xml, $songData, $albumData, $artistData, $catalogData, $addAmpacheInfo, $elementName);
    }

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
                $results['object_cnt'] = Stats::get_object_count('song', $results['id'], null);
            }
        }
        $extension       = pathinfo($results['file'], PATHINFO_EXTENSION);
        $results['type'] = strtolower($extension);
        $results['mime'] = Song::type_to_mime($results['type']);

        return $results;
    }
    public static function getAlbumData($albumId)
    {
        $sql        = "SELECT * FROM `album` WHERE `id`='$albumId'";
        $db_results = Dba::read($sql);

        if (!$db_results) {
            return array();
        }

        $row = Dba::fetch_assoc($db_results);

        return $row;
    }

    public static function getArtistData($artistId)
    {
        $sql        = "SELECT * FROM `artist` WHERE `id`='$artistId'";
        $db_results = Dba::read($sql);

        if (!$db_results) {
            return array();
        }

        $row = Dba::fetch_assoc($db_results);

        $row['f_name']      = trim($row['prefix'] . ' ' . $row['name']);
        $row['f_full_name'] = trim(trim($row['prefix']) . ' ' . trim($row['name']));

        return $row;
    }

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
            $catalog_path    = rtrim($result['path'], "/");
            $results['path'] = str_replace($catalog_path . "/", "", $file_Path);

            return $results;
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function createSong($xml, $songData, $albumData, $artistData, $catalogData, $addAmpacheInfo = false, $elementName = 'song')
    {
        // Don't create entries for disabled songs
        if (!$songData['enabled']) {
            return null;
        }

        $xsong = $xml->addChild(htmlspecialchars($elementName));
        $xsong->addAttribute('id', self::getSongId($songData['id']));
        $xsong->addAttribute('parent', self::getAlbumId($songData['album']));
        //$xsong->addAttribute('created', );
        $xsong->addAttribute('title', self::checkName($songData['title']));
        $xsong->addAttribute('isDir', 'false');
        $xsong->addAttribute('isVideo', 'false');
        $xsong->addAttribute('type', 'music');
        // $album = new Album(songData->album);
        $xsong->addAttribute('albumId', self::getAlbumId($albumData['id']));
        $albumData['full_name'] = trim(trim($albumData['prefix']) . ' ' . trim($albumData['name']));

        $xsong->addAttribute('album', self::checkName($albumData['full_name']));
        // $artist = new Artist($song->artist);
        // $artist->format();
        $xsong->addAttribute('artistId', self::getArtistId($songData['artist']));
        $xsong->addAttribute('artist', self::checkName($artistData['f_full_name']));
        $xsong->addAttribute('coverArt', self::getAlbumId($albumData['id']));
        $xsong->addAttribute('duration', $songData['time']);
        $xsong->addAttribute('bitRate', (int) ($songData['bitrate'] / 1000));
        if ($addAmpacheInfo) {
            $xsong->addAttribute('playCount', $songData['object_cnt']);
        }
        $rating      = new Rating($songData['id'], "song");
        $user_rating = $rating->get_user_rating();
        if ($user_rating > 0) {
            $xsong->addAttribute('userRating', ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xsong->addAttribute('averageRating', ceil($avg_rating));
        }
        self::setIfStarred($xsong, 'song', $songData['id']);
        if ($songData['track'] > 0) {
            $xsong->addAttribute('track', $songData['track']);
        }
        if ($songData['year'] > 0) {
            $xsong->addAttribute('year', $songData['year']);
        }
        $tags = Tag::get_object_tags('song', $songData['id']);
        if (count($tags) > 0) {
            $xsong->addAttribute('genre', $tags[0]['name']);
        }
        $xsong->addAttribute('size', $songData['size']);
        if ($albumData['disk'] > 0) {
            $xsong->addAttribute('discNumber', $albumData['disk']);
        }
        $xsong->addAttribute('suffix', $songData['type']);
        $xsong->addAttribute('contentType', $songData['mime']);
        // Return a file path relative to the catalog root path
        $xsong->addAttribute('path', $catalogData['path']);

        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode');
        $valid_types   = Song::get_stream_types_for_type($songData['type'], 'api');
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && !in_array('native', $valid_types))) {
            // $transcode_settings = Song::get_transcode_settings_for_media(null, null, 'api', 'song');
            $transcode_type     = AmpConfig::get('encode_player_api_target', 'mp3');
            $xsong->addAttribute('transcodedSuffix', $transcode_type);
            $xsong->addAttribute('transcodedContentType', Song::type_to_mime($transcode_type));
        }

        return $xsong;
    }

    /**
     * @param Album $album
     *
     * @return string|null
     */
    private static function formatAlbum($album, $checkDisk = true)
    {
        $name = $album->full_name;
        /*        if ($album->year > 0) {
                    $name .= " [" . $album->year . "]";
                }
        */
        if (($checkDisk || !AmpConfig::get('album_group')) && $album->disk) {
            $name .= " [" . T_('Disk') . " " . $album->disk . "]";
        }

        return self::checkName($name);
    }

    /**
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
     * @param SimpleXMLElement $xml
     * @param Artist $artist
     */
    public static function addArtistDirectory($xml, $artist)
    {
        $artist->format();
        $xdir = $xml->addChild('directory');
        $xdir->addAttribute('id', self::getArtistId($artist->id));
        $xdir->addAttribute('name', $artist->f_full_name);

        $allalbums = $artist->get_albums();
        foreach ($allalbums as $albumid) {
            $album = new Album($albumid);
            self::addAlbum($xdir, $album, false, false, "child");
        }
    }

    /**
     * @param SimpleXMLElement $xml
     * @param Album $album
     */
    public static function addAlbumDirectory($xml, $album)
    {
        $xdir = $xml->addChild('directory');
        $xdir->addAttribute('id', self::getAlbumId($album->id));
        $xdir->addAttribute('name', self::formatAlbum($album, false));
        $album->format();
        if ($album->artist_id) {
            $xdir->addAttribute('parent', self::getArtistId($album->artist_id));
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
     * @param SimpleXMLElement $xml
     */
    public static function addGenres($xml, $tags)
    {
        $xgenres = $xml->addChild('genres');

        foreach ($tags as $tag) {
            $otag   = new Tag($tag['id']);
            $xgenre = $xgenres->addChild('genre', htmlspecialchars($otag->name));
            $counts = $otag->count('', Core::get_global('user')->id);
            $xgenre->addAttribute("songCount", $counts['song']);
            $xgenre->addAttribute("albumCount", $counts['album']);
        }
    }

    public static function addVideos($xml, $videos)
    {
        $xvideos = $xml->addChild('videos');
        foreach ($videos as $video) {
            $video->format();
            self::addVideo($xvideos, $video);
        }
    }
    public static function addVideo($xml, $video, $elementName = 'video')
    {
        $xvideo = $xml->addChild($elementName);
        $xvideo->addAttribute('id', self::getVideoId($video->id));
        $xvideo->addAttribute('title', $video->f_full_title);
        $xvideo->addAttribute('isDir', 'false');
        $xvideo->addAttribute('coverArt', self::getVideoId($video->id));
        $xvideo->addAttribute('isVideo', 'true');
        $xvideo->addAttribute('type', 'video');
        $xvideo->addAttribute('duration', $video->time);
        if ($video->year > 0) {
            $xvideo->addAttribute('year', $video->year);
        }
        $tags = Tag::get_object_tags('video', $video->id);
        if (count($tags) > 0) {
            $xvideo->addAttribute('genre', $tags[0]['name']);
        }
        $xvideo->addAttribute('size', $video->size);
        $xvideo->addAttribute('suffix', $video->type);
        $xvideo->addAttribute('contentType', $video->mime);
        // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
        $path = basename($video->file);
        $xvideo->addAttribute('path', $path);

        self::setIfStarred($xvideo, 'video', $video->id);
        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode');
        $valid_types   = Song::get_stream_types_for_type($video->type, 'api');
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && !in_array('native', $valid_types))) {
            $transcode_settings = $video->get_transcode_settings(null, 'api');
            if ($transcode_settings) {
                $transcode_type = $transcode_settings['format'];
                $xvideo->addAttribute('transcodedSuffix', $transcode_type);
                $xvideo->addAttribute('transcodedContentType', Video::type_to_mime($transcode_type));
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function addPlaylists($xml, $playlists, $smartplaylists = array())
    {
        $xplaylists = $xml->addChild('playlists');
        foreach ($playlists as $plistid) {
            $playlist = new Playlist($plistid);
            self::addPlaylist($xplaylists, $playlist);
        }
        foreach ($smartplaylists as $splistid) {
            $smartplaylist = new Search($splistid, 'song');
            self::addSmartPlaylist($xplaylists, $smartplaylist);
        }
    }

    /**
     * @param Playlist $playlist
     * @param SimpleXMLElement $xml
     */
    public static function addPlaylist($xml, $playlist, $songs = false)
    {
        $xplaylist = $xml->addChild('playlist');
        $xplaylist->addAttribute('id', $playlist->id);
        $xplaylist->addAttribute('name', self::checkName($playlist->name));
        $user = new User($playlist->user);
        $xplaylist->addAttribute('owner', $user->username);
        $xplaylist->addAttribute('public', ($playlist->type != "private") ? "true" : "false");
        $xplaylist->addAttribute('created', date("c", $playlist->date));
        $xplaylist->addAttribute('songCount', $playlist->get_media_count('song'));
        $xplaylist->addAttribute('duration', $playlist->get_total_duration());

        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $songId) {
                self::addSong($xplaylist, $songId, false, "entry");
            }
        }
    }

    /**
     * @param Search $playlist
     * @param SimpleXMLElement $xml
     */
    public static function addSmartPlaylist($xml, $playlist, $songs = false)
    {
        $xplaylist = $xml->addChild('playlist');
        $xplaylist->addAttribute('id', self::getSmartPlId($playlist->id));
        $xplaylist->addAttribute('name', self::checkName($playlist->name));
        $user = new User($playlist->user);
        $xplaylist->addAttribute('owner', $user->username);
        $xplaylist->addAttribute('public', ($playlist->type != "private") ? "true" : "false");

        if ($songs) {
            $allitems = $playlist->get_items();
            foreach ($allitems as $item) {
                $song = new Song($item['object_id']);
                self::addSong($xplaylist, $song->id, false, "entry");
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function addRandomSongs($xml, $songs)
    {
        $xsongs = $xml->addChild('randomSongs');
        foreach ($songs as $songid) {
            self::addSong($xsongs, $songid);
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function addSongsByGenre($xml, $songs)
    {
        $xsongs = $xml->addChild('songsByGenre');
        foreach ($songs as $songid) {
            self::addSong($xsongs, $songid);
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function addNowPlaying($xml, $data)
    {
        $xplaynow = $xml->addChild('nowPlaying');
        foreach ($data as $d) {
            $track = self::addSong($xplaynow, $d['media'], false, "entry");
            if ($track !== null) {
                $track->addAttribute('username', $d['client']->username);
                $track->addAttribute('minutesAgo', (int) (time() - ($d['expire'] - AmpConfig::get('stream_length')) / 1000));
                $track->addAttribute('playerId', $d['agent']);
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml
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
     * @param string $objectType
     * @param SimpleXMLElement $xml
     */
    private static function setIfStarred($xml, $objectType, $objectId)
    {
//        $object_type = strtolower(get_class($libitem));
        if (Core::is_library_item($objectType)) {
            if (AmpConfig::get('userflags')) {
                $starred = new Userflag($objectId, $objectType);
                if ($res = $starred->get_flag(null, true)) {
                    $xml->addAttribute('starred', date("Y-m-d",$res[1]) . 'T' . date("H:i:s", $res[1]) . 'Z');
                }
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml
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
     * @param SimpleXMLElement $xml
     */
    public static function addUser($xml, $user)
    {
        $xuser = $xml->addChild('user');
        $xuser->addAttribute('username', $user->username);
        $xuser->addAttribute('email', $user->email);
        $xuser->addAttribute('scrobblingEnabled', 'true');
        $isManager = ($user->access >= 75);
        $isAdmin   = ($user->access >= 100);
        $xuser->addAttribute('adminRole', $isAdmin ? 'true' : 'false');
        $xuser->addAttribute('settingsRole', 'true');
        $xuser->addAttribute('downloadRole', Preference::get_by_user($user->id, 'download') ? 'true' : 'false');
        $xuser->addAttribute('playlistRole', 'true');
        $xuser->addAttribute('coverArtRole', $isManager ? 'true' : 'false');
        $xuser->addAttribute('commentRole', 'false');
        $xuser->addAttribute('podcastRole', 'false');
        $xuser->addAttribute('streamRole', 'true');
        $xuser->addAttribute('jukeboxRole', 'false');
        $xuser->addAttribute('shareRole', Preference::get_by_user($user->id, 'share') ? 'true' : 'false');
    }

    /**
     * @param SimpleXMLElement $xml
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
     * @param SimpleXMLElement $xml
     * @param Live_Stream $radio
     */
    public static function addRadio($xml, $radio)
    {
        $xradio = $xml->addChild('internetRadioStation ');
        $xradio->addAttribute('id', $radio->id);
        $xradio->addAttribute('name', self::checkName($radio->name));
        $xradio->addAttribute('streamUrl', $radio->url);
        $xradio->addAttribute('homePageUrl', $radio->site_url);
    }

    /**
     * @param SimpleXMLElement $xml
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
     * @param SimpleXMLElement $xml
     * @param Share $share
     */
    public static function addShare($xml, $share)
    {
        $xshare = $xml->addChild('share');
        $xshare->addAttribute('id', $share->id);
        $xshare->addAttribute('url', $share->public_url);
        $xshare->addAttribute('description', $share->description);
        $user = new User($share->user);
        $xshare->addAttribute('username', $user->username);
        $xshare->addAttribute('created', date("c", $share->creation_date));
        if ($share->lastvisit_date > 0) {
            $xshare->addAttribute('lastVisited', date("c", $share->lastvisit_date));
        }
        if ($share->expire_days > 0) {
            $xshare->addAttribute('expires', date("c", $share->creation_date + ($share->expire_days * 86400)));
        }
        $xshare->addAttribute('visitCount', $share->counter);

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
     * @param SimpleXMLElement $xml
     */
    public static function addShares($xml, $shares)
    {
        $xshares = $xml->addChild('shares');
        foreach ($shares as $shareid) {
            $share = new Share($shareid);
            // Don't add share with max counter already reached
            if ($share->max_counter == 0 || $share->counter < $share->max_counter) {
                self::addShare($xshares, $share);
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function addJukeboxPlaylist($xml, Localplay $localplay)
    {
        $xjbox  = self::createJukeboxStatus($xml, $localplay, 'jukeboxPlaylist');
        $tracks = $localplay->get();
        foreach ($tracks as $track) {
            if ($track['oid']) {
                $song = new Song($track['oid']);
                self::addSong($xjbox, $song, false, 'entry');
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function createJukeboxStatus($xml, Localplay $localplay, $elementName = 'jukeboxStatus')
    {
        $xjbox  = $xml->addChild($elementName);
        $status = $localplay->status();
        $xjbox->addAttribute('currentIndex', 0);    // Not supported
        $xjbox->addAttribute('playing', ($status['state'] == 'play') ? 'true' : 'false');
        $xjbox->addAttribute('gain', $status['volume']);
        $xjbox->addAttribute('position', 0);    // Not supported

        return $xjbox;
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function addLyrics($xml, $artist, $title, $song_id)
    {
        $song = new Song($song_id);
        $song->format();
        $song->fill_ext_info();
        $lyrics = $song->get_lyrics();

        if (!empty($lyrics) && $lyrics['text']) {
            $text    = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $lyrics['text']);
            $text    = str_replace("\r", '', $text);
            $xlyrics = $xml->addChild("lyrics", $text);
            if ($artist) {
                $xlyrics->addAttribute("artist", $artist);
            }
            if ($title) {
                $xlyrics->addAttribute("title", $title);
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function addArtistInfo($xml, $info, $similars)
    {
        $artist = new Artist($info['id']);

        $xartist = $xml->addChild("artistInfo");
        $xartist->addChild("biography", trim($info['summary']));
        $xartist->addChild("musicBrainzId", $artist->mbid);
        //$xartist->addChild("lastFmUrl", "");
        $xartist->addChild("smallImageUrl", htmlentities($info['smallphoto']));
        $xartist->addChild("mediumImageUrl", htmlentities($info['mediumphoto']));
        $xartist->addChild("largeImageUrl", htmlentities($info['largephoto']));

        foreach ($similars as $similar) {
            $xsimilar = $xartist->addChild("similarArtist");
            $xsimilar->addAttribute("id", ($similar['id'] !== null ? self::getArtistId($similar['id']) : "-1"));
            $xsimilar->addAttribute("name", self::checkName($similar['name']));
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function addSimilarSongs($xml, $similar_songs)
    {
        $xsimilar = $xml->addChild("similarSongs");
        foreach ($similar_songs as $similar_song) {
            $song = new Song($similar_song['id']);
            $song->format();
            if ($song->id) {
                self::addSong($xsimilar, $song->id);
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml
     * @param Podcast[] $podcasts
     */
    public static function addPodcasts($xml, $podcasts, $includeEpisodes = false)
    {
        $xpodcasts = $xml->addChild("podcasts");
        foreach ($podcasts as $podcast) {
            $podcast->format();
            $xchannel = $xpodcasts->addChild("channel");
            $xchannel->addAttribute("id", self::getPodcastId($podcast->id));
            $xchannel->addAttribute("url", $podcast->feed);
            $xchannel->addAttribute("title", self::checkName($podcast->f_title));
            $xchannel->addAttribute("description", $podcast->f_description);
            if (Art::has_db($podcast->id, 'podcast')) {
                $xchannel->addAttribute("coverArt", "pod-" . self::getPodcastId($podcast->id));
            }
            $xchannel->addAttribute("status", "completed");
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
     * @param Podcast_Episode $episode
     * @param SimpleXMLElement $xml
     */
    private static function addPodcastEpisode($xml, $episode, $elementName = 'episode')
    {
        $episode->format();
        $xepisode = $xml->addChild($elementName);
        $xepisode->addAttribute("id", self::getPodcastEpId($episode->id));
        $xepisode->addAttribute("channelId", self::getPodcastId($episode->podcast));
        $xepisode->addAttribute("title", self::checkName($episode->f_title));
        $xepisode->addAttribute("album", $episode->f_podcast);
        $xepisode->addAttribute("description", self::checkName($episode->f_description));
        $xepisode->addAttribute("duration", $episode->time);
        $xepisode->addAttribute("genre", "Podcast");
        $xepisode->addAttribute("isDir", "false");
        $xepisode->addAttribute("publishDate", date("c", $episode->pubdate));
        $xepisode->addAttribute("status", $episode->state);
        $xepisode->addAttribute("parent", self::getPodcastId($episode->podcast));
        if (Art::has_db($episode->podcast, 'podcast')) {
            $xepisode->addAttribute("coverArt", self::getPodcastId($episode->podcast));
        }

        self::setIfStarred($xepisode, 'episode', $episode->id);

        if ($episode->file) {
            $xepisode->addAttribute("streamId", self::getPodcastEpId($episode->id));
            $xepisode->addAttribute("size", $episode->size);
            $xepisode->addAttribute("suffix", $episode->type);
            $xepisode->addAttribute("contentType", $episode->mime);
            // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
            $path = basename($episode->file);
            $xepisode->addAttribute("path", $path);
        }
    }

    /**
     * @param SimpleXMLElement $xml
     * @param Podcast_Episode[] $episodes
     */
    public static function addNewestPodcastEpisodes($xml, $episodes)
    {
        $xpodcasts = $xml->addChild("newestPodcasts");
        foreach ($episodes as $episode) {
            $episode->format();
            self::addPodcastEpisode($xpodcasts, $episode);
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    public static function addBookmarks($xml, $bookmarks)
    {
        $xbookmarks = $xml->addChild("bookmarks");
        foreach ($bookmarks as $bookmark) {
            $bookmark->format();
            self::addBookmark($xbookmarks, $bookmark);
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    private static function addBookmark($xml, $bookmark)
    {
        $xbookmark = $xml->addChild("bookmark");
        $xbookmark->addAttribute("position", $bookmark->position);
        $xbookmark->addAttribute("username", $bookmark->f_user);
        $xbookmark->addAttribute("comment", $bookmark->comment);
        $xbookmark->addAttribute("created", date("c", $bookmark->creation_date));
        $xbookmark->addAttribute("changed", date("c", $bookmark->update_date));
        if ($bookmark->object_type == "song") {
            $song = new Song($bookmark->object_id);
            self::addSong($xbookmark, $song->id, false, 'entry');
        } elseif ($bookmark->object_type == "video") {
            self::addVideo($xbookmark, new Video($bookmark->object_id), 'entry');
        } elseif ($bookmark->object_type == "podcast_episode") {
            self::addPodcastEpisode($xbookmark, new Podcast_Episode($bookmark->object_id), 'entry');
        }
    }
}
