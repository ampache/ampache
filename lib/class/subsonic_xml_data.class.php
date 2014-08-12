<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
 * XML_Data Class
 *
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls
 *
 */
class Subsonic_XML_Data
{
    const API_VERSION = "1.10.1";

    const SSERROR_GENERIC = 0;
    const SSERROR_MISSINGPARAM = 10;
    const SSERROR_APIVERSION_CLIENT = 20;
    const SSERROR_APIVERSION_SERVER = 30;
    const SSERROR_BADAUTH = 40;
    const SSERROR_UNAUTHORIZED = 50;
    const SSERROR_TRIAL = 60;
    const SSERROR_DATA_NOTFOUND = 70;

    // Ampache doesn't have a global unique id but each items are unique per category. We use id pattern to identify item category.
    const AMPACHEID_ARTIST = 100000000;
    const AMPACHEID_ALBUM = 200000000;
    const AMPACHEID_SONG = 300000000;
    const AMPACHEID_SMARTPL = 400000000;
    const AMPACHEID_VIDEO = 500000000;

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

    public static function getAmpacheId($id)
    {
        return ($id % Subsonic_XML_Data::AMPACHEID_ARTIST);
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
        return ($id >= Subsonic_XML_Data::AMPACHEID_ARTIST && $id < Subsonic_XML_Data::AMPACHEID_ALBUM);
    }

    public static function isAlbum($id)
    {
        return ($id >= Subsonic_XML_Data::AMPACHEID_ALBUM && $id < Subsonic_XML_Data::AMPACHEID_SONG);
    }

    public static function isSong($id)
    {
        return ($id >= Subsonic_XML_Data::AMPACHEID_SONG);
    }

    public static function isSmartPlaylist($id)
    {
        return ($id >= Subsonic_XML_Data::AMPACHEID_SMARTPL);
    }

    public static function isVideo($id)
    {
        return ($id >= Subsonic_XML_Data::AMPACHEID_VIDEO);
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
        if (empty($version)) $version = Subsonic_XML_Data::API_VERSION;
        $response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>');
        $response->addAttribute('xmlns', 'http://subsonic.org/restapi');
        $response->addAttribute('version', $version);
        return $response;
    }

    public static function createError($code, $message = "", $version = "")
    {
        if (empty($version)) $version = Subsonic_XML_Data::API_VERSION;
        $response = self::createFailedResponse($version);
        self::setError($response, $code, $message);
        return $response;
    }

