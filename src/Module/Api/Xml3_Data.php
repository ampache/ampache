<?php

declare(strict_types=0);
/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Api;

use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Video;
use Ampache\Module\Playback\Stream;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Useractivity;
use Traversable;

/**
 * Xml_Data Class
 *
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls
 *
 */
class Xml3_Data
{
    // This is added so that we don't pop any webservers
    private static ?int $limit  = 5000;
    private static int $offset  = 0;
    private static string $type = '';

    /**
     * constructor
     *
     * We don't use this, as its really a static class
     */
    private function __construct()
    {
        // Rien a faire
    }

    /**
     * set_offset
     *
     * This takes an int and changes the offset
     *
     * @param int $offset
     */
    public static function set_offset($offset): void
    {
        self::$offset = (int)$offset;
    }

    /**
     * set_limit
     *
     * This sets the limit for any ampache transactions
     *
     * @param int|string $limit
     */
    public static function set_limit($limit): bool
    {
        if (!$limit) {
            return false;
        }

        self::$limit = (strtolower((string)$limit) == "none") ? null : (int)$limit;

        return true;
    }

    /**
     * set_type
     *
     * This sets the type of Xml_Data we are working on
     *
     * @param string $type    Xml_Data type
     */
    public static function set_type($type): void
    {
        if (in_array($type, ['rss', 'xspf', 'itunes'])) {
            self::$type = $type;
        }
    }

    /**
     * error
     *
     * This generates a standard XML Error message
     *
     * @param int $code    Error code
     * @param string $string    Error message
     */
    public static function error($code, $string): string
    {
        $string = "\t<error code=\"$code\"><![CDATA[" . $string . "]]></error>";

        return Xml_Data::output_xml($string);
    }

    /**
     * single_string
     *
     * This takes two values, first the key second the string
     *
     * @param string $key
     * @param string $string    xml data
     */
    public static function single_string($key, $string = ''): string
    {
        $final = self::_header();
        if (!empty($string)) {
            $final .= "\t<$key><![CDATA[" . $string . "]]></$key>";
        } else {
            $final .= "\t<$key />";
        }
        $final .= self::_footer();

        return $final;
    }

    /**
     * header
     *
     * This returns the header
     *
     * @see _header()
     */
    public static function header($title = null): string
    {
        return self::_header($title);
    }

    /**
     * footer
     *
     * This returns the footer
     *
     * @see _footer()
     */
    public static function footer(): string
    {
        return self::_footer();
    }

    /**
     * tags_string
     *
     * This returns the formatted 'tags' string for an xml document
     * @param array $tags
     */
    private static function tags_string($tags): string
    {
        $string = '';

        if (!empty($tags)) {
            $atags = [];
            foreach ($tags as $tag) {
                if (array_key_exists($tag['id'], $atags)) {
                    $atags[$tag['id']]['count']++;
                } else {
                    $atags[$tag['id']] = [
                        'name' => $tag['name'],
                        'count' => 1
                    ];
                }
            }

            foreach ($atags as $tag_id => $data) {
                $string .= "\t<tag id=\"" . $tag_id . "\" count=\"" . $data['count'] . "\" ><![CDATA[" . $data['name'] . "]]></tag>\n";
            }
        }

        return $string;
    }

    /**
     * playlist_song_tracks_string
     *
     * This returns the formatted 'playlistTrack' string for an xml document
     */
    private static function playlist_song_tracks_string($song, $playlist_data): string
    {
        if (empty($playlist_data)) {
            return "";
        }
        $playlist_track = "";
        foreach ($playlist_data as $playlist) {
            if ($playlist["object_id"] == $song->id) {
                $playlist_track .= "\t<playlisttrack>" . $playlist["track"] . "</playlisttrack>\n";
            }
        }

        return $playlist_track;
    }

    /**
     * keyed_array
     *
     * This will build an xml document from a key'd array
     */
    public static function keyed_array(array $array, ?bool $callback = false): string
    {
        $string = '';

        // Foreach it
        foreach ($array as $key => $value) {
            $attribute = '';
            // See if the key has attributes
            if (is_array($value) && isset($value['<attributes>'])) {
                $attribute = ' ' . $value['<attributes>'];
                $key       = $value['value'];
            }

            // If it's an array, run again
            if (is_array($value)) {
                $value = self::keyed_array($value, true);
                $string .= "<$key$attribute>\n$value\n</$key>\n";
            } else {
                $string .= "\t<$key$attribute><![CDATA[" . $value . "]]></$key>\n";
            }
        } // end foreach

        if (!$callback) {
            $string = Xml_Data::output_xml($string);
        }

        return $string;
    }

