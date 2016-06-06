<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

    const SSERROR_GENERIC           = 0;
    const SSERROR_MISSINGPARAM      = 10;
    const SSERROR_APIVERSION_CLIENT = 20;
    const SSERROR_APIVERSION_SERVER = 30;
    const SSERROR_BADAUTH           = 40;
    const SSERROR_UNAUTHORIZED      = 50;
    const SSERROR_TRIAL             = 60;
    const SSERROR_DATA_NOTFOUND     = 70;

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

    public static function getArtistId($id)
    {
        return $id + Subsonic_XML_Data::AMPACHEID_ARTIST;
    }

    public static function getAlbumId($id)
    {
        return $id + Subsonic_XML_Data::AMPACHEID_ALBUM;
    }

    public static function getSongId($id)
    {
        return $id + Subsonic_XML_Data::AMPACHEID_SONG;
    }

    public static function getSmartPlId($id)
    {
        return $id + Subsonic_XML_Data::AMPACHEID_SMARTPL;
    }

    public static function getVideoId($id)
    {
        return $id + Subsonic_XML_Data::AMPACHEID_VIDEO;
    }
    
    public static function getPodcastId($id)
    {
        return $id + Subsonic_XML_Data::AMPACHEID_PODCAST;
    }
    
    public static function getPodcastEpId($id)
    {
        return $id + Subsonic_XML_Data::AMPACHEID_PODCASTEP;
    }
    
    private static function cleanId($id)
    {
        // Remove all al-, ar-, ... prefixs
        $tpos = strpos($id, "-");
        if ($tpos !== false) {
            $id = intval(substr($id, $tpos + 1));
        }
        return $id;
    }

    public static function getAmpacheId($id)
    {
        return (self::cleanId($id) % Subsonic_XML_Data::AMPACHEID_ARTIST);
    }

    public static function getAmpacheIds($ids)
    {
        $ampids = array();
        foreach ($ids as $id) {
            $ampids[] = self::getAmpacheId($id);
        }
        return $ampids;
    }

    public static function isArtist($id)
    {
        $id = self::cleanId($id);
        return ($id >= Subsonic_XML_Data::AMPACHEID_ARTIST && $id < Subsonic_XML_Data::AMPACHEID_ALBUM);
    }

    public static function isAlbum($id)
    {
        $id = self::cleanId($id);
        return ($id >= Subsonic_XML_Data::AMPACHEID_ALBUM && $id < Subsonic_XML_Data::AMPACHEID_SONG);
    }

    public static function isSong($id)
    {
        $id = self::cleanId($id);
        return ($id >= Subsonic_XML_Data::AMPACHEID_SONG && $id < Subsonic_XML_Data::AMPACHEID_SMARTPL);
    }

    public static function isSmartPlaylist($id)
    {
        $id = self::cleanId($id);
        return ($id >= Subsonic_XML_Data::AMPACHEID_SMARTPL && $id < Subsonic_XML_Data::AMPACHEID_VIDEO);
    }

    public static function isVideo($id)
    {
        $id = self::cleanId($id);
        return ($id >= Subsonic_XML_Data::AMPACHEID_VIDEO && $id < Subsonic_XML_Data::AMPACHEID_PODCAST);
    }
    
    public static function isPodcast($id)
    {
        $id = self::cleanId($id);
        return ($id >= Subsonic_XML_Data::AMPACHEID_PODCAST && $id < Subsonic_XML_Data::AMPACHEID_PODCASTEP);
    }
    
    public static function isPodcastEp($id)
    {
        $id = self::cleanId($id);
        return ($id >= Subsonic_XML_Data::AMPACHEID_PODCASTEP);
    }
    
    public static function getAmpacheType($id)
    {
        if (self::isArtist($id)) {
            return "artist";
        } elseif (self::isAlbum($id)) {
            return "album";
        } elseif (self::isSong($id)) {
            return "song";
        } elseif (self::isSmartPlaylist($id)) {
            return "search";
        } elseif (self::isVideo($id)) {
            return "video";
        } elseif (self::isPodcast($id)) {
            return "podcast";
        } elseif (self::isPodcastEp($id)) {
            return "podcast_episode";
        }
        
        return "";
    }

    public static function createFailedResponse($version = "")
    {
        $response = self::createResponse($version);
        $response->addAttribute('status', 'failed');
        return $response;
    }

    public static function createSuccessResponse($version = "")
    {
        $response = self::createResponse($version);
        $response->addAttribute('status', 'ok');
        return $response;
    }

    public static function createResponse($version = "")
    {
        if (empty($version)) {
            $version = Subsonic_XML_Data::API_VERSION;
        }
        $response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>');
        $response->addAttribute('xmlns', 'http://subsonic.org/restapi');
        $response->addAttribute('type', 'ampache');
        $response->addAttribute('version', $version);
        return $response;
    }

    public static function createError($code, $message = "", $version = "")
    {
        if (empty($version)) {
            $version = Subsonic_XML_Data::API_VERSION;
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
    public static function setError($xml, $code, $message = "")
    {
        $xerr = $xml->addChild('error');
        $xerr->addAttribute('code', $code);

        if (empty($message)) {
            switch ($code) {
                case Subsonic_XML_Data::SSERROR_GENERIC:            $message = "A generic error."; break;
                case Subsonic_XML_Data::SSERROR_MISSINGPARAM:       $message = "Required parameter is missing."; break;
                case Subsonic_XML_Data::SSERROR_APIVERSION_CLIENT:  $message = "Incompatible Subsonic REST protocol version. Client must upgrade."; break;
                case Subsonic_XML_Data::SSERROR_APIVERSION_SERVER:  $message = "Incompatible Subsonic REST protocol version. Server must upgrade."; break;
                case Subsonic_XML_Data::SSERROR_BADAUTH:            $message = "Wrong username or password."; break;
                case Subsonic_XML_Data::SSERROR_UNAUTHORIZED:       $message = "User is not authorized for the given operation."; break;
                case Subsonic_XML_Data::SSERROR_TRIAL:              $message = "The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details."; break;
                case Subsonic_XML_Data::SSERROR_DATA_NOTFOUND:      $message = "The requested data was not found."; break;
            }
        }

        $xerr->addAttribute("message", $message);
    }

    public static function addLicense($xml)
    {
        $xlic = $xml->addChild('license');
        $xlic->addAttribute('valid', 'true');
        $xlic->addAttribute('email', 'webmaster@ampache.org');
        $xlic->addAttribute('key', 'ABC123DEF');
        $xlic->addAttribute('date', '2009-09-03T14:46:43');
    }

    public static function addMusicFolders($xml, $catalogs)
    {
        $xfolders = $xml->addChild('musicFolders');
        foreach ($catalogs as $id) {
            $catalog = Catalog::create_from_id($id);
            $xfolder = $xfolders->addChild('musicFolder');
            $xfolder->addAttribute('id', $id);
            $xfolder->addAttribute('name', $catalog->name);
        }
    }

    public static function addArtistsIndexes($xml, $artists, $lastModified)
    {
        $xindexes = $xml->addChild('indexes');
        $xindexes->addAttribute('lastModified', number_format($lastModified * 1000, 0, '.', ''));
        self::addArtists($xindexes, $artists);
    }

    public static function addArtistsRoot($xml, $artists, $albumsSet = false)
    {
        $xartists        = $xml->addChild('artists');
        $ignoredArticles = AmpConfig::get('catalog_prefix_pattern');
        if (!empty($ignoredArticles)) {
            $ignoredArticles = str_replace("|", ",", $ignoredArticles);
            $xartists->addAttribute('ignoredArticles', $ignoredArticles);
        }
        self::addArtists($xartists, $artists, true, $albumsSet);
    }

    public static function addArtists($xml, $artists, $extra=false, $albumsSet = false)
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

    public static function addArtist($xml, $artist, $extra=false, $albums=false, $albumsSet = false)
    {
        $xartist = $xml->addChild('artist');
        $xartist->addAttribute('id', self::getArtistId($artist->id));
        $xartist->addAttribute('name', self::checkName($artist->name));

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
            foreach ($allalbums as $id) {
                $album = new Album($id);
                self::addAlbum($xartist, $album);
            }
        }
    }

    public static function addAlbumList($xml, $albums, $elementName="albumList")
    {
        $xlist = $xml->addChild(htmlspecialchars($elementName));
        foreach ($albums as $id) {
            $album = new Album($id);
            self::addAlbum($xlist, $album);
        }
    }

    public static function addAlbum($xml, $album, $songs=false, $addAmpacheInfo=false, $elementName="album")
    {
        $xalbum = $xml->addChild(htmlspecialchars($elementName));
        $xalbum->addAttribute('id', self::getAlbumId($album->id));
        $xalbum->addAttribute('album', self::checkName($album->name));
        $xalbum->addAttribute('title', self::formatAlbum($album, $elementName === "album"));
        $xalbum->addAttribute('name', self::checkName($album->name));
        $xalbum->addAttribute('isDir', 'true');
        $album->format();
        if ($album->has_art) {
            $xalbum->addAttribute('coverArt', 'al-' . self::getAlbumId($album->id));
        }
        $xalbum->addAttribute('songCount', $album->song_count);
        $xalbum->addAttribute('duration', $album->total_duration);
        $xalbum->addAttribute('artistId', self::getArtistId($album->artist_id));
        $xalbum->addAttribute('parent', self::getArtistId($album->artist_id));
        $xalbum->addAttribute('artist', self::checkName($album->artist_name));
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
        
        self::setIfStarred($xalbum, $album);

        if ($songs) {
            $allsongs = $album->get_songs();
            foreach ($allsongs as $id) {
                $song = new Song($id);
                self::addSong($xalbum, $song, $addAmpacheInfo);
            }
        }
    }

    public static function addSong($xml, $song, $addAmpacheInfo=false, $elementName='song')
    {
        self::createSong($xml, $song, $addAmpacheInfo, $elementName);
    }

    public static function createSong($xml, $song, $addAmpacheInfo=false, $elementName='song')
    {
        // Don't create entries for disabled songs
        if (!$song->enabled) {
            return null;
        }
        
        $xsong = $xml->addChild(htmlspecialchars($elementName));
        $xsong->addAttribute('id', self::getSongId($song->id));
        $xsong->addAttribute('parent', self::getAlbumId($song->album));
        //$xsong->addAttribute('created', );
        $xsong->addAttribute('title', self::checkName($song->title));
        $xsong->addAttribute('isDir', 'false');
        $xsong->addAttribute('isVideo', 'false');
        $xsong->addAttribute('type', 'music');
        $album = new Album($song->album);
        $xsong->addAttribute('albumId', self::getAlbumId($album->id));
        $xsong->addAttribute('album', self::checkName($album->name));
        $artist = new Artist($song->artist);
        $xsong->addAttribute('artistId', self::getArtistId($song->artist));
        $xsong->addAttribute('artist', self::checkName($artist->name));
        $xsong->addAttribute('coverArt', self::getAlbumId($album->id));
        $xsong->addAttribute('duration', $song->time);
        $xsong->addAttribute('bitRate', intval($song->bitrate / 1000));
        if ($addAmpacheInfo) {
            $xsong->addAttribute('playCount', $song->object_cnt);
        }
        $rating      = new Rating($song->id, "song");
        $user_rating = $rating->get_user_rating();
        if ($user_rating > 0) {
            $xsong->addAttribute('userRating', ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xsong->addAttribute('averageRating', ceil($avg_rating));
        }
        self::setIfStarred($xsong, $song);
        if ($song->track > 0) {
            $xsong->addAttribute('track', $song->track);
        }
        if ($song->year > 0) {
            $xsong->addAttribute('year', $song->year);
        }
        $tags = Tag::get_object_tags('song', $song->id);
        if (count($tags) > 0) {
            $xsong->addAttribute('genre', $tags[0]['name']);
        }
        $xsong->addAttribute('size', $song->size);
        if ($album->disk > 0) {
            $xsong->addAttribute('discNumber', $album->disk);
        }
        $xsong->addAttribute('suffix', $song->type);
        $xsong->addAttribute('contentType', $song->mime);
        // Return a file path relative to the catalog root path
        $path = $song->get_rel_path();
        $xsong->addAttribute('path', $path);

        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode');
        $valid_types   = Song::get_stream_types_for_type($song->type, 'api');
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && !in_array('native', $valid_types))) {
            $transcode_settings = $song->get_transcode_settings(null, 'api');
            if ($transcode_settings) {
                $transcode_type = $transcode_settings['format'];
                $xsong->addAttribute('transcodedSuffix', $transcode_type);
                $xsong->addAttribute('transcodedContentType', Song::type_to_mime($transcode_type));
            }
        }

        return $xsong;
    }

    private static function formatAlbum($album, $checkDisk = true)
    {
        $name = $album->name;
        if ($album->year > 0) {
            $name .= " [" . $album->year . "]";
        }

        if (($checkDisk || !AmpConfig::get('album_group')) && $album->disk) {
            $name .= " [" . T_('Disk') . " " . $album->disk . "]";
        }

        return self::checkName($name);
    }
    
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
        return $name;
    }

    public static function addArtistDirectory($xml, $artist)
    {
        $xdir = $xml->addChild('directory');
        $xdir->addAttribute('id', self::getArtistId($artist->id));
        $xdir->addAttribute('name', $artist->name);

        $allalbums = $artist->get_albums();
        foreach ($allalbums as $id) {
            $album = new Album($id);
            self::addAlbum($xdir, $album, false, false, "child");
        }
    }

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
        foreach ($disc_ids as $id) {
            $disc     = new Album($id);
            $allsongs = $disc->get_songs();
            foreach ($allsongs as $id) {
                $song = new Song($id);
                self::addSong($xdir, $song, false, "child");
            }
        }
    }

    public static function addGenres($xml, $tags)
    {
        $xgenres = $xml->addChild('genres');

        foreach ($tags as $tag) {
            $otag   = new Tag($tag['id']);
            $xgenre = $xgenres->addChild('genre', htmlspecialchars($otag->name));
            $counts = $otag->count('', $GLOBALS['user']->id);
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
        $xvideo->addAttribute('path', $video);
        
        self::setIfStarred($xvideo, $song);

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

    public static function addPlaylists($xml, $playlists, $smartplaylists = array())
    {
        $xplaylists = $xml->addChild('playlists');
        foreach ($playlists as $id) {
            $playlist = new Playlist($id);
            self::addPlaylist($xplaylists, $playlist);
        }
        foreach ($smartplaylists as $id) {
            $smartplaylist = new Search($id, 'song');
            self::addSmartPlaylist($xplaylists, $smartplaylist);
        }
    }

    public static function addPlaylist($xml, $playlist, $songs=false)
    {
        $xplaylist = $xml->addChild('playlist');
        $xplaylist->addAttribute('id', $playlist->id);
        $xplaylist->addAttribute('name', $playlist->name);
        $user = new User($playlist->user);
        $xplaylist->addAttribute('owner', $user->username);
        $xplaylist->addAttribute('public', ($playlist->type != "private") ? "true" : "false");
        $xplaylist->addAttribute('created', date("c", $playlist->date));
        $xplaylist->addAttribute('songCount', $playlist->get_media_count('song'));
        $xplaylist->addAttribute('duration', $playlist->get_total_duration());

        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $id) {
                $song = new Song($id);
                self::addSong($xplaylist, $song, false, "entry");
            }
        }
    }

    public static function addSmartPlaylist($xml, $playlist, $songs=false)
    {
        $xplaylist = $xml->addChild('playlist');
        $xplaylist->addAttribute('id', self::getSmartPlId($playlist->id));
        $xplaylist->addAttribute('name', $playlist->name);
        $user = new User($playlist->user);
        $xplaylist->addAttribute('owner', $user->username);
        $xplaylist->addAttribute('public', ($playlist->type != "private") ? "true" : "false");

        if ($songs) {
            $allitems = $playlist->get_items();
            foreach ($allitems as $item) {
                $song = new Song($item['object_id']);
                self::addSong($xplaylist, $song, false, "entry");
            }
        }
    }

    public static function addRandomSongs($xml, $songs)
    {
        $xsongs = $xml->addChild('randomSongs');
        foreach ($songs as $id) {
            $song = new Song($id);
            self::addSong($xsongs, $song);
        }
    }

    public static function addSongsByGenre($xml, $songs)
    {
        $xsongs = $xml->addChild('songsByGenre');
        foreach ($songs as $id) {
            $song = new Song($id);
            self::addSong($xsongs, $song);
        }
    }

    public static function addNowPlaying($xml, $data)
    {
        $xplaynow = $xml->addChild('nowPlaying');
        foreach ($data as $d) {
            $track = self::createSong($xplaynow, $d['media'], false, "entry");
            if ($track !== null) {
                $track->addAttribute('username', $d['client']->username);
                $track->addAttribute('minutesAgo', intval(time() - ($d['expire'] - AmpConfig::get('stream_length')) / 1000));
                $track->addAttribute('playerId', $d['agent']);
            }
        }
    }

    public static function addSearchResult($xml, $artists, $albums, $songs, $elementName = "searchResult2")
    {
        $xresult = $xml->addChild(htmlspecialchars($elementName));
        foreach ($artists as $id) {
            $artist = new Artist($id);
            self::addArtist($xresult, $artist);
        }
        foreach ($albums as $id) {
            $album = new Album($id);
            self::addAlbum($xresult, $album);
        }
        foreach ($songs as $id) {
            $song = new Song($id);
            self::addSong($xresult, $song);
        }
    }
    
    private static function setIfStarred($xml, $libitem)
    {
        $object_type = strtolower(get_class($libitem));
        if (Core::is_library_item($object_type)) {
            if (AmpConfig::get('userflags')) {
                $starred = new Userflag($libitem->id, $object_type);
                if ($starred->get_flag()) {
                    $xml->addAttribute('starred', 'true');
                }
            }
        }
    }

    public static function addStarred($xml, $artists, $albums, $songs, $elementName="starred")
    {
        $xstarred = $xml->addChild(htmlspecialchars($elementName));

        foreach ($artists as $id) {
            $artist = new Artist($id);
            self::addArtist($xstarred, $artist);
        }

        foreach ($albums as $id) {
            $album = new Album($id);
            self::addAlbum($xstarred, $album);
        }

        foreach ($songs as $id) {
            $song = new Song($id);
            self::addSong($xstarred, $song);
        }
    }

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

    public static function addUsers($xml, $users)
    {
        $xusers = $xml->addChild('users');
        foreach ($users as $id) {
            $user = new User($id);
            self::addUser($xusers, $user);
        }
    }

    public static function addRadio($xml, $radio)
    {
        $xradio = $xml->addChild('internetRadioStation ');
        $xradio->addAttribute('id', $radio->id);
        $xradio->addAttribute('name', $radio->name);
        $xradio->addAttribute('streamUrl', $radio->url);
        $xradio->addAttribute('homePageUrl', $radio->site_url);
    }

    public static function addRadios($xml, $radios)
    {
        $xradios = $xml->addChild('internetRadioStations');
        foreach ($radios as $id) {
            $radio = new Live_Stream($id);
            self::addRadio($xradios, $radio);
        }
    }

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
            self::addSong($xshare, $song, false, "entry");
        } elseif ($share->object_type == 'playlist') {
            $playlist = new Playlist($share->object_id);
            $songs    = $playlist->get_songs();
            foreach ($songs as $id) {
                $song = new Song($id);
                self::addSong($xshare, $song, false, "entry");
            }
        } elseif ($share->object_type == 'album') {
            $album = new Album($share->object_id);
            $songs = $album->get_songs();
            foreach ($songs as $id) {
                $song = new Song($id);
                self::addSong($xshare, $song, false, "entry");
            }
        }
    }

    public static function addShares($xml, $shares)
    {
        $xshares = $xml->addChild('shares');
        foreach ($shares as $id) {
            $share = new Share($id);
            // Don't add share with max counter already reached
            if ($share->max_counter == 0 || $share->counter < $share->max_counter) {
                self::addShare($xshares, $share);
            }
        }
    }

    public static function addJukeboxPlaylist($xml, Localplay $localplay)
    {
        $xjbox  = self::createJukeboxStatus($xml, $localplay, 'jukeboxPlaylist');
        $tracks = $localplay->get();
        foreach ($tracks as $track) {
            if ($track['oid']) {
                $song = new Song($track['oid']);
                self::createSong($xjbox, $song, false, 'entry');
            }
        }
    }

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

    public static function addLyrics($xml, $artist, $title, $song_id)
    {
        $song = new Song($song_id);
        $song->format();
        $song->fill_ext_info();
        $lyrics = $song->get_lyrics();

        if ($lyrics && $lyrics['text']) {
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

    public static function addSimilarSongs($xml, $similar_songs)
    {
        $xsimilar = $xml->addChild("similarSongs");
        foreach ($similar_songs as $similar_song) {
            $song = new Song($similar_song['id']);
            $song->format();
            if ($song->id) {
                self::addSong($xsimilar, $song);
            }
        }
    }
    
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
    
    private static function addPodcastEpisode($xml, $episode, $elementName = 'episode')
    {
        $episode->format();
        $xepisode = $xml->addChild($elementName);
        $xepisode->addAttribute("id", self::getPodcastEpId($episode->id));
        $xepisode->addAttribute("channelId", self::getPodcastId($episode->podcast));
        $xepisode->addAttribute("title", $episode->f_title);
        $xepisode->addAttribute("album", $episode->f_podcast);
        $xepisode->addAttribute("description", $episode->f_description);
        $xepisode->addAttribute("duration", $episode->time);
        $xepisode->addAttribute("genre", "Podcast");
        $xepisode->addAttribute("isDir", "false");
        $xepisode->addAttribute("publishDate", date("c", $episode->pubdate));
        $xepisode->addAttribute("status", $episode->state);
        $xepisode->addAttribute("parent", self::getPodcastId($episode->podcast));
        if (Art::has_db($episode->podcast, 'podcast')) {
            $xepisode->addAttribute("coverArt", self::getPodcastId($episode->podcast));
        }
        
        self::setIfStarred($xepisode, $episode);
        
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
    
    public static function addNewestPodcastEpisodes($xml, $episodes)
    {
        $xpodcasts = $xml->addChild("newestPodcasts");
        foreach ($episodes as $episode) {
            $episode->format();
            self::addPodcastEpisode($xpodcasts, $episode);
        }
    }
    
    public static function addBookmarks($xml, $bookmarks)
    {
        $xbookmarks = $xml->addChild("bookmarks");
        foreach ($bookmarks as $bookmark) {
            $bookmark->format();
            self::addBookmark($xbookmarks, $bookmark);
        }
    }
    
    private static function addBookmark($xml, $bookmark)
    {
        $xbookmark = $xml->addChild("bookmark");
        $xbookmark->addAttribute("position", $bookmark->position);
        $xbookmark->addAttribute("username", $bookmark->f_user);
        $xbookmark->addAttribute("comment", $bookmark->comment);
        $xbookmark->addAttribute("created", date("c", $bookmark->creation_date));
        $xbookmark->addAttribute("changed", date("c", $bookmark->update_date));
        if ($bookmark->object_type == "song") {
            self::addSong($xbookmark, new Song($bookmark->object_id), false, 'entry');
        } elseif ($bookmark->object_type == "video") {
            self::addVideo($xbookmark, new Video($bookmark->object_id), 'entry');
        } elseif ($bookmark->object_type == "podcast_episode") {
            self::addPodcastEpisode($xbookmark, new Podcast_Episode($bookmark->object_id), 'entry');
        }
    }
}