    /**
     * Set error information.
     *
     * @param    SimpleXMLElement   $xml    Parent node
     * @param    integer    $code    Error code
     * @param    string    $string    Error message
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

    public static function addArtistsRoot($xml, $artists)
    {
        $xartists = $xml->addChild('artists');
        self::addArtists($xartists, $artists, true);
    }

    public static function addArtists($xml, $artists, $extra=false)
    {
        $xlastcat = null;
        $xsharpcat = null;
        $xlastletter = '';
        foreach ($artists as $artist) {
            if (strlen($artist->name) > 0) {
                $letter = strtoupper($artist->name[0]);
                if ($letter == "X" || $letter == "Y" || $letter == "Z") $letter = "X-Z";
                else if (!preg_match("/^[A-W]$/", $letter)) $letter = "#";

                if ($letter != $xlastletter) {
                    $xlastletter = $letter;
                    if ($letter == '#' && $xsharpcat != null) {
                        $xlastcat = $xsharpcat;
                    } else {
                        $xlastcat = $xml->addChild('index');
                        $xlastcat->addAttribute('name', $xlastletter);

                        if ($letter == '#') {
                            $xsharpcat = $xlastcat;
                        }
                    }
                }
            }

            if ($xlastcat != null) {
                self::addArtist($xlastcat, $artist, $extra);
            }
        }
    }

    public static function addArtist($xml, $artist, $extra=false, $albums=false)
    {
        $xartist = $xml->addChild('artist');
        $xartist->addAttribute('id', self::getArtistId($artist->id));
        $xartist->addAttribute('name', $artist->name);

        $allalbums = array();
        if ($extra || $albums) {
            $allalbums = $artist->get_albums(null, true);
        }

        if ($extra) {
            //$xartist->addAttribute('coverArt');
            $xartist->addAttribute('albumCount', count($allalbums));
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
          $xlist = $xml->addChild($elementName);
          foreach ($albums as $id) {
            $album = new Album($id);
            self::addAlbum($xlist, $album);
          }
    }

    public static function addAlbum($xml, $album, $songs=false, $elementName="album")
    {
        $xalbum = $xml->addChild($elementName);
        $xalbum->addAttribute('id', self::getAlbumId($album->id));
        $xalbum->addAttribute('album', $album->name);
        $xalbum->addAttribute('title', self::formatAlbum($album));
        $xalbum->addAttribute('name', $album->name);
        $xalbum->addAttribute('isDir', 'true');
        $album->format();
        if ($album->has_art) {
            $xalbum->addAttribute('coverArt', self::getAlbumId($album->id));
        }
        $xalbum->addAttribute('songCount', $album->song_count);
        $xalbum->addAttribute('duration', $album->total_duration);
        $xalbum->addAttribute('artistId', self::getArtistId($album->artist_id));
        $xalbum->addAttribute('parent', self::getArtistId($album->artist_id));
        $xalbum->addAttribute('artist', $album->artist_name);

        $rating = new Rating($album->id, "album");
        $rating_value = $rating->get_average_rating();
        $xalbum->addAttribute('averageRating', ($rating_value) ? $rating_value : 0);

        if ($songs) {
            $allsongs = $album->get_songs();
            foreach ($allsongs as $id) {
                $song = new Song($id);
                self::addSong($xalbum, $song);
            }
        }
    }

     public static function addSong($xml, $song, $elementName='song')
     {
        self::createSong($xml, $song, $elementName);
     }

    public static function createSong($xml, $song, $elementName='song')
    {
        $xsong = $xml->addChild($elementName);
        $xsong->addAttribute('id', self::getSongId($song->id));
        $xsong->addAttribute('parent', self::getAlbumId($song->album));
        //$xsong->addAttribute('created', );
        $xsong->addAttribute('title', $song->title);
        $xsong->addAttribute('isDir', 'false');
        $xsong->addAttribute('isVideo', 'false');
        $xsong->addAttribute('type', 'music');
        $album = new Album($song->album);
        $xsong->addAttribute('albumId', self::getAlbumId($album->id));
        $xsong->addAttribute('album', $album->name);
        $artist = new Artist($song->artist);
        $xsong->addAttribute('artistId', self::getArtistId($album->id));
        $xsong->addAttribute('artist', $artist->name);
        $xsong->addAttribute('coverArt', self::getAlbumId($album->id));
        $xsong->addAttribute('duration', $song->time);
        $xsong->addAttribute('bitRate', intval($song->bitrate / 1000));
        if ($song->track > 0) {
            $xsong->addAttribute('track', $song->track);
        }
        if ($song->year > 0) {
            $xsong->addAttribute('year', $song->year);
        }
        $tags = Tag::get_object_tags('song', $song->id);
        if (count($tags) > 0) $xsong->addAttribute('genre', $tags[0]['name']);
        $xsong->addAttribute('size', $song->size);
        if ($album->disk > 0) $xsong->addAttribute('discNumber', $album->disk);
        $xsong->addAttribute('suffix', $song->type);
        $xsong->addAttribute('contentType', $song->mime);
        // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
        $path = $artist->name . '/' . $album->name . '/' . basename($song->file);
        $xsong->addAttribute('path', $path);

        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode');
        $transcode_mode = AmpConfig::get('transcode_' . $song->type);
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && $transcode_mode == 'required')) {
            $transcode_settings = $song->get_transcode_settings(null);
            if ($transcode_settings) {
                $transcode_type = $transcode_settings['format'];
                $xsong->addAttribute('transcodedSuffix', $transcode_type);
                $xsong->addAttribute('transcodedContentType', Song::type_to_mime($transcode_type));
            }
        }

        return $xsong;
    }

    private static function formatAlbum($album)
    {
        $name = $album->name;
        if ($album->year > 0) {
            $name .= " [" . $album->year . "]";
        }

        if ($album->disk) {
            $name .= " [" . T_('Disk') . " " . $album->disk . "]";
        }

        return $name;
    }

    public static function addArtistDirectory($xml, $artist)
    {
        $xdir = $xml->addChild('directory');
        $xdir->addAttribute('id', self::getArtistId($artist->id));
        $xdir->addAttribute('name', $artist->name);

        $allalbums = $artist->get_albums(null, true);
        foreach ($allalbums as $id) {
            $album = new Album($id);
            self::addAlbum($xdir, $album, false, "child");
        }
    }

    public static function addAlbumDirectory($xml, $album)
    {
        $xdir = $xml->addChild('directory');
        $xdir->addAttribute('id', self::getAlbumId($album->id));
        $xdir->addAttribute('name', self::formatAlbum($album));
        $album->format();
        //$xdir->addAttribute('parent', self::getArtistId($album->artist_id));

        $allsongs = $album->get_songs();
        foreach ($allsongs as $id) {
            $song = new Song($id);
            self::addSong($xdir, $song, "child");
        }
    }

    public static function addGenres($xml, $tags)
    {
        $xgenres = $xml->addChild('genres');

        foreach ($tags as $tag) {
            $otag = new Tag($tag['id']);
            $xgenres->addChild('genre', $otag->name);
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

    public static function addVideo($xml, $video)
    {
        $xvideo = $xml->addChild('video');
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
        if (count($tags) > 0) $xvideo->addAttribute('genre', $tags[0]['name']);
        $xvideo->addAttribute('size', $video->size);
        $xvideo->addAttribute('suffix', $video->type);
        $xvideo->addAttribute('contentType', $video->mime);
        // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
        $path = basename($video->file);
        $xvideo->addAttribute('path', $path);

        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode');
        $transcode_mode = AmpConfig::get('transcode_' . $video->type);
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && $transcode_mode == 'required')) {
            $transcode_settings = $video->get_transcode_settings(null);
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
        $xplaylist->addAttribute('songCount', $playlist->get_song_count());
        $xplaylist->addAttribute('duration', $playlist->get_total_duration());

        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $id) {
                $song = new Song($id);
                self::addSong($xplaylist, $song, "entry");
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
                self::addSong($xplaylist, $song, "entry");
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
            $track = self::createSong($xplaynow, $d['media'], "entry");
            $track->addAttribute('username', $d['client']->username);
            $track->addAttribute('minutesAgo', intval(time() - ($d['expire'] - AmpConfig::get('stream_length')) / 1000));
            $track->addAttribute('playerId', $d['agent']);
        }
    }

    public static function addSearchResult($xml, $artists, $albums, $songs, $elementName = "searchResult2")
    {
        $xresult = $xml->addChild($elementName);
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

    public static function addStarred($xml, $artists, $albums, $songs, $elementName="starred")
    {
        $xstarred = $xml->addChild($elementName);

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
        $xuser->addAttribute('scrobblingEnabled', 'false');
        $isManager = ($user->access >= 75);
        $isAdmin = ($user->access >= 100);
        $xuser->addAttribute('adminRole', $isAdmin ? 'true' : 'false');
        $xuser->addAttribute('settingsRole', $isAdmin ? 'true' : 'false');
        $xuser->addAttribute('downloadRole', Preference::get_by_user($user->id, 'download') ? 'true' : 'false');
        $xuser->addAttribute('playlistRole', 'true');
        $xuser->addAttribute('coverArtRole', $isManager ? 'true' : 'false');
        $xuser->addAttribute('commentRole', 'false');
        $xuser->addAttribute('podcastRole', 'false');
        $xuser->addAttribute('streamRole', 'true');
        $xuser->addAttribute('jukeboxRole', 'false');
        $xuser->addAttribute('shareRole', 'false');
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
        $xshare = $xml->addChild('share ');
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
            self::addSong($xshare, $song, "entry");
        } elseif ($share->object_type == 'playlist') {
            $playlist = new Playlist($share->object_id);
            $songs = $playlist->get_songs();
            foreach ($songs as $id) {
                $song = new Song($id);
                self::addSong($xshare, $song, "entry");
            }
        } elseif ($share->object_type == 'album') {
            $album = new Album($share->object_id);
            $songs = $album->get_songs();
            foreach ($songs as $id) {
                $song = new Song($id);
                self::addSong($xshare, $song, "entry");
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
}