    /**
     * tags
     *
     * This returns tags to the user, in a pretty xml document with the information
     *
     * @param array $tags
     */
    public static function tags($tags): string
    {
        $string = "<total_count>" . count($tags) . "</total_count>\n";

        if (count($tags) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $tags = array_splice($tags, self::$offset, self::$limit);
            } else {
                $tags = array_splice($tags, self::$offset);
            }
        }

        foreach ($tags as $tag_id) {
            $tag    = new Tag($tag_id);
            $counts = $tag->count();
            $string .= "<tag id=\"$tag_id\">\n\t<name><![CDATA[" . $tag->name . "]]></name>\n\t<albums>" . (int)($counts['album'] ?? 0) . "</albums>\n\t<artists>" . (int)($counts['artist'] ?? 0) . "</artists>\n\t<songs>" . (int)($counts['song'] ?? 0) . "</songs>\n\t<videos>" . (int)($counts['video'] ?? 0) . "</videos>\n\t<playlists>" . (int)($counts['playlist'] ?? 0) . "</playlists>\n\t<stream>" . (int)($counts['live_stream'] ?? 0) . "</stream>\n</tag>\n";
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty xml document with the information
     * we want
     *
     * @param array $artists
     * @param array $include    Array of other items to include
     * @param User $user
     * @param bool $full_xml  whether to return a full XML document or just the node
     */
    public static function artists($artists, $include, $user, $full_xml = true): string
    {
        if (null == $include) {
            $include = [];
        }
        $string = "<total_count>" . count($artists) . "</total_count>\n";

        if (count($artists) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $artists = array_splice($artists, self::$offset, self::$limit);
            } else {
                $artists = array_splice($artists, self::$offset);
            }
        }

        Rating::build_cache('artist', $artists);

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            if ($artist->isNew()) {
                continue;
            }
            $artist->format();

            $rating      = new Rating($artist_id, 'artist');
            $user_rating = $rating->get_user_rating($user->getId());
            $tag_string  = self::tags_string($artist->tags);

            // Build the Art URL, include session
            $art_url = AmpConfig::get_web_path('/client') . '/image.php?object_id=' . $artist_id . '&object_type=artist';

            // Handle includes
            if (in_array("albums", $include)) {
                $albums = self::albums(self::getAlbumRepository()->getAlbumByArtist($artist->id), $include, $user, false);
            } else {
                $albums = (AmpConfig::get('album_group'))
                    ? $artist->album_count
                    : $artist->album_disk_count;
            }
            if (in_array("songs", $include)) {
                $songs = self::songs(self::getSongRepository()->getByArtist($artist_id), $user, '', false);
            } else {
                $songs = $artist->song_count;
            }

            $string .= "<artist id=\"" . $artist->id . "\">\n\t<name><![CDATA[" . $artist->get_fullname() . "]]></name>\n" . $tag_string . "\t<albums>" . $albums . "</albums>\n\t<songs>" . $songs . "</songs>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<preciserating>" . ($user_rating ?? 0) . "</preciserating>\n\t<rating>" . ($user_rating ?? 0) . "</rating>\n\t<averagerating>" . ($rating->get_average_rating() ?? 0) . "</averagerating>\n\t<mbid>" . $artist->mbid . "</mbid>\n\t<summary><![CDATA[" . $artist->summary . "]]></summary>\n\t<yearformed>" . $artist->yearformed . "</yearformed>\n\t<placeformed><![CDATA[" . $artist->placeformed . "]]></placeformed>\n</artist>\n";
        } // end foreach artists

