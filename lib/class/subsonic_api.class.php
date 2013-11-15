<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 * Subsonic Class
 *
 * This class wrap Ampache to Subsonic API functions. See http://www.subsonic.org/pages/api.jsp
 * These are all static calls.
 *
 */
class Subsonic_Api {
        
    /**
     * constructor
     * This really isn't anything to do here, so it's private
     */
    private function __construct() {
    
    }
    
    public static function check_version($input, $version = "1.0.0", $addheader = false) {
        if (version_compare($input['v'], $version) < 0) {
            ob_end_clean();
            if ($addheader) header("Content-type: text/xml; charset=" . Config::get('site_charset'));
            echo Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_APIVERSION_CLIENT)->asXml();
            exit;
        }
    }
    
    public static function check_parameter($parameter, $addheader = false) {
        if (empty($parameter)) {
            ob_end_clean();
            if ($addheader) header("Content-type: text/xml; charset=" . Config::get('site_charset'));
            echo Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM)->asXml();
            exit;
        }
    }
    
    public static function follow_stream($url) {
        // Stream media, easier to redirect to the dedicated page
        header("Location: " . $url);
    }
    

    /**
     * ping
     * Simple server ping to test connectivity with the server.
     * Takes no parameter.
     */
    public static function ping($input) {
        self::check_version($input);
        
        echo Subsonic_XML_Data::createSuccessResponse()->asXml();
    }
    
    /**
     * getLicense
     * Get details about the software license. Always return a valid default license.
     * Takes no parameter.
     */
    public static function getlicense($input) {
        self::check_version($input);
        
        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addLicense($r);
        echo $r->asXml();
    }
    
    /**
     * getMusicFolders
     * Get all configured top-level music folders (= ampache catalogs).
     * Takes no parameter.
     */
     public static function getmusicfolders($input) {
        self::check_version($input);
               
        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addMusicFolders($r, Catalog::get_catalogs());
        echo $r->asXml();
    }
    
    /**
     * getIndexes
     * Get an indexed structure of all artists.
     * Takes optional musicFolderId and optional ifModifiedSince in parameters.
     */
     public static function getindexes($input) {
        self::check_version($input);
        
        $musicFolderId = $input['musicFolderId'];
        $ifModifiedSince = $input['ifModifiedSince'];
        
        $catalogs = array();
        if (!empty($musicFolderId)) {
            $catalogs[] = $musicFolderId;
        } else {
            $catalogs = Catalog::get_catalogs();
        }
        
        $lastmodified = 0;
        $fcatalogs = array();
        
        foreach($catalogs as $id) {
            $clastmodified = 0;
            $catalog = new Catalog($id);
            
            if ($catalog->last_update > $clastmodified) $clastmodified = $catalog->last_update;
            if ($catalog->last_add > $clastmodified) $clastmodified = $catalog->last_add;
            if ($catalog->last_clean > $clastmodified) $clastmodified = $catalog->last_clean;
            
            if ($clastmodified > $lastmodified) $lastmodified = $clastmodified;
            if (!empty($ifModifiedSince) && $clastmodified > $ifModifiedSince) $fcatalogs[] = $id;
        }
        if (empty($ifModifiedSince)) $fcatalogs = $catalogs;
        
        $r = Subsonic_XML_Data::createSuccessResponse();
        if (count($fcatalogs) > 0) {
            $artists = Catalog::get_artists($fcatalogs);
            Subsonic_XML_Data::addArtistsIndexes($r, $artists, $lastmodified);
        }
        echo $r->asXml();
    }
    
    /**
     * getMusicDirectory
     * Get a list of all files in a music directory.
     * Takes the directory id in parameters.
     */
     public static function getmusicdirectory($input) {
        self::check_version($input);
        
        $id = $input['id'];
        self::check_parameter($id);
        
        $r = Subsonic_XML_Data::createSuccessResponse();
        if (Subsonic_XML_Data::isArtist($id)) {
            $artist = new Artist(Subsonic_XML_Data::getAmpacheId($id));
            Subsonic_XML_Data::addArtistDirectory($r, $artist);
        }
        else if(Subsonic_XML_Data::isAlbum($id)) {
            $album = new Album(Subsonic_XML_Data::getAmpacheId($id));
            Subsonic_XML_Data::addAlbumDirectory($r, $album);
        }
        echo $r->asXml();
     }
     
    /**
     * getGenres
     * Get all genres.
     * Takes no parameter.
     */
    public static function getgenres($input) {
        self::check_version($input, "1.9.0");
        
        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addGenres($r, Tag::get_tags());
        echo $r->asXml();
    }
    
    /**
     * getArtists
     * Get all artists.
     * Takes no parameter.
     */
    public static function getartists($input) {
        self::check_version($input, "1.8.0");
    
        $r = Subsonic_XML_Data::createSuccessResponse();
        $artists = Catalog::get_artists(Catalog::get_catalogs());
        Subsonic_XML_Data::addArtistsRoot($r, $artists);
        echo $r->asXml();
    }
    
    /**
     * getArtist
     * Get details fro an artist, including a list of albums.
     * Takes the artist id in parameter.
     */
    public static function getartist($input) {
        self::check_version($input, "1.8.0");
        
        $artistid = $input['id'];
        self::check_parameter($artistid);
        
        $artist = new Artist(Subsonic_XML_Data::getAmpacheId($artistid));
        if (empty($artist->name)) {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, "Artist not found.");
        } else {
            $r = Subsonic_XML_Data::createSuccessResponse();
            Subsonic_XML_Data::addArtist($r, $artist, true, true);
        }
        echo $r->asXml();
    }
    
    /**
     * getAlbum
     * Get details for an album, including a list of songs.
     * Takes the album id in parameter.
     */
    public static function getalbum($input) {
        self::check_version($input, "1.8.0");
        
        $albumid = $input['id'];
        self::check_parameter($albumid);
        
        $album = new Album(Subsonic_XML_Data::getAmpacheId($albumid));
        if (empty($album->name)) {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, "Album not found.");
        } else {
            $r = Subsonic_XML_Data::createSuccessResponse();
            Subsonic_XML_Data::addAlbum($r, $album, true);
        }
        
        echo $r->asXml();
    }
    
    /**
     * getVideos
     * Get all videos.
     * Takes no parameter.
     * Not supported yet.
     */
    public static function getvideos($input) {
        self::check_version($input, "1.8.0");
        
        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addVideos($r);
        echo $r->asXml();
    }
    
    /**
     * getAlbumList
     * Get a list of random, newest, highest rated etc. albums.
     * Takes the list type with optional size and offset in parameters.
     */
    public static function getalbumlist($input, $elementName="albumList") {
        self::check_version($input, "1.2.0");

        $type = $input['type'];
        self::check_parameter($type);

        $size = $input['size'];
        $offset = $input['offset'];

        $albums = array();
        if ($type == "random") {
            $albums = Album::get_random($size);
        } else if ($type == "newest") {
            $albums = Stats::get_newest("album", $size, $offset);    
        } else if ($type == "highest") {
            $albums = Rating::get_highest("album", $size, $offset);    
        } else if ($type == "frequent") {
            $albums = Stats::get_top("album", $size, '', $offset);
        } else if ($type == "recent") {
            $albums = Stats::get_recent("album", $size, $offset);
        }

        if (count($albums)) {
            $r = Subsonic_XML_Data::createSuccessResponse();
            Subsonic_XML_Data::addAlbumList($r, $albums, $elementName);
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        }

        echo $r->asXml();
    }
    
    /**
     * getAlbumList2
     * See getAlbumList.
     */
    public static function getalbumlist2($input) {
        self::check_version($input, "1.8.0");
        self::getAlbumList($input, "albumList2");
    }
    
    /**
     * getRandomSongs
     * Get random songs matching the given criteria.
     * Takes the optional size, genre, fromYear, toYear and music folder id in parameters.
     */
    public static function getrandomsongs($input) {
        self::check_version($input, "1.2.0");

        $size = $input['size'];
        if (!$size) $size = 10;
        $genre = $input['genre'];
        $fromYear = $input['fromYear'];
        $toYear = $input['toYear'];
        $musicFolderId = $input['musicFolderId'];
        
        $search = array();
        $search['limit'] = $size;
        $search['random'] = $size;
        $search['type'] = "song";
        $i = 0;
        if ($genre) {
            $search['rule_'.$i.'_input'] = $genre;
            $search['rule_'.$i.'_operator'] = 0;
            $search['rule_'.$i.''] = "tag";
            ++$i;
        }
        if ($fromYear) {
            $search['rule_'.$i.'_input'] = $fromYear;
            $search['rule_'.$i.'_operator'] = 0;
            $search['rule_'.$i.''] = "year";
            ++$i;
        }
        if ($toYear) {
            $search['rule_'.$i.'_input'] = $toYear;
            $search['rule_'.$i.'_operator'] = 1;
            $search['rule_'.$i.''] = "year";
            ++$i;
        }
        if ($musicFolderId) {
            if (Subsonic_XML_Data::isArtist($musicFolderId)) {
                $artist = new Artist(Subsonic_XML_Data::getAmpacheId($musicFolderId));
                $finput = $artist->name;
                $ftype = "artist";
            } else if (Subsonic_XML_Data::isAlbum($musicFolderId)) {
                $album = new Album(Subsonic_XML_Data::getAmpacheId($musicFolderId));
                $finput = $album->name;
                $ftype = "artist";
            }
            $search['rule_'.$i.'_input'] = $finput;
            $search['rule_'.$i.'_operator'] = 4;
            $search['rule_'.$i.''] = $ftype;
            ++$i;
        }
        $songs = Random::advanced("song", $search);

        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addRandomSongs($r, $songs);
        echo $r->asXml();
    }
    
    /**
     * getSongsByGenre
     * Get songs in a given genre.
     * Takes the genre with optional count and offset in parameters.
     */
    public static function getsongsbygenre($input) {
        self::check_version($input, "1.9.0");

        $genre = $input['genre'];
        self::check_parameter($genre);
        $count = $input['count'];
        $offset = $input['offset'];
        
        $tag = Tag::construct_from_name($genre);
        if ($tag->id) {
            $songs = Tag::get_tag_objects("song", $tag->id, $count, $offset);
        }
        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addSongsByGenre($r, $songs);
        echo $r->asXml();
    }
    
    /**
     * getNowPlaying
     * Get what is currently being played by all users.
     * Takes no parameter.
     */
    public static function getnowplaying($input) {
        self::check_version($input);

        $data = Stream::get_now_playing();
        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addNowPlaying($r, $data);
        echo $r->asXml();
    }    
    
    /**
     * search2
     * Get albums, artists and songs matching the given criteria.
     * Takes query with optional artist count, artist offset, album count, album offset, song count and song offset in parameters.
     */
    public static function search2($input, $elementName="searchResult2") {
        self::check_version($input, "1.2.0");
        
        $query = $input['query'];
        self::check_parameter($query);
        
        $artistCount = $input['artistCount'];
        $artistOffset = $input['artistOffset'];
        $albumCount = $input['albumCount'];
        $albumOffset = $input['albumOffset'];
        $songCount = $input['songCount'];
        $songOffset = $input['songOffset'];
        
        $sartist = array();
        $sartist['limit'] = $artistCount;
        if ($artistOffset) $sartist['offset'] = $artistOffset;
        $sartist['rule_1_input'] = $query;
        $sartist['rule_1_operator'] = 0;
        $sartist['rule_1'] = "name";
        $sartist['type'] = "artist";
        $artists = Search::run($sartist);

        $salbum = array();
        $salbum['limit'] = $albumCount;
        if ($albumOffset) $salbum['offset'] = $albumOffset;
        $salbum['rule_1_input'] = $query;
        $salbum['rule_1_operator'] = 0;
        $salbum['rule_1'] = "title";
        $salbum['type'] = "album";
        $albums = Search::run($salbum);
        
        $ssong = array();
        $ssong['limit'] = $songCount;
        if ($songOffset) $ssong['offset'] = $songOffset;
        $ssong['rule_1_input'] = $query;
        $ssong['rule_1_operator'] = 0;
        $ssong['rule_1'] = "anywhere";
        $ssong['type'] = "song";
        $songs = Search::run($ssong);
        
        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addSearchResult($r, $artists, $albums, $songs, $elementName);
        echo $r->asXml();
    }
    
    /**
     * search3
     * See search2.
     */
    public static function search3($input) {
        self::check_version($input, "1.8.0");
        self::search2($input, "searchResult3");
    }
    
    /**
     * getPlaylists
     * Get all playlists a user is allowed to play.
     * Takes optional user in parameter.
     */
    public static function getplaylists($input) {
        self::check_version($input);
        
        $r = Subsonic_XML_Data::createSuccessResponse();
        $username = $input['username'];
        
        // Don't allow playlist listing for another user
        if (empty($username) || $username == $GLOBALS['user']->username) {
            Subsonic_XML_Data::addPlaylists($r, Playlist::get_playlists());
        } else {
            $user = User::get_from_username($username);
            if ($user->id) {
                Subsonic_XML_Data::addPlaylists($r, Playlist::get_users($user->id));
            } else {
                Subsonic_XML_Data::addPlaylists($r, array());
            }
        }
        echo $r->asXml();
    }
    
    /**
     * getPlaylist
     * Get the list of files in a saved playlist.
     * Takes the playlist id in parameters.
     */
    public static function getplaylist($input) {
        self::check_version($input);
        
        $playlistid = $input['id'];
        self::check_parameter($playlistid);
        
        $playlist = new Playlist($playlistid);
        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addPlaylist($r, $playlist, true);
        echo $r->asXml();
    }
    
    /**
     * createPlaylist
     * Create (or updates) a playlist.
     * Takes playlist id in parameter if updating, name in parameter if creating and a list of song id for the playlist.
     */
    public static function createplaylist($input) {
        self::check_version($input, "1.2.0");
        
        $playlistId = $input['playlistId'];
        $name = $input['name'];
        $songId = $input['songId'];
        
        if ($playlistId) {
            self::_updatePlaylist($playlistId, $name, $songId);
            $r = Subsonic_XML_Data::createSuccessResponse();
        } else if (!empty($name)) {
            $playlistId = Playlist::create($name, 'public');
            if (count($songId) > 0) {
                self::_updatePlaylist($playlistId, "", $songId);
            }
            $r = Subsonic_XML_Data::createSuccessResponse();
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM);
        }
        echo $r->asXml();
    }
    
    private static function _updatePlaylist($id, $name, $songsIdToAdd = array(), $songIndexToRemove = array(), $public = true) {
        $playlist = new Playlist($id);
        
        $newdata = array();
        $newdata['name'] = (!empty($name)) ? $name : $playlist->name;
        $newdata['pl_type'] = ($public) ? "public" : "private";
        $playlist->update($newdata);
        
        if (is_array($songsIdToAdd) && count($songsIdToAdd) > 0) {
            $playlist->add_songs(Subsonic_XML_Data::getAmpacheIds($songsIdToAdd));
        }
        
        if (is_array($songIndexToRemove) && count($songIndexToRemove) > 0) {
            $tracks = Subsonic_XML_Data::getAmpacheIds($songIndexToRemove);
            foreach ($tracks as $track) {
                $playlist->delete_track_number($track);
            }
        }
    }
     
    /**
     * updatePlaylist
     * Update a playlist.
     * Takes playlist id in parameter with optional name, comment, public level and a list of song id to add/remove.
     */
    public static function updateplaylist($input) {
        self::check_version($input, "1.8.0");

        $playlistId = $input['playlistId'];
        self::check_parameter($playlistId);
        
        $name = $input['name'];
        $comment = $input['comment'];   // Not supported.
        $public = boolean($input['public']);
        echo $public;
        $songIdToAdd = $input['songIdToAdd'];
        $songIndexToRemove = $input['songIndexToRemove'];
        
        $r = Subsonic_XML_Data::createSuccessResponse();
        echo $r->asXml();
    }
     
    /**
     * deletePlaylist
     * Delete a saved playlist.
     * Takes playlist id in parameter.
     */
    public static function deleteplaylist($input) {
        self::check_version($input, "1.2.0");

        $playlistId = $input['playlistId'];
        self::check_parameter($playlistId);
        
        $playlist = new Playlist($playlistId);
        $playlist->delete();
        
        $r = Subsonic_XML_Data::createSuccessResponse();
        echo $r->asXml();
    }
    
    /**
     * stream
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
     */
    public static function stream($input) {
        self::check_version($input, "1.0.0", true);

        $fileid = $input['id'];
        self::check_parameter($fileid, true);

        $maxBitRate = $input['maxBitRate']; // Not supported.
        $format = $input['format']; // mp3, flv or raw. Not supported.
        $timeOffset = $input['timeOffset']; // For video streaming. Not supported.
        $size = $input['size']; // For video streaming. Not supported.
        $maxBitRate = $input['maxBitRate']; // For video streaming. Not supported.
        $estimateContentLength = $input['estimateContentLength']; // Not supported.
        
        $url = Song::play_url(Subsonic_XML_Data::getAmpacheId($fileid));      
        self::follow_stream($url);
    }
     
    /**
     * download
     * Downloads a given media file.
     * Takes the file id in parameter.
     */
    public static function download($input) {
        self::check_version($input, "1.0.0", true);
        
        $fileid = $input['id'];
        self::check_parameter($fileid, true);
        
        $url = Song::play_url(Subsonic_XML_Data::getAmpacheId($fileid)) . '&action=download';
        self::follow_stream($url);
    }
    
    /**
     * hls
     * Create an HLS playlist.
     * Takes the file id in parameter with optional max bit rate.
     */
    public static function hls($input) {
        self::check_version($input, "1.8.0", true);

        $fileid = $input['id'];
        self::check_parameter($fileid, true);
        
        $bitRate = $input['bitRate']; // Not supported.
        
        $media = array();
        $media['object_type'] = 'song';
        $media['object_id'] = Subsonic_XML_Data::getAmpacheId($fileid);
        
        $medias = array();
        $medias[] = $media;
        $stream = new Stream_Playlist();
        $stream->add($medias);
        
        header('Content-Type: application/vnd.apple.mpegurl;');
        $stream->create_m3u();
    }
     
    /**
     * getCoverArt
     * Get a cover art image.
     * Takes the cover art id in parameter.
     */
    public static function getcoverart($input) {
        self::check_version($input, "1.0.0", true);
        
        $id = $input['id'];
        self::check_parameter($id, true);
        $size = $input['size'];
        
        $art = null;
        if (Subsonic_XML_Data::isArtist($id)) {
            $art = new Art(Subsonic_XML_Data::getAmpacheId($id), "artist");
        } else if (Subsonic_XML_Data::isAlbum($id)) {
            $art = new Art(Subsonic_XML_Data::getAmpacheId($id), "album");
        } else if (Subsonic_XML_Data::isSong($id)) {
            $art = new Art(Subsonic_XML_Data::getAmpacheId($id), "song");
        }
        
        if ($art != null) {
            $art->get_db();
            if (!$size) {
                echo $art->raw;
            } else {
                $dim = array();
                $dim['width'] = $size;
                $dim['height'] = $size;
                $thumb = $art->get_thumb($dim);
                echo $thumb['thumb'];
            }
        }
    }
     
    /**
     * setRating
     * Sets the rating for a music file.
     * Takes the file id and rating in parameters.
     */
    public static function setrating($input) {
        self::check_version($input, "1.6.0");
        
        $id = $input['id'];
        self::check_parameter($id);
        $rating = $input['rating'];

        $robj = null;
        if (Subsonic_XML_Data::isArtist($id)) {
            $robj = new Rating(Subsonic_XML_Data::getAmpacheId($id), "artist");
        } else if (Subsonic_XML_Data::isAlbum($id)) {
            $robj = new Rating(Subsonic_XML_Data::getAmpacheId($id), "album");
        } else if (Subsonic_XML_Data::isSong($id)) {
            $robj = new Rating(Subsonic_XML_Data::getAmpacheId($id), "song");
        }

        if ($robj != null) {
            $robj->set_rating($rating);

            $r = Subsonic_XML_Data::createSuccessResponse();
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, "Media not found."); 
        }

        echo $r->asXml();
    }
    
    

    /****   CURRENT UNSUPPORTED FUNCTIONS   ****/
     
    /**
     * getLyrics
     * Searches and returns lyrics for a given song.
     * Takes the optional artist and title in parameters.
     */
    public static function getlyrics($input) {
        self::check_version($input, "1.2.0");

        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * getStarred
     * Get starred songs, albums and artists.
     * Takes no parameter.
     * Not supported.
     */
    public static function getstarred($input, $elementName="starred") {
        self::check_version($input, "1.8.0");
        
        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addStarred($r, $elementName);
        echo $r->asXml();
    }
     
     
    /**
     * getStarred2
     * See getStarred.
     */
    public static function getStarred2($input) {
        self::getStarred($input, "starred2");
    }
    
    /**
     * star
     * Attaches a star to a song, album or artist.
     * Takes the optional file id, album id or artist id in parameters.
     * Not supported.
     */
    public static function star($input) {
        self::check_version($input, "1.8.0");

        // Ignore error
        $r = Subsonic_XML_Data::createSuccessResponse();
        echo $r->asXml();
    }
     
    /**
     * unstar
     * Removes the star from a song, album or artist.
     * Takes the optional file id, album id or artist id in parameters.
     * Not supported.
     */
    public static function unstar($input) {
        self::check_version($input, "1.8.0");
        
        // Ignore error
        $r = Subsonic_XML_Data::createSuccessResponse();
        echo $r->asXml();
    }
    
    /**
     * scrobble
     * Scrobbles a given music file on last.fm.
     * Takes the file id with optional time and submission parameters.
     * Not supported.
     */
    public static function scrobble($input) {
        self::check_version($input, "1.5.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * getShares
     * Get information about shared media this user is allowed to manage.
     * Takes no parameter.
     * Not supported.
     */
    public static function getshares($input) {
        self::check_version($input, "1.6.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * createShare
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     * Not supported.
     */
    public static function createshare($input) {
        self::check_version($input, "1.6.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * updateShare
     * Update the description and/or expiration date for an existing share.
     * Takes the share id to update with optional description and expires parameters.
     * Not supported.
     */
    public static function updateshare($input) {
        self::check_version($input, "1.6.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * deleteShare
     * Delete an existing share.
     * Takes the share id to delete in parameters.
     * Not supported.
     */
    public static function deleteshare($input) {
        self::check_version($input, "1.6.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * getPodcasts
     * Get all podcast channels.
     * Takes the optional includeEpisodes and channel id in parameters
     * Not supported.
     */
    public static function getpodcasts($input) {
        self::check_version($input, "1.6.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * refreshPodcasts
     * Request the server to check for new podcast episodes.
     * Takes no parameters.
     * Not supported.
     */
    public static function refreshpodcasts($input) {
        self::check_version($input, "1.9.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * createPodcastChannel
     * Add a new podcast channel.
     * Takes the podcast url in parameter.
     * Not supported.
     */
    public static function createpodcastchannel($input) {
        self::check_version($input, "1.9.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * deletePodcastChannel
     * Delete an existing podcast channel
     * Takes the podcast id in parameter.
     * Not supported.
     */
    public static function deletepodcastchannel($input) {
        self::check_version($input, "1.9.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * deletePodcastEpisode
     * Delete a podcast episode
     * Takes the podcast episode id in parameter.
     * Not supported.
     */
    public static function deletepodcastepisode($input) {
        self::check_version($input, "1.9.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * downloadPodcastEpisode
     * Request the server to download a podcast episode
     * Takes the podcast episode id in parameter.
     * Not supported.
     */
    public static function downloadpodcastepisode($input) {
        self::check_version($input, "1.9.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * jukeboxControl
     * Control the jukebox.
     * Takes the action with optional index, offset, song id and volume gain in parameters.
     * Not supported.
     */
    public static function jukeboxcontrol($input) {
        self::check_version($input, "1.2.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * getInternetRadioStations
     * Get all internet radio stations
     * Takes no parameter.
     * Not supported.
     */
    public static function getinternetradiostations($input) {
        self::check_version($input, "1.9.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * getChatMessages
     * Get the current chat messages.
     * Takes no parameter.
     * Not supported.
     */
    public static function getchatmessages($input) {
        self::check_version($input, "1.2.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * addChatMessages
     * Add a message to the chat.
     * Takes the message in parameter.
     * Not supported.
     */
    public static function addchatmessages($input) {
        self::check_version($input, "1.2.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * getUser
     * Get details about a given user.
     * Takes the username in parameter.
     * Not supported.
     */
    public static function getuser($input) {
        self::check_version($input, "1.3.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * getUsers
     * Get details about a given user.
     * Takes no parameter.
     * Not supported.
     */
    public static function getusers($input) {
        self::check_version($input, "1.8.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * createUser
     * Create a new user.
     * Takes the username, password and email with optional roles in parameters.
     * Not supported.
     */
    public static function createuser($input) {
        self::check_version($input, "1.1.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * deleteUser
     * Delete an existing user.
     * Takes the username in parameter.
     * Not supported.
     */
    public static function deleteuser($input) {
        self::check_version($input, "1.3.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * changePassword
     * Change the password of an existing user.
     * Takes the username with new password in parameters.
     * Not supported.
     */
    public static function changepassword($input) {
        self::check_version($input, "1.1.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * getBookmarks
     * Get all user bookmarks.
     * Takes no parameter.
     * Not supported.
     */
    public static function getbookmarks($input) {
        self::check_version($input, "1.9.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * createBookmark
     * Creates or updates a bookmark.
     * Takes the file id and position with optional comment in parameters.
     * Not supported.
     */
    public static function createbookmark($input) {
        self::check_version($input, "1.9.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
    
    /**
     * deleteBookmark
     * Delete an existing bookmark.
     * Takes the file id in parameter.
     * Not supported.
     */
    public static function deletebookmark($input) {
        self::check_version($input, "1.9.0");
        
        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        echo $r->asXml();
    }
}
?>
