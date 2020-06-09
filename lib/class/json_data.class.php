<?php
declare(strict_types=0);
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
        self::$offset = (int) $offset;
    } // set_offset

    /**
     * set_limit
     *
     * This sets the limit for any ampache transactions
     *
     * @param    integer    $limit    (description here...)
     * @return    boolean
     */
    public static function set_limit($limit)
    {
        if (!$limit) {
            return false;
        }

        if (strtolower($limit) == "none") {
            self::$limit = null;
        } else {
            self::$limit = (int) ($limit);
        }

        return true;
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
    public static function error($code, $string)
    {
        return json_encode(array("error" => array("code" => $code, "message" => $string)), JSON_PRETTY_PRINT);
    } // error

    /**
     * success
     *
     * This generates a standard JSON Success message
     * nothing fancy here...
     *
     * @param    string    $string    success message
     * @return    string    return success message JSON
     */
    public static function success($string)
    {
        return json_encode(array("success" => $string), JSON_PRETTY_PRINT);
    } // success

    /**
     * tags_string
     *
     * This returns the formatted 'tags' string for an JSON document
     * @param $tags
     * @return string
     */
    private static function tags_string($tags)
    {
        $JSON = array();

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
                $JSON['id']    = $id;
                $JSON['count'] = $data['count'];
                $JSON['name']  = $data['name'];
            }
        }

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // tags_string

    /**
     * indexes
     *
     * This returns tags to the user, in a pretty JSON document with the information
     *
     * @param    array    $objects    (description here...)
     * @param    string    $type    (description here...)
     * @return string return JSON
     */
    public static function indexes($objects, $type)
    {
        //here is where we call the object type
        // 'artist'|'album'|'song'|'playlist'|'share'|'podcast'
        switch ($type) {
            case 'song':
                return self::songs($objects);
            case 'album':
                return self::albums($objects);
            case 'artist':
                return self::artists($objects);
            case 'playlist':
                return self::playlists($objects);
            case 'share':
                return self::shares($objects);
            case 'podcast':
                return self::podcasts($objects);
            default:
                return self::error('401', T_('Wrong object type ' . $type));
        }
    }

    /**
     * tags
     *
     * This returns tags to the user, in a pretty JSON document with the information
     *
     * @param    array    $tags    (description here...)
     * @return string return JSON
     */
    public static function tags($tags)
    {
        if (count($tags) > self::$limit or self::$offset > 0) {
            $tags = array_splice($tags, self::$offset, self::$limit);
        }

        $JSON = [];

        foreach ($tags as $tag_id) {
            $tag    = new Tag($tag_id);
            $counts = $tag->count();
            array_push($JSON, array(
                "id" => $tag_id,
                "name" => $tag->name,
                "albums" => (int) $counts['album'],
                "artists" => (int) $counts['artist'],
                "songs" => (int) $counts['song'],
                "videos" => (int) $counts['video'],
                "playlists" => (int) $counts['playlist'],
                "stream" => (int) $counts['live_stream']
            ));
        } // end foreach

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // tags

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty JSON document with the information
     * we want
     *
     * @param array $artists (description here...)
     * @param array $include
     * @param boolean $user_id
     * @param bool $encode
     * @return array|string return JSON
     */
    public static function artists($artists, $include = [], $user_id = false, $encode = true)
    {
        if (count($artists) > self::$limit or self::$offset > 0) {
            $artists = array_splice($artists, self::$offset, self::$limit);
        }

        $JSON = [];

        Rating::build_cache('artist', $artists);

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            $artist->format();


            $rating     = new Rating($artist_id, 'artist');
            $flag       = new Userflag($artist_id, 'artist');
            $tag_string = self::tags_string($artist->tags);

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $artist_id . '&object_type=artist&auth=' . scrub_out(Core::get_request('auth'));

            // Handle includes
            if (in_array("albums", $include)) {
                $albums = self::albums($artist->get_albums(), $include, false, false);
            } else {
                $albums = ($artist->albums ?: 0);
            }
            if (in_array("songs", $include)) {
                $songs = self::songs($artist->get_songs(), false, false);
            } else {
                $songs = ($artist->songs ?: 0);
            }

            array_push($JSON, array(
                "id" => $artist->id,
                "name" => $artist->f_full_name,
                "tags" => $tag_string,
                "albums" => ($albums ?: null),
                "songs" => ($songs ?: null),
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user_id, false) ? 0 : 1),
                "preciserating" => ($rating->get_user_rating() ?: 0),
                "rating" => ($rating->get_user_rating() ?: 0),
                "averagerating" => ($rating->get_average_rating() ?: 0),
                "mbid" => $artist->mbid,
                "summary" => $artist->summary,
                "yearformed" => $artist->yearformed,
                "placeformed" => $artist->placeformed
            ));
        } // end foreach artists

        if ($encode) {
            return json_encode($JSON, JSON_PRETTY_PRINT);
        }

        return $JSON;
    } // artists

    /**
     * albums
     *
     * This echos out a standard albums JSON document, it pays attention to the limit
     *
     * @param array $albums (description here...)
     * @param array $include
     * @param boolean $user_id
     * @param bool $encode
     * @return array|string
     */
    public static function albums($albums, $include = [], $user_id = false, $encode = true)
    {
        if (count($albums) > self::$limit or self::$offset > 0) {
            $albums = array_splice($albums, self::$offset, self::$limit);
        }

        Rating::build_cache('album', $albums);

        $JSON = [];
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $album->format();

            $disk   = $album->disk;
            $rating = new Rating($album_id, 'album');
            $flag   = new Userflag($album_id, 'album');

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $album->id . '&object_type=album&auth=' . scrub_out($_REQUEST['auth']);

            $theArray = [];

            $theArray["id"]   = $album->id;
            $theArray["name"] = $album->name;

            // Do a little check for artist stuff
            if ($album->album_artist_name != "") {
                $theArray['artist'] = array(
                    "id" => $album->artist_id,
                    "name" => $album->album_artist_name
                );
            } elseif ($album->artist_count != 1) {
                $theArray['artist'] = array(
                    "id" => 0,
                    "name" => 'Various'
                );
            } else {
                $theArray['artist'] = array(
                    "id" => $album->artist_id,
                    "name" => $album->artist_name
                );
            }

            // Handle includes
            if (in_array("songs", $include)) {
                $songs = self::songs($album->get_songs(), false, false);
            } else {
                $songs = $album->song_count;
            }

            //count multiple disks
            if ($album->allow_group_disks) {
                $disk = (count($album->album_suite) <= 1) ? $album->disk : count($album->album_suite);
            }

            $theArray['year']          = $album->year;
            $theArray['tracks']        = $songs;
            $theArray['disk']          = $disk;
            $theArray['tags']          = self::tags_string($album->tags);
            $theArray['art']           = $art_url;
            $theArray['flag']          = (!$flag->get_flag($user_id, false) ? 0 : 1);
            $theArray['preciserating'] = ($rating->get_user_rating() ?: 0);
            $theArray['rating']        = ($rating->get_user_rating() ?: 0);
            $theArray['averagerating'] = $rating->get_average_rating();
            $theArray['mbid']          = $album->mbid;

            array_push($JSON, $theArray);
        } // end foreach

        if ($encode) {
            return json_encode($JSON, JSON_PRETTY_PRINT);
        }

        return $JSON;
    } // albums

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty XML document
     *
     * @param array $playlists (description here...)
     * @return string return JSON
     */
    public static function playlists($playlists)
    {
        if (count($playlists) > self::$limit || self::$offset > 0) {
            $playlists = array_slice($playlists, self::$offset, self::$limit);
        }

        $allPlaylists = [];

        // Foreach the playlist ids
        foreach ($playlists as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist  = 1000000
             */
            if (str_replace('smart_', '', (string) $playlist_id) === (string) $playlist_id) {
                $playlist     = new Playlist($playlist_id);
                $playlist_id  = $playlist->id;
                $playlist->format();

                $playlist_name  = $playlist->name;
                $playlist_user  = $playlist->f_user;
                $playitem_total = $playlist->get_media_count('song');
                $playlist_type  = $playlist->type;
            } else {
                $playlist     = new Search(str_replace('smart_', '', (string) $playlist_id));
                $playlist->format();

                $playlist_name  = Search::get_name_byid(str_replace('smart_', '', (string) $playlist_id));
                if ($playlist->type !== 'public') {
                    $playlist_user  = $playlist->f_user;
                } else {
                    $playlist_user  = $playlist->type;
                }
                $last_count     = ((int) $playlist->last_count > 0) ? $playlist->last_count : 5000;
                $playitem_total = ($playlist->limit == 0) ? $last_count : $playlist->limit;
                $playlist_type  = $playlist->type;
            }
            // Build this element
            array_push($allPlaylists, [
                "id" => $playlist_id,
                "name" => $playlist_name,
                "owner" => $playlist_user,
                "items" => $playitem_total,
                "type" => $playlist_type]);
        } // end foreach

        return json_encode($allPlaylists, JSON_PRETTY_PRINT);
    } // playlists

    /**
     * shares
     *
     * This returns shares to the user, in a pretty json document with the information
     *
     * @param array $shares (description here...)
     * @return string return JSON
     */
    public static function shares($shares)
    {
        if (count($shares) > self::$limit || self::$offset > 0) {
            $shares = array_splice($shares, self::$offset, self::$limit);
        }

        $allShares = [];
        foreach ($shares as $share_id) {
            $share = new Share($share_id);
            $share->format();
            $share_name           = $share->f_name;
            $share_user           = $share->f_user;
            $share_allow_stream   = $share->f_allow_stream;
            $share_allow_download = $share->f_allow_download;
            $share_creation_date  = $share->f_creation_date;
            $share_lastvisit_date = $share->f_lastvisit_date;
            $share_object_type    = $share->object_type;
            $share_object_id      = $share->object_id;
            $share_expire_days    = $share->expire_days;
            $share_max_counter    = $share->max_counter;
            $share_counter        = $share->counter;
            $share_secret         = $share->secret;
            $share_public_url     = $share->public_url;
            $share_description    = $share->description;
            // Build this element
            array_push($allShares, [
                "id" => $share_id,
                "name" => $share_name,
                "owner" => $share_user,
                "allow_stream" => $share_allow_stream,
                "allow_download" => $share_allow_download,
                "creation_date" => $share_creation_date,
                "lastvisit_date" => $share_lastvisit_date,
                "object_type" => $share_object_type,
                "object_id" => $share_object_id,
                "expire_days" => $share_expire_days,
                "max_counter" => $share_max_counter,
                "counter" => $share_counter,
                "secret" => $share_secret,
                "public_url" => $share_public_url,
                "description" => $share_description]);
        } // end foreach

        return json_encode($allShares, JSON_PRETTY_PRINT);
    } // shares

    /**
     * catalogs
     *
     * This returns catalogs to the user, in a pretty json document with the information
     *
     * @param integer[] $catalogs group of catalog id's
     * @return string return JSON
     */
    public static function catalogs($catalogs)
    {
        if (count($catalogs) > self::$limit || self::$offset > 0) {
            $catalogs = array_splice($catalogs, self::$offset, self::$limit);
        }

        $allCatalogs = [];
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            $catalog->format();
            $catalog_name           = $catalog->name;
            $catalog_type           = $catalog->catalog_type;
            $catalog_gather_types   = $catalog->gather_types;
            $catalog_enabled        = $catalog->enabled;
            $catalog_last_add       = $catalog->f_add;
            $catalog_last_clean     = $catalog->f_clean;
            $catalog_last_update    = $catalog->f_update;
            $catalog_path           = $catalog->f_info;
            $catalog_rename_pattern = $catalog->rename_pattern;
            $catalog_sort_pattern   = $catalog->sort_pattern;
            // Build this element
            array_push($allCatalogs, [
                "id" => $catalog_id,
                "name" => $catalog_name,
                "type" => $catalog_type,
                "gather_types" => $catalog_gather_types,
                "last_add" => $catalog_enabled,
                "allow_download" => $catalog_last_add,
                "last_clean" => $catalog_last_clean,
                "last_update" => $catalog_last_update,
                "path" => $catalog_path,
                "rename_pattern" => $catalog_rename_pattern,
                "sort_pattern" => $catalog_sort_pattern]);
        } // end foreach

        return json_encode($allCatalogs, JSON_PRETTY_PRINT);
    } // catalogs

    /**
     * podcasts
     *
     * This returns podcasts to the user, in a pretty json document with the information
     *
     * @param array $podcasts (description here...)
     * @param boolean $episodes include the episodes of the podcast
     * @return string return JSON
     */
    public static function podcasts($podcasts, $episodes = false)
    {
        if (count($podcasts) > self::$limit || self::$offset > 0) {
            $podcasts = array_splice($podcasts, self::$offset, self::$limit);
        }

        $allPodcasts = [];
        foreach ($podcasts as $podcast_id) {
            $podcast = new Podcast($podcast_id);
            $podcast->format();
            $podcast_name        = $podcast->f_title;
            $podcast_description = $podcast->description;
            $podcast_language    = $podcast->f_language;
            $podcast_copyright   = $podcast->f_copyright;
            $podcast_feed_url    = $podcast->feed;
            $podcast_generator   = $podcast->f_generator;
            $podcast_website     = $podcast->f_website;
            $podcast_build_date  = $podcast->f_lastbuilddate;
            $podcast_sync_date   = $podcast->f_lastsync;
            $podcast_public_url  = $podcast->link;
            $podcast_episodes    = array();
            if ($episodes) {
                $items            = $podcast->get_episodes();
                $podcast_episodes = self::podcast_episodes($items, true);
            }
            // Build this element
            array_push($allPodcasts, [
                "id" => $podcast_id,
                "name" => $podcast_name,
                "description" => $podcast_description,
                "language" => $podcast_language,
                "copyright" => $podcast_copyright,
                "feed_url" => $podcast_feed_url,
                "generator" => $podcast_generator,
                "website" => $podcast_website,
                "build_date" => $podcast_build_date,
                "sync_date" => $podcast_sync_date,
                "public_url" => $podcast_public_url,
                "podcast_episode" => $podcast_episodes]);
        } // end foreach

        return json_encode($allPodcasts, JSON_PRETTY_PRINT);
    } // podcasts

    /**
     * podcast_episodes
     *
     * This returns podcasts to the user, in a pretty json document with the information
     *
     * @param  array   $podcast_episodes    (description here...)
     * @param  boolean $simple just return the data as an array for pretty somewhere else
     * @return array|string return JSON
     */
    public static function podcast_episodes($podcast_episodes, $simple = false)
    {
        if (count($podcast_episodes) > self::$limit || self::$offset > 0) {
            $podcast_episodes = array_splice($podcast_episodes, self::$offset, self::$limit);
        }
        $allEpisodes = array();
        foreach ($podcast_episodes as $episode_id) {
            $episode = new Podcast_Episode($episode_id);
            $episode->format();
            array_push($allEpisodes, [
                "id" => $episode_id,
                "name" => $episode->f_title,
                "description" => $episode->f_description,
                "category" => $episode->f_category,
                "author" => $episode->f_author,
                "author_full" => $episode->f_artist_full,
                "website" => $episode->f_website,
                "pubdate" => $episode->f_pubdate,
                "state" => $episode->f_state,
                "filelength" => $episode->f_time_h,
                "filesize" => $episode->f_size,
                "filename" => $episode->f_file,
                "url" => $episode->link]);
        }
        if ($simple) {
            return $allEpisodes;
        }

        return json_encode($allEpisodes, JSON_PRETTY_PRINT);
    } // podcast_episodes

    /**
     * songs
     *
     * This returns an array of songs populated from an array of song ids.
     * (Spiffy isn't it!)
     * @param $songs
     * @param boolean $user_id
     * @param bool $encode
     * @return array|string
     */
    public static function songs($songs, $user_id = false, $encode =  true)
    {
        if (count($songs) > self::$limit or self::$offset > 0) {
            $songs = array_slice($songs, self::$offset, self::$limit);
        }

        Song::build_cache($songs);
        Stream::set_session($_REQUEST['auth']);

        $JSON           = [];
        $playlist_track = 0;

        // Foreach the ids!
        foreach ($songs as $song_id) {
            $song = new Song($song_id);

            // If the song id is invalid/null
            if (!$song->id) {
                continue;
            }


            $song->format();
            //TODO: $tag_string
            $rating  = new Rating($song_id, 'song');
            $flag    = new Userflag($song_id, 'song');
            $art_url = Art::url($song->album, 'album', $_REQUEST['auth']);
            $playlist_track++;

            $ourSong = array(
                "id" => $song->id,
                "title" => $song->title,
                "name" => $song->title,
                "artist" => array(
                    "id" => $song->artist,
                    "name" => $song->get_artist_name()),
                "album" => array(
                    "id" => $song->album,
                    "name" => $song->get_album_name()),
            );
            if ($song->albumartist) {
                $ourSong['albumartist'] = array(
                    "id" => $song->albumartist,
                    "name" => $song->get_album_artist_name()
                );
            }

            $ourSong['filename']              = $song->file;
            $ourSong['track']                 = $song->track;
            $ourSong['playlisttrack']         = $playlist_track;
            $ourSong['time']                  = (int) $song->time;
            $ourSong['year']                  = $song->year;
            $ourSong['bitrate']               = $song->bitrate;
            $ourSong['rate']                  = $song->rate;
            $ourSong['mode']                  = $song->mode;
            $ourSong['mime']                  = $song->mime;
            $ourSong['url']                   = Song::play_url($song->id, '', 'api', false, $user_id);
            $ourSong['size']                  = $song->size;
            $ourSong['mbid']                  = $song->mbid;
            $ourSong['album_mbid']            = $song->album_mbid;
            $ourSong['artist_mbid']           = $song->artist_mbid;
            $ourSong['albumartist_mbid']      = $song->albumartist_mbid;
            $ourSong['art']                   = $art_url;
            $ourSong['flag']                  = (!$flag->get_flag($user_id, false) ? 0 : 1);
            $ourSong['preciserating']         = ($rating->get_user_rating() ?: 0);
            $ourSong['rating']                = ($rating->get_user_rating() ?: 0);
            $ourSong['averagerating']         = ($rating->get_average_rating() ?: 0);
            $ourSong['playcount']             = (int) $song->played;
            $ourSong['composer']              = $song->composer;
            $ourSong['channels']              = $song->channels;
            $ourSong['comment']               = $song->comment;
            $ourSong['publisher']             = $song->label;
            $ourSong['language']              = $song->language;
            $ourSong['replaygain_album_gain'] = $song->replaygain_album_gain;
            $ourSong['replaygain_album_peak'] = $song->replaygain_album_peak;
            $ourSong['replaygain_track_gain'] = $song->replaygain_track_gain;
            $ourSong['replaygain_track_peak'] = $song->replaygain_track_peak;

            if (Song::isCustomMetadataEnabled()) {
                foreach ($song->getMetadata() as $metadata) {
                    $meta_name           = str_replace(array(' ', '(', ')', '/', '\\', '#'), '_', $metadata->getField()->getName());
                    $ourSong[$meta_name] = $metadata->getData();
                }
            }
            $genre = [];
            foreach ($song->tags as $tag) {
                array_push($genre, $tag['name']);
            }
            $ourSong['genre'] = $genre;

            array_push($JSON, $ourSong);
        } // end foreach

        if ($encode) {
            return json_encode($JSON, JSON_PRETTY_PRINT);
        }

        return $JSON;
    } // songs

    /**
     * videos
     *
     * This builds the JSON document for displaying video objects
     *
     * @param    array    $videos    (description here...)
     * @param integer $user_id
     * @return string return JSON
     */
    public static function videos($videos, $user_id)
    {
        if (count($videos) > self::$limit or self::$offset > 0) {
            $videos = array_slice($videos, self::$offset, self::$limit);
        }

        $JSON   = [];
        foreach ($videos as $video_id) {
            $video = new Video($video_id);
            $video->format();
            array_push($JSON, array(
                "id" => $video->id,
                "title" => $video->title,
                "mime" => $video->mime,
                "resolution" => $video->f_resolution,
                "size" => $video->size,
                "tags" => self::tags_string($video->tags),
                "url" => Video::play_url($video->id, '', 'api', false, $user_id)
            ));
        } // end foreach

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // videos

    /**
     * democratic
     *
     * This handles creating an JSON document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param integer[] $object_ids Object IDs
     * @param boolean $user_id
     * @return string    return JSON
     */
    public static function democratic($object_ids=array(), $user_id = false)
    {
        if (!is_array($object_ids)) {
            $object_ids = array();
        }

        $democratic = Democratic::get_current_playlist();

        $JSON = [];

        foreach ($object_ids as $row_id => $data) {
            $song = new $data['object_type']($data['object_id']);
            $song->format();

            //FIXME: This is duplicate code and so wrong, functions need to be improved
            $tag           = new Tag($song->tags['0']);
            $song->genre   = $tag->id;
            $song->f_genre = $tag->name;

            $tag_string = self::tags_string($song->tags); //TODO

            $rating = new Rating($song->id, 'song');

            $art_url = Art::url($song->album, 'album', $_REQUEST['auth']);

            array_push($JSON, array(
                "id" => $song->id,
                "title" => $song->title,
                "artist" => array("id" => $song->artist, "name" => $song->f_artist_full),
                "album" => array("id" => $song->album, "name" => $song->f_album_full),
                "genre" => array("id" => $song->genre, "name" => $song->f_genre),
                "track" => $song->track,
                "time" => $song->time,
                "mime" => $song->mime,
                "url" => Song::play_url($song->id, '', 'api', false, $user_id),
                "size" => $song->size,
                "art" => $art_url,
                "preciserating" => $rating->get_user_rating(),
                "rating" => $rating->get_user_rating(),
                "averagerating" => $rating->get_average_rating(),
                "vote" => $democratic->get_vote($row_id)
            ));
        } // end foreach

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // democratic

    /**
     * user
     *
     * This handles creating an JSON document for a user
     *
     * @param  User    $user    User
     * @param  boolean $fullinfo
     * @return string  return JSON
     */
    public static function user(User $user, $fullinfo)
    {
        $JSON = array();
        $user->format();
        if ($fullinfo) {
            $JSON['user'] = array(
                "id" => $user->id,
                "username" => $user->username,
                "auth" => $user->apikey,
                "email" => $user->email,
                "access" => (string) $user->access,
                "fullname_public" => (string) $user->fullname_public,
                "validation" => $user->validation,
                "disabled" => (string) $user->disabled,
                "create_date" => $user->create_date,
                "last_seen" => $user->last_seen,
                "website" => $user->website,
                "state" => $user->state,
                "city" => $user->city
            );
        } else {
            $JSON['user'] = array(
                "id" => $user->id,
                "username" => $user->username,
                "create_date" => $user->create_date,
                "last_seen" => $user->last_seen,
                "website" => $user->website,
                "state" => $user->state,
                "city" => $user->city
            );
        }

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
     * @param    integer[]    $users    User identifier list
     * @return string return JSON
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
     * @param    integer[]    $shouts    Shout identifier list
     * @return string return JSON
     */
    public static function shouts($shouts)
    {
        $JSON = [];
        foreach ($shouts as $shout_id) {
            $shout = new Shoutbox($shout_id);
            $shout->format();
            $user     = new User($shout->user);
            $ourArray = array(
                "id" => $shout_id,
                "date" => $shout->date,
                "text" => $shout->text
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
     * @param    integer[]    $activities    Activity identifier list
     * @return string return JSON
     */
    public static function timeline($activities)
    {
        $JSON             = array();
        $JSON['timeline'] = []; // To match the XML style, IMO kinda uselesss
        foreach ($activities as $aid) {
            $activity = new Useractivity($aid);
            $user     = new User($activity->user);
            $ourArray = array(
                "id" => $aid,
                "data" => $activity->activity_date,
                "object_type" => $activity->object_type,
                "object_id" => $activity->object_id,
                "action" => $activity->action
            );

            if ($user->id) {
                $ourArray['username'] = $user->username;
            }
            array_push($JSON['timeline'], $ourArray);
        }

        return json_encode($JSON, JSON_PRETTY_PRINT);
    } // timeline

    //TODO: Podcast
} // end json_data.class
