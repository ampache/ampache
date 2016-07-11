<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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
 * JSON_Data Class
 *
 * This class takes care of all of the JSON document stuff in Ampache these
 * are all static calls
 *
 */
class JSON_Data
{
    // This is added so that we don't pop any webservers
    private static $limit  = 5000;
    private static $offset = 0;
    private static $type   = '';

    /**
     * constructor
     *
     * We don't use this, as its really a static class
     */
    private function __construct()
    {
        // Rien a faire
    } // constructor

    /**
     * set_offset
     *
     * This takes an int and changes the offset
     *
     * @param    integer    $offset    (description here...)
     * @return    void
     */
    public static function set_offset($offset)
    {
        $offset       = intval($offset);
        self::$offset = $offset;
    } // set_offset

    /**
     * set_limit
     *
     * This sets the limit for any ampache transactions
     *
     * @param    integer    $limit    (description here...)
     * @return    void
     */
    public static function set_limit($limit)
    {
        if (!$limit) {
            return false;
        }

        $limit       = intval($limit);
        self::$limit = $limit;
    } // set_limit

    /**
     * error
     *
     * This generates a JSON Error message
     * nothing fancy here...
     *
     * @param    integer    $code    Error code
     * @param    string    $string    Error message
     * @return    string    return error message JSON
     */
    public static function error($code,$string)
    {
        $JSON = json_encode(array("error" => array("code" => $code, "message" => $string)), JSON_PRETTY_PRINT);
        return $JSON;
    } // error

    /**
     * single_string
     *
     * This takes two values, first the key second the string
     *
     * @param    string    $key    (description here...)
     * @param    string    $string    JSON data
     * @return    string    return JSON
     */
    public static function single_string($key, $string='')
    {
        if (!empty($string)) {
            $JSON = json_encode(array($key => $string), JSON_PRETTY_PRINT);
        } else {
            $JSON = json_encode(array("message" => $key), JSON_PRETTY_PRINT);
        }

        return $JSON;
    } // single_string

    /**
     * tags_string
     *
     * This returns the formatted 'tags' string for an JSON document
     *
     */
    private static function tags_string($tags)
    {
        $JSON = null;

        if (is_array($tags)) {
            $atags = array();
            foreach ($tags as $tag_id => $data) {
                if (array_key_exists($data['id'], $atags)) {
                    $atags[$data['id']]['count']++;
                } else {
                    $atags[$data['id']] = array('name' => $data['name'],
                        'count' => 1);
                }
            }

            foreach ($atags as $id => $data) {
                $JSON['id'] = $id;
                $JSON['count'] = $data['count'];
                $JSON['name'] = $data['name'];
            }
        }

        return $JSON;
    } // tags_string


    /**
     * playlist_song_tracks_string
     *
     * This returns the formatted 'playlistTrack' string for an JSON document
     *
     */
    private static function playlist_song_tracks_string($song, $playlist_data)
    {
        if (empty($playlist_data)) {
            return "";
        }
        $playlist_track = "";

        foreach ($playlist_data as $playlist) {
            if ($playlist["object_id"] == $song->id) {
                return $playlist["track"];
            }
        }
        return "";
    } // playlist_song_tracks_string