        return Xml_Data::output_xml($string, $full_xml);
    }

    /**
     * albums
     *
     * This echos out a standard albums XML document, it pays attention to the limit
     *
     * @param array $albums
     * @param array|false $include Array of other items to include
     * @param User $user
     * @param bool $full_xml  whether to return a full XML document or just the node
     */
    public static function albums($albums, $include, $user, $full_xml = true): string
    {
        $string = "<total_count>" . count($albums) . "</total_count>\n";

        if (count($albums) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $albums = array_splice($albums, self::$offset, self::$limit);
            } else {
                $albums = array_splice($albums, self::$offset);
            }
        }

        Rating::build_cache('album', $albums);

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            if ($album->isNew()) {
                continue;
            }
            $album->format();

            $rating      = new Rating($album_id, 'album');
            $user_rating = $rating->get_user_rating($user->getId());

            // Build the Art URL, include session
            $art_url = AmpConfig::get_web_path('/client') . '/image.php?object_id=' . $album->id . '&object_type=album';

            $string .= "<album id=\"" . $album->id . "\">\n\t<name><![CDATA[" . $album->name . "]]></name>\n";

            if ($album->get_artist_fullname() != "") {
                $string .= "\t<artist id=\"$album->album_artist\"><![CDATA[" . $album->f_artist_name . "]]></artist>\n";
            }

            // Handle includes
            if ($include && in_array("songs", $include)) {
                $songs = self::songs(self::getSongRepository()->getByAlbum($album->id), $user, '', false);
            } else {
                $songs = $album->song_count;
            }

            $string .= "\t<year>" . $album->year . "</year>\n\t<tracks>" . $songs . "</tracks>\n\t<disk>" . $album->disk_count . "</disk>\n" . self::tags_string($album->tags) . "\t<art><![CDATA[" . $art_url . "]]></art>\n\t<preciserating>" . ($user_rating ?? 0) . "</preciserating>\n\t<rating>" . ($user_rating ?? 0) . "</rating>\n\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n\t<mbid>" . $album->mbid . "</mbid>\n</album>\n";
        } // end foreach

        return Xml_Data::output_xml($string, $full_xml);
    }

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty XML document
     *
     * @param array $playlists
     */
    public static function playlists($playlists): string
    {
        $string = "<total_count>" . count($playlists) . "</total_count>\n";

        if (count($playlists) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $playlists = array_slice($playlists, self::$offset, self::$limit);
            } else {
                $playlists = array_slice($playlists, self::$offset);
            }
        }

        // Foreach the playlist ids
        foreach ($playlists as $playlist_id) {
            $playlist = new Playlist($playlist_id);
            if ($playlist->isNew()) {
                continue;
            }
            $playlist->format();
            $item_total = $playlist->get_media_count('song');

            // Build this element
            $string .= "<playlist id=\"" . $playlist->id . "\">\n\t<name><![CDATA[" . $playlist->name . "]]></name>\n\t<owner><![CDATA[" . $playlist->username . "]]></owner>\n\t<items>" . $item_total . "</items>\n\t<type>" . $playlist->type . "</type>\n</playlist>\n";
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * songs
     *
     * This returns an xml document from an array of song ids
     */
    public static function songs($songs, $user, $playlist_data = '', $full_xml = true): string
    {
        $string = "<total_count>" . count($songs) . "</total_count>\n";

        if (count($songs) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $songs = array_slice($songs, self::$offset, self::$limit);
            } else {
                $songs = array_slice($songs, self::$offset);
            }
        }

        Song::build_cache($songs);
        Stream::set_session($_REQUEST['auth'] ?? '');

        // Foreach the ids!
        foreach ($songs as $song_id) {
            $song = new Song($song_id);

            // If the song id is invalid/null
            if ($song->isNew()) {
                continue;
            }

            $song->format();
            $playlist_track_string = self::playlist_song_tracks_string($song, $playlist_data);
            $tag_string            = self::tags_string(Tag::get_top_tags('song', $song_id));
            $rating                = new Rating($song_id, 'song');
            $user_rating           = $rating->get_user_rating($user->getId());
            $art_url               = Art::url($song->album, 'album', $_REQUEST['auth'] ?? '');
            $songMime              = $song->mime;
            $songBitrate           = $song->bitrate;
            $play_url              = $song->play_url('', 'api', false, $user->id, $user->streamtoken);

            $string .= "<song id=\"" . $song->id . "\">\n\t<title><![CDATA[" . $song->title . "]]></title>\n\t<name><![CDATA[" . $song->title . "]]></name>\n"
                . "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_artist_fullname() . "]]></artist>\n"
                . "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_fullname() . "]]></album>\n";
            if ($song->albumartist) {
                $string .= "\t<albumartist id=\"" . $song->albumartist . "\"><![CDATA[" . $song->get_album_artist_fullname() . "]]></albumartist>\n";
            }
            $string .= $tag_string . "\t<filename><![CDATA[" . $song->file . "]]></filename>\n\t<track>" . $song->track . "</track>\n" . $playlist_track_string . "\t<time>" . $song->time . "</time>\n\t<year>" . $song->year . "</year>\n\t<bitrate>" . $songBitrate . "</bitrate>\n\t<rate>" . $song->rate . "</rate>\n\t<mode>" . $song->mode . "</mode>\n\t<mime>" . $songMime . "</mime>\n\t<url><![CDATA[" . $play_url . "]]></url>\n\t<size>" . $song->size . "</size>\n\t<mbid>" . $song->mbid . "</mbid>\n\t<album_mbid>" . $song->album_mbid . "</album_mbid>\n\t<artist_mbid>" . $song->artist_mbid . "</artist_mbid>\n\t<albumartist_mbid>" . $song->albumartist_mbid . "</albumartist_mbid>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<preciserating>" . ($user_rating ?? 0) . "</preciserating>\n\t<rating>" . ($user_rating ?? 0) . "</rating>\n\t<averagerating>" . ($rating->get_average_rating() ?? 0) . "</averagerating>\n\t<composer><![CDATA[" . $song->composer . "]]></composer>\n\t<channels>" . $song->channels . "</channels>\n\t<comment><![CDATA[" . $song->comment . "]]></comment>\n";

            $string .= "\t<publisher><![CDATA[" . $song->label . "]]></publisher>\n\t<language>" . $song->language . "</language>\n\t<replaygain_album_gain>" . $song->replaygain_album_gain . "</replaygain_album_gain>\n\t<replaygain_album_peak>" . $song->replaygain_album_peak . "</replaygain_album_peak>\n\t<replaygain_track_gain>" . $song->replaygain_track_gain . "</replaygain_track_gain>\n\t<replaygain_track_peak>" . $song->replaygain_track_peak . "</replaygain_track_peak>\n";
            foreach ($song->tags as $tag) {
                $string .= "\t<genre><![CDATA[" . $tag['name'] . "]]></genre>\n";
            }

            $string .= "</song>\n";
        } // end foreach

        return Xml_Data::output_xml($string, $full_xml);
    }

    /**
     * videos
     *
     * This builds the xml document for displaying video objects
     *
     * @param array $videos
     */
    public static function videos($videos): string
    {
        $string = "<total_count>" . count($videos) . "</total_count>\n";

        if (count($videos) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $videos = array_slice($videos, self::$offset, self::$limit);
            } else {
                $videos = array_slice($videos, self::$offset);
            }
        }

        foreach ($videos as $video_id) {
            $video = new Video($video_id);
            if ($video->isNew()) {
                continue;
            }
            $video->format();

            $string .= "<video id=\"" . $video->id . "\">\n\t<title><![CDATA[" . $video->title . "]]></title>\n\t<name><![CDATA[" . $video->title . "]]></name>\n\t<mime><![CDATA[" . $video->mime . "]]></mime>\n\t<resolution>" . $video->f_resolution . "</resolution>\n\t<size>" . $video->size . "</size>\n" . self::tags_string($video->tags) . "\t<url><![CDATA[" . $video->play_url('', 'api') . "]]></url>\n</video>\n";
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * democratic
     *
     * This handles creating an xml document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param array $object_ids    Object IDs
     * @param User $user
     */
    public static function democratic($object_ids, $user): string
    {
        if (!is_array($object_ids)) {
            $object_ids = [];
        }
        $democratic = Democratic::get_current_playlist($user);
        $string     = '';

        foreach ($object_ids as $row_id => $data) {
            $className = ObjectTypeToClassNameMapper::map($data['object_type']);
            /** @var Song $song */
            $song = new $className($data['object_id']);
            if ($song->isNew()) {
                continue;
            }
            $song->format();

            //FIXME: This is duplicate code and so wrong, functions need to be improved
            $tag         = new Tag($song->tags['0']);
            $tag_string  = self::tags_string($song->tags);
            $rating      = new Rating($song->id, 'song');
            $user_rating = $rating->get_user_rating($user->getId());
            $art_url     = Art::url($song->album, 'album', $_REQUEST['auth'] ?? '');
            $songMime    = $song->mime;
            $play_url    = $song->play_url('', 'api', false, $user->id, $user->streamtoken);

            $string .= "<song id=\"" . $song->id . "\">\n\t<title><![CDATA[" . $song->title . "]]></title>\n\t<name><![CDATA[" . $song->title . "]]></name>\n"
                . "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_artist_fullname() . "]]></artist>\n"
                . "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_fullname() . "]]></album>\n"
                . "\t<genre id=\"" . $tag->id . "\"><![CDATA[" . $tag->name . "]]></genre>\n" . $tag_string . "\t<track>" . $song->track . "</track>\n\t<time>" . $song->time . "</time>\n\t<mime>" . $songMime . "</mime>\n\t<url><![CDATA[" . $play_url . "]]></url>\n\t<size>" . $song->size . "</size>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<preciserating>" . ($user_rating ?? 0) . "</preciserating>\n\t<rating>" . ($user_rating ?? 0) . "</rating>\n\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n\t<vote>" . $democratic->get_vote($row_id) . "</vote>\n</song>\n";
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * user
     *
     * This handles creating an xml document for a user
     */
    public static function user(User $user): string
    {
        $user->format();

        $string = "<user id=\"" . $user->id . "\">\n\t<username><![CDATA[" . $user->username . "]]></username>\n\t<create_date>" . $user->create_date . "</create_date>\n\t<last_seen>" . $user->last_seen . "</last_seen>\n\t<website><![CDATA[" . $user->website . "]]></website>\n\t<state><![CDATA[" . $user->state . "]]></state>\n\t<city><![CDATA[" . $user->city . "]]></city>\n";
        if ($user->fullname_public) {
            $string .= "\t<fullname><![CDATA[" . $user->fullname . "]]></fullname>\n";
        }
        $string .= "</user>\n";

        return Xml_Data::output_xml($string);
    }

    /**
     * users
     *
     * This handles creating an xml document for a user list
     *
     * @param int[] $users    User identifier list
     */
    public static function users($users): string
    {
        $string = "<users>\n";
        foreach ($users as $user_id) {
            $user = new User($user_id);
            if ($user->isNew() === false) {
                $string .= "\t<username><![CDATA[" . $user->username . "]]></username>\n";
            }
        }
        $string .= "</users>\n";

        return Xml_Data::output_xml($string);
    }

    /**
     * shouts
     *
     * This handles creating an xml document for a shout list
     *
     * @param Traversable<Shoutbox> $shouts Shout identifier list
     */
    public static function shouts(Traversable $shouts): string
    {
        $string = "<shouts>\n";

        /** @var Shoutbox $shout */
        foreach ($shouts as $shout) {
            $user = $shout->getUser();
            $string .= "\t<shout id=\"" . $shout->getId() . "\">\n\t\t<date>" . $shout->getDate()->getTimestamp() . "</date>\n\t\t<text><![CDATA[" . $shout->getText() . "]]></text>\n";
            if ($user !== null) {
                $string .= "\t\t<username><![CDATA[" . $user->getUsername() . "]]></username>";
            }
            $string .= "\t</shout>n";
        }
        $string .= "</shouts>\n";

        return Xml_Data::output_xml($string);
    }

    /**
     * timeline
     *
     * This handles creating an xml document for an activity list
     *
     * @param int[] $activities    Activity identifier list
     */
    public static function timeline($activities): string
    {
        $string = "<timeline>\n";
        foreach ($activities as $aid) {
            $activity = new Useractivity($aid);
            $user     = new User($activity->user);
            $string .= "\t<activity id=\"" . $aid . "\">\n\t\t<date>" . $activity->activity_date . "</date>\n\t\t<object_type><![CDATA[" . $activity->object_type . "]]></object_type>\n\t\t<object_id>" . $activity->object_id . "</object_id>\n\t\t<action><![CDATA[" . $activity->action . "]]></action>\n";
            if ($user->isNew() === false) {
                $string .= "\t\t<username><![CDATA[" . $user->username . "]]></username>\n";
            }
            $string .= "\t</activity>\n";
        }
        $string .= "</timeline>\n";

        return self::_header() . $string . self::_footer();
    }

    /**
     * _header
     *
     * this returns a standard header, there are a few types
     * so we allow them to pass a type if they want to
     */
    private static function _header($title = null): string
    {
        switch (self::$type) {
            case 'xspf':
                $header = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<playlist version = \"1\" xmlns=\"http://xspf.org/ns/0/\">\n<title>" . ($title ?? T_("Ampache XSPF Playlist")) . "</title>\n<creator>" . scrub_out(AmpConfig::get('site_title')) . "</creator>\n<annotation>" . scrub_out(AmpConfig::get('site_title')) . "</annotation>\n<info>" . AmpConfig::get_web_path('/client') . "</info>\n<trackList>\n";
                break;
            case 'itunes':
                $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<!-- XML Generated by Ampache v." . AmpConfig::get('version') . " -->\n";
                break;
            case 'rss':
                $header = "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset') . "\" ?>\n <!-- RSS Generated by Ampache v." . AmpConfig::get('version') . " on " . date("r", time()) . "-->\n<rss version=\"2.0\">\n<channel>\n";
                break;
            default:
                $header = "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset') . "\" ?>\n<root>\n";
                break;
        } // end switch

        return $header;
    }

    /**
     * _footer
     *
     * this returns the footer for this document, these are pretty boring
     */
    private static function _footer(): string
    {
        switch (self::$type) {
            case 'itunes':
                $footer = "\t\t</dict>\t\n</dict>\n</plist>\n";
                break;
            case 'xspf':
                $footer = "</trackList>\n</playlist>\n";
                break;
            case 'rss':
                $footer = "\n</channel>\n</rss>\n";
                break;
            default:
                $footer = "\n</root>\n";
                break;
        } // end switch on type

        return $footer;
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