    /**
     * tags
     *
     * This returns tags to the user, in a pretty JSON document with the information
     *
     * @param    array    $tags    (description here...)
     * @return    string    return json
     */
    public static function tags($tags)
    {
        if (count($tags) > self::$limit or self::$offset > 0) {
            $tags = array_splice($tags,self::$offset,self::$limit);
        }

        $JSON = [];

        foreach ($tags as $tag_id) {
            $tag    = new Tag($tag_id);
            $counts = $tag->count();
            array_push($JSON, array("tag" => array(
                id => $tag_id,
                name => $tag->name,
                albums => intval($counts['album']),
                artists => intval($counts['artist']),
                songs => intval($counts['song']),
                videos => intval($counts['video']),
                playlists => intval($counts['playlist']),
                stream => intval($counts['live_stream'])
            )));
        } // end foreach

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // tags

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty JSON document with the information
     * we want
     *
     * @param    array    $artists    (description here...)
     * @return    string    return JSON
     */
    public static function artists($artists)
    {
        if (count($artists) > self::$limit or self::$offset > 0) {
            $artists = array_splice($artists,self::$offset,self::$limit);
        }

        $JSON = [];

        Rating::build_cache('artist',$artists);

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            $artist->format();

            $rating     = new Rating($artist_id,'artist');
            $tag_string = self::tags_string($artist->tags);

            array_push($JSON, array(
                "artist" => array(
                    id => $artist->id,
                    name => $artist->f_full_name,
                    tags => $tag_string,
                    albums => ($artist->albums ?: 0),
                    songs => ($artist->songs ?: 0),
                    preciserating => ($rating->get_user_rating() ?: 0),
                    rating => ($rating->get_user_rating() ?: 0),
                    averagerating => ($rating->get_average_rating() ?: 0),
                    mbid => $artist->mbid,
                    summary => $artist->summary,
                    yearformed => $artist->yearformed,
                    placeformed => $artist->placeformed
            )));


        } // end foreach artists

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // artists

    /**
     * albums
     *
     * This echos out a standard albums JSON document, it pays attention to the limit
     *
     * @param    array    $albums    (description here...)
     * @return    string    return JSON
     */
    public static function albums($albums)
    {
        if (count($albums) > self::$limit or self::$offset > 0) {
            $albums = array_splice($albums,self::$offset,self::$limit);
        }

        Rating::build_cache('album',$albums);

        $JSON = [];
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $album->format();

            $rating = new Rating($album_id,'album');

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $album->id . '&object_type=album&auth=' . scrub_out($_REQUEST['auth']);

            $theArray = [];

            $theArray["album"] = array(
                id => $album->id,
                name => $album->name
            );

            // Do a little check for artist stuff
            if ($album->artist_count != 1) {
                $theArray['artist'] = array(
                    id => 0,
                    name => 'Various'
                );
            } else {
                $theArray['album']['artist'] = array(
                    id => $album->artist_id,
                    name => $album->artist_name
                );

            }

            $theArray['album']['year'] = $album->year;
            $theArray['album']['tracks'] = $album->song_count;
            $theArray['album']['disk'] = $album->disk;
            $theArray['album']['tags'] = self::tags_string($album->tags);
            $theArray['album']['art'] = $art_url;
            $theArray['album']['preciserating'] = $rating->get_user_rating();
            $theArray['album']['rating'] = $rating->get_user_rating();
            $theArray['album']['averagerating'] = $rating->get_average_rating();
            $theArray['album']['mbid'] = $album->mbid;


            array_push($JSON, $theArray);
        } // end foreach

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // albums

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty JSON document
     *
     * @param    array    $playlists    (description here...)
     * @return    string    return JSON
     */
    public static function playlists($playlists)
    {
        if (count($playlists) > self::$limit or self::$offset > 0) {
            $playlists = array_slice($playlists,self::$offset,self::$limit);
        }

        $allPlaylists = [];

        // Foreach the playlist ids
        foreach ($playlists as $playlist_id) {
            $playlist = new Playlist($playlist_id);
            $playlist->format();
            $item_total = $playlist->get_media_count('song');

            array_push($allPlaylists, array(
                "playlist" => array(
                    "id" => $playlist->id,
                    "name" => $playlist->name,
                    "owner" => $playlist->f_user,
                    "items" => $item_total,
                    "type" => $playlist->type)));
        } // end foreach

        return json_encode($allPlaylists, JSON_PRETTY_PRINT);
    } // playlists

    /**
     * songs
     *
     * This returns a JSON document from an array of song ids.
     * (Spiffy isn't it!)
     */
    public static function songs($songs, $playlist_data='')
    {
        if (count($songs) > self::$limit or self::$offset > 0) {
            $songs = array_slice($songs, self::$offset, self::$limit);
        }

        Song::build_cache($songs);
        Stream::set_session($_REQUEST['auth']);

        $JSON = [];

        // Foreach the ids!
        foreach ($songs as $song_id) {
            $song = new Song($song_id);

            // If the song id is invalid/null
            if (!$song->id) {
                continue;
            }

            $song->format();
            $playlist_track_string = self::playlist_song_tracks_string($song, $playlist_data);
            $rating                = new Rating($song_id, 'song');
            $art_url               = Art::url($song->album, 'album', $_REQUEST['auth']);

            $ourSong = array(
                id => $song->id,
                title => $song->title,
                artist => array(
                    id => $song->artist,
                    name => $song->get_artist_name()),
                album => array(
                    id => $song->album,
                    name => $song->get_album_name()),
            );
                if ($song->albumartist) {
                    $ourSong['albumartist'] = array(
                        id => $song->albumartist,
                        name => $song->get_album_artist_name()
                    );
                }

                    $ourSong['filename'] = $song->file;
                    $ourSong['track'] = $song->track;
                    $ourSong['playlisttrack'] = $playlist_track_string;
                    $ourSong['time'] = $song->time;
                    $ourSong['year'] = $song->year;
                    $ourSong['bitrate'] = $song->bitrate;
                    $ourSong['rate'] = $song->rate;
                    $ourSong['mode'] = $song->mode;
                    $ourSong['mime'] = $song->mime;
                    $ourSong['url'] = Song::play_url($song->id, '', 'api');
                    $ourSong['size'] = $song->size;
                    $ourSong['mbid'] = $song->mbid;
                    $ourSong['album_mbid'] = $song->album_mbid;
                    $ourSong['artist_mbid'] = $song->artist_mbid;
                    $ourSong['albumartist_mbid'] = $song->albumartist_mbid;
                    $ourSong['art'] = $song->art_url;
                    $ourSong['preciserating'] = ($rating->get_user_rating() ?: 0);
                    $ourSong['rating'] = ($rating->get_user_rating() ?: 0);
                    $ourSong['averagerating'] = ($rating->get_average_rating() ?: 0);
                    $ourSong['composer'] = $song->composer;
                    $ourSong['channels'] = $song->channels;
                    $ourSong['comment'] = $song->comment;
                    $ourSong['publisher'] = $song->label;
                    $ourSong['language'] = $song->language;
                    $ourSong['replaygain_album_gain'] = $song->replaygain_album_gain;
                    $ourSong['replaygain_album_peak'] = $song->replaygain_album_peak;
                    $ourSong['replaygain_track_gain'] = $song->replaygain_track_gain;
                    $ourSong['replaygain_track_peak'] = $song->replaygain_track_peak;

                    $tags = [];
                    foreach ($song->tags as $tag) {
                        array_push($tags, $tag['name']);
                    }
                    $ourSong['tags'] = $tags;

            array_push($JSON, array("song" => $ourSong));


        } // end foreach

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // songs

    /**
     * videos
     *
     * This builds the JSON document for displaying video objects
     *
     * @param    array    $videos    (description here...)
     * @return    string    return JSON
     */
    public static function videos($videos)
    {
        if (count($videos) > self::$limit or self::$offset > 0) {
            $videos = array_slice($videos,self::$offset,self::$limit);
        }

        $string = '';
        $JSON = [];
        foreach ($videos as $video_id) {
            $video = new Video($video_id);
            $video->format();
            $JSON['video'] = array(
                id => $video->id,
                title => $video->title,
                mime => $video->mime,
                resolution => $video->f_resolution,
                size => $video->size,
                tags => self::tags_string($video->tags),
                url => Video::play_url($video->id, '', 'api')


            );

        } // end foreach
        
        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // videos

    /**
     * democratic
     *
     * This handles creating an JSON document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param    array    $object_ids    Object IDs
     * @return    string    return JSON
     */
    public static function democratic($object_ids=array())
    {
        if (!is_array($object_ids)) {
            $object_ids = array();
        }

        $democratic = Democratic::get_current_playlist();

        $JSON = [];

        foreach ($object_ids as $row_id=>$data) {
            $song = new $data['object_type']($data['object_id']);
            $song->format();

            //FIXME: This is duplicate code and so wrong, functions need to be improved
            $tag           = new Tag($song->tags['0']);
            $song->genre   = $tag->id;
            $song->f_genre = $tag->name;

            $tag_string = self::tags_string($song->tags);

            $rating = new Rating($song->id,'song');

            $art_url = Art::url($song->album, 'album', $_REQUEST['auth']);

            array_push($JSON, array(
                id => $song->id,
                title => $song->title,
                artist => array(id => $song->artist, name => $song->f_artist_full),
                album => array(id => $song->album, name => $song->f_album_full),
                genre => array(id => $song->genre, name => $song->f_genre),
                track => $song->track,
                time => $song->time,
                mime => $song->mime,
                url => Song::play_url($song->id, '', 'api'),
                size => $song->size,
                art => $art_url,
                preciserating => $rating->get_user_rating(),
                rating => $rating->get_user_rating(),
                averagerating => $rating->get_average_rating(),
                vote => $democratic->get_vote($row_id)
            ));
        } // end foreach

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // democratic

    /**
     * user
     *
     * This handles creating an JSON document for an user
     *
     * @param    User    $user    User
     * @return    string    return JSON
     */
    public static function user(User $user)
    {
        $user->format();

        $JSON['user'] = array(
            id => $user->id,
            username => $user->username,
            create_date => $user->create_date,
            last_seen => $user->last_seen,
            website => $user->website,
            state => $user->state,
            city => $user->city
        );

        if ($user->fullname_public) {
            $JSON['user']['fullname'] = $user->fullname;
        }

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // user

    /**
     * users
     *
     * This handles creating an JSON document for an user list
     *
     * @param    int[]    $users    User identifier list
     * @return    string    return JSON
     */
    public static function users($users)
    {
        $JSON = [];
        foreach ($users as $user_id) {
            $user = new User($user_id);
            array_push($JSON, $user->username);
        }
        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // users

    /**
     * shouts
     *
     * This handles creating an JSON document for a shout list
     *
     * @param    int[]    $shouts    Shout identifier list
     * @return    string    return JSON
     */
    public static function shouts($shouts)
    {
        $JSON = [];
        foreach ($shouts as $shout_id) {
            $shout = new Shoutbox($shout_id);
            $shout->format();
            $user = new User($shout->user);
            $ourArray = array(
                id => $shout_id,
                date => $shout->date,
                text => $shout->text
            );
            if ($user->id) {
                $ourArray['username'] = $user->username;
            }
            array_push($JSON, $ourArray);
        }

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // shouts

    /**
     * timeline
     *
     * This handles creating an JSON document for an activity list
     *
     * @param    int[]    $activities    Activity identifier list
     * @return    string    return JSON
     */
    public static function timeline($activities)
    {
        $JSON['timeline'] = []; // To match the XML style, IMO kinda uselesss
        foreach ($activities as $aid) {
            $activity = new Useractivity($aid);
            $user = new User($activity->user);
            $ourArray = array(
                id => $aid,
                data => $activity->activity_date,
                object_type => $activity->object_type,
                object_id => $activity->object_id,
                action => $activity->action
            );

            if ($user->id) {
                $ourArray['username'] = $user->username;
            }
            array_push($JSON['timeline'], $ourArray);
        }

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // timeline

    public static function podcast(library_item $libitem)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><rss />');
        $xml->addAttribute("xmlns:xmlns:atom", "http://www.w3.org/2005/Atom");
        $xml->addAttribute("xmlns:xmlns:itunes", "http://www.itunes.com/dtds/podcast-1.0.dtd");
        $xml->addAttribute("version", "2.0");
        $xchannel = $xml->addChild("channel");
        $xchannel->addChild("title", $libitem->get_fullname() . " Podcast");
        $xlink = $xchannel->addChild("atom:link", htmlentities($libitem->link));
        if (Art::has_db($libitem->id, get_class($libitem))) {
            $ximg = $xchannel->addChild("xmlns:itunes:image");
            $ximg->addAttribute("href", Art::url($libitem->id, get_class($libitem)));
        }
        $summary = $libitem->get_description();
        if (!empty($summary)) {
            $summary = htmlentities($summary);
            $xchannel->addChild("description", $summary);
            $xchannel->addChild("xmlns:itunes:summary", $summary);
        }
        $xchannel->addChild("generator", "Ampache");
        $xchannel->addChild("xmlns:itunes:category", "Music");
        $owner = $libitem->get_user_owner();
        if ($owner) {
            $user_owner = new User($owner);
            $user_owner->format();
            $xowner = $xchannel->addChild("xmlns:itunes:owner");
            $xowner->addChild("xmlns:itunes:name", $user_owner->f_name);
        }

        $medias = $libitem->get_medias();
        foreach ($medias as $media_info) {
            $media = new $media_info['object_type']($media_info['object_id']);
            $media->format();
            $xitem = $xchannel->addChild("item");
            $xitem->addChild("title", htmlentities($media->get_fullname()));
            if ($media->f_artist) {
                $xitem->addChild("xmlns:itunes:author", $media->f_artist);
            }
            $xmlink = $xitem->addChild("link", htmlentities($media->link));
            $xitem->addChild("guid", htmlentities($media->link));
            if ($media->addition_time) {
                $xitem->addChild("pubDate", date("r", $media->addition_time));
            }
            $description = $media->get_description();
            if (!empty($description)) {
                $xitem->addChild("description", htmlentities($description));
            }
            $xitem->addChild("xmlns:itunes:duration", $media->f_time);
            if ($media->mime) {
                $surl  = $media_info['object_type']::play_url($media_info['object_id']);
                $xencl = $xitem->addChild("enclosure");
                $xencl->addAttribute("type", $media->mime);
                $xencl->addAttribute("length", $media->size);
                $xencl->addAttribute("url", $surl);
            }
        }

        $xmlstr = $xml->asXml();
        // Format xml output
        $dom = new DOMDocument();
        if ($dom->loadXML($xmlstr) !== false) {
            $dom->formatOutput = true;
            return $dom->saveXML();
        } else {
            return $xmlstr;
        }
    }
} // JSON_Data
