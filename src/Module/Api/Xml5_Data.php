<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Metadata;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Useractivity;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Traversable;

/**
 * XML5_Data Class
 *
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls
 */
class Xml5_Data
{
    // This is added so that we don't pop any webservers
    private static ?int $limit  = 5000;
    private static int $offset  = 0;
    private static string $type = '';

    /**
     * set_offset
     *
     * This takes an int and changes the offset
     *
     * @param int|string $offset Change the starting position of your results. (e.g 5001 when selecting in groups of 5000)
     */
    public static function set_offset(int|string $offset): void
    {
        self::$offset = (int)$offset;
    }

    /**
     * set_limit
     *
     * This sets the limit for any ampache transactions
     *
     * @param int|string $limit Set a limit on your results
     */
    public static function set_limit(int|string $limit): bool
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
     */
    public static function set_type(string $type): bool
    {
        if (!in_array(strtolower($type), ['rss', 'xspf', 'itunes'])) {
            return false;
        }

        self::$type = $type;

        return true;
    }

    /**
     * error
     *
     * This generates a standard XML Error message
     */
    public static function error(int|string $code, string $string, string $action, string $type): string
    {
        $xml_string = "\t<error errorCode=\"$code\">\n\t\t<errorAction><![CDATA[" . $action . "]]></errorAction>\n\t\t<errorType><![CDATA[" . $type . "]]></errorType>\n\t\t<errorMessage><![CDATA[" . $string . "]]></errorMessage>\n\t</error>";

        return Xml_Data::output_xml($xml_string);
    }

    /**
     * success
     *
     * This generates a standard XML Success message
     * nothing fancy here...
     *
     * @param string $string success message
     * @param array<string, string> $return_data
     * @return string
     */
    public static function success(string $string, array $return_data = []): string
    {
        $xml_string = "\t<success code=\"1\"><![CDATA[" . $string . "]]></success>";
        foreach ($return_data as $title => $data) {
            $xml_string .= "\n\t<$title><![CDATA[" . $data . "]]></$title>";
        }

        return Xml_Data::output_xml($xml_string);
    }

    /**
     * empty
     *
     * This generates an empty root element
     */
    public static function empty(): string
    {
        return "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset', 'UTF-8') . "\" ?>\n<root>\n</root>\n";
    }

    /**
     * header
     *
     * This returns the header
     * @see _header()
     */
    public static function header(?string $title = null): string
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
     * genre_string
     *
     * This returns the formatted 'genre' string for an xml document
     * @param list<array{id: int, name: string, is_hidden: int, count: int}> $tags
     */
    private static function genre_string(array $tags): string
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
                $string .= "\t<genre id=\"" . $tag_id . "\"><![CDATA[" . $data['name'] . "]]></genre>\n";
            }
        }

        return $string;
    }

    /**
     * keyed_array
     *
     * This will build an xml document from a key'd array,
     */
    public static function keyed_array(array $array, bool $callback = false, bool|string $object = false): string
    {
        $string = '';
        // Foreach it
        foreach ($array as $key => $value) {
            $attribute = '';
            // See if the key has attributes
            if (is_array($value) && isset($value['attributes'])) {
                $attribute = ' ' . $value['attributes'];
                $key       = $value['value'];
            }

            // If it's an array, run again
            if (is_array($value)) {
                $value = self::keyed_array($value, true);
                $string .= ($object) ? "<$object>\n$value\n</$object>\n" : "<$key$attribute>\n$value\n</$key>\n";
            } else {
                $string .= ($object) ? "\t<$object index=\"" . $key . "\"><![CDATA[" . $value . "]]></$object>\n" : "\t<$key$attribute><![CDATA[" . $value . "]]></$key>\n";
            }
        } // end foreach

        if (!$callback) {
            $string = Xml_Data::output_xml($string);
        }

        return $string;
    }

    /**
     * object_array
     *
     * This will build an xml document from an array of arrays, an id is required for the array data
     * <root>
     *   <$object_type> //optional
     *     <$item id="123">
     *       <data></data>
     */
    public static function object_array(array $array, string $item, string $object_type = ''): string
    {
        $string = ($object_type == '') ? '' : "<$object_type>\n";
        // Foreach it
        foreach ($array as $object) {
            $string .= "\t<$item id=\"" . $object['id'] . "\">\n";
            foreach ($object as $name => $value) {
                if ($name === 'widget') {
                    $widget_type = $value[0];
                    $filter      = '';
                    if (is_array($value[1])) {
                        foreach ($value[1] as $key => $val) {
                            $filter .= "\t\t<$widget_type id=\"$key\"><![CDATA[" . $val . "]]></$widget_type>\n";
                        }
                    }
                } elseif (($name === 'values' || $name === 'subtypes') && is_array($value)) {
                    $filter = '';
                    foreach ($value as $key => $val) {
                        $filter .= "\t\t<value id=\"$key\"><![CDATA[" . $val . "]]></value>\n";
                    }
                } else {
                    $filter = (is_numeric($value)) ? $value : "<![CDATA[" . $value . "]]>";
                }
                $string .= ($name !== 'id') ? "\t\t<$name>$filter</$name>\n" : '';
            }
            $string .= "\t</$item>\n";
        } // end foreach
        $string .= ($object_type == '') ? '' : "</$object_type>";

        return Xml_Data::output_xml($string);
    }

    /**
     * indexes
     *
     * This takes an array of object_ids and return XML based on the type of object
     * we want
     *
     * @param list<int|string> $objects Array of object_ids (Mixed string|int)
     * @param string $object_type 'artist'|'album'|'song'|'playlist'|'share'|'podcast'|'podcast_episode'|'video'|'live_stream'
     * @param User $user
     * @param string $auth
     * @param bool $full_xml whether to return a full XML document or just the node.
     * @param bool $include include episodes from podcasts or tracks in a playlist
     * @return string
     */
    public static function indexes(array $objects, string $object_type, User $user, string $auth, bool $full_xml = true, bool $include = false): string
    {
        if ((count($objects) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        // you might not want the joined tables for playlists
        $total_count = (AmpConfig::get('hide_search', false) && $object_type == 'playlist')
            ? Catalog::get_update_info('search', $user->id) + Catalog::get_update_info('playlist', $user->id)
            : Catalog::get_update_info($object_type, $user->id);
        $string = ($full_xml) ? "<total_count>" . $total_count . "</total_count>\n" : '';

        // here is where we call the object type
        switch ($object_type) {
            case 'artist':
                foreach ($objects as $object_id) {
                    if ($include) {
                        $string .= self::artists([(int)$object_id], ['songs', 'albums'], $user, $auth, false);
                    } else {
                        $artist = new Artist((int)$object_id);
                        if ($artist->isNew()) {
                            break;
                        }
                        $albums = self::getAlbumRepository()->getAlbumByArtist((int)$object_id);
                        $string .= "<$object_type id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $artist->get_fullname() . "]]></name>\n";
                        foreach ($albums as $album_id) {
                            if ($album_id > 0) {
                                $album = new Album($album_id);
                                $string .= "\t<album id=\"" . $album_id . '"><![CDATA[' . $album->get_fullname() . "]]></album>\n";
                            }
                        }
                        $string .= "</$object_type>\n";
                    }
                }
                break;
            case 'album':
                foreach ($objects as $object_id) {
                    if ($include) {
                        $string .= self::albums([(int)$object_id], ['songs'], $user, $auth, false);
                    } else {
                        $album = new Album((int)$object_id);
                        $string .= "<$object_type id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $album->get_fullname() . "]]></name>\n";
                        if ($album->get_artist_fullname() != "") {
                            $string .= "\t\t<artist id=\"" . $album->album_artist . "\"><![CDATA[" . $album->get_artist_fullname() . "]]></artist>\n";
                        }
                        $string .= "</$object_type>\n";
                    }
                }
                break;
            case 'song':
                foreach ($objects as $object_id) {
                    $song = new Song((int)$object_id);
                    $song->fill_ext_info();
                    $string .= "<$object_type id=\"" . $object_id . "\">\n\t<title><![CDATA[" . $song->get_fullname() . "]]></title>\n\t<name><![CDATA[" . $song->get_fullname() . "]]></name>\n"
                        . "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_artist_fullname() . "]]></artist>\n"
                        . "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_fullname() . "]]></album>\n";
                    if ($song->get_album_artist_fullname() != "") {
                        $string .= "\t<albumartist id=\"" . $song->albumartist . "\"><![CDATA[" . $song->get_album_artist_fullname() . "]]></albumartist>\n";
                    }
                    $string .= "\t<disk><![CDATA[" . $song->disk . "]]></disk>\n\t<track>" . $song->track . "</track>\n</$object_type>\n";
                }
                break;
            case 'playlist':
                foreach ($objects as $object_id) {
                    if ((int)$object_id === 0) {
                        $playlist       = new Search((int)str_replace('smart_', '', (string)$object_id), 'song', $user);
                        $playitem_total = $playlist->last_count;
                    } else {
                        $playlist       = new Playlist((int)$object_id);
                        $playitem_total = $playlist->get_media_count('song');
                    }
                    $playlist_name = $playlist->get_fullname();
                    $playlist_user = $playlist->username;

                    $songs = ($include) ? $playlist->get_items() : [];
                    $string .= "<$object_type id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $playlist_name . "]]></name>\n\t<items>" . (int)$playitem_total . "</items>\n\t<owner><![CDATA[" . $playlist_user . "]]></owner>\n\t<type><![CDATA[" . $playlist->type . "]]></type>\n";
                    $playlist_track = 0;
                    foreach ($songs as $song_id) {
                        if ($song_id['object_type']->value == 'song') {
                            $playlist_track++;
                            $string .= "\t\t<playlisttrack id=\"" . $song_id['object_id'] . "\">" . $playlist_track . "</playlisttrack>\n";
                        }
                    }
                    $string .= "</$object_type>\n";
                }
                break;
            case 'share':
                $string .= self::shares($objects, $user, false);
                break;
            case 'podcast':
                foreach ($objects as $object_id) {
                    $podcast = self::getPodcastRepository()->findById((int)$object_id);
                    if ($podcast !== null) {
                        $string .= "<podcast id=\"$object_id\">\n\t<name><![CDATA[" . $podcast->get_fullname() . "]]></name>\n\t<description><![CDATA[" . $podcast->get_description() . "]]></description>\n\t<language><![CDATA[" . scrub_out($podcast->getLanguage()) . "]]></language>\n\t<copyright><![CDATA[" . scrub_out($podcast->getCopyright()) . "]]></copyright>\n\t<feed_url><![CDATA[" . $podcast->getFeedUrl() . "]]></feed_url>\n\t<generator><![CDATA[" . scrub_out($podcast->getGenerator()) . "]]></generator>\n\t<website><![CDATA[" . scrub_out($podcast->getWebsite()) . "]]></website>\n\t<build_date><![CDATA[" . $podcast->getLastBuildDate()->format(DATE_ATOM) . "]]></build_date>\n\t<sync_date><![CDATA[" . $podcast->getLastSyncDate()->format(DATE_ATOM) . "]]></sync_date>\n\t<public_url><![CDATA[" . $podcast->get_link() . "]]></public_url>\n";
                        if ($include) {
                            $episodes = $podcast->getEpisodeIds();
                            foreach ($episodes as $episode_id) {
                                $string .= self::podcast_episodes([(int)$episode_id], $user, $auth, false);
                            }
                        }
                        $string .= "\t</podcast>\n";
                    }
                }
                break;
            case 'podcast_episode':
                $string .= self::podcast_episodes($objects, $user, $auth, false);
                break;
            case 'video':
                $string .= self::videos($objects, $user, $auth, false);
                break;
            case 'live_stream':
                $string .= self::live_streams($objects, $user, false);
        }

        return Xml_Data::output_xml($string, $full_xml);
    }

    /**
     * licenses
     *
     * This returns licenses to the user, in a pretty xml document with the information
     *
     * @param list<int|string> $licenses Licence id's assigned to songs and artists
     * @param User $user
     * @return string
     */
    public static function licenses(array $licenses, User $user): string
    {
        if ((count($licenses) > self::$limit || self::$offset > 0) && self::$limit) {
            $licenses = array_splice($licenses, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_update_info('license', $user->id) . "</total_count>\n";

        $licenseRepository = self::getLicenseRepository();

        foreach ($licenses as $license_id) {
            $license = $licenseRepository->findById((int)$license_id);
            if ($license !== null) {
                $string .= "<license id=\"$license_id\">\n\t<name><![CDATA[" . $license->getName() . "]]></name>\n\t<description><![CDATA[" . $license->getDescription() . "]]></description>\n\t<external_link><![CDATA[" . $license->getExternalLink() . "]]></external_link>\n</license>\n";
            }
        }

        return Xml_Data::output_xml($string);
    }

    /**
     * labels
     *
     * This returns labels to the user, in a pretty xml document with the information
     *
     * @param list<int|string> $labels
     * @param User $user
     * @return string
     */
    public static function labels(array $labels, User $user): string
    {
        if ((count($labels) > self::$limit || self::$offset > 0) && self::$limit) {
            $labels = array_splice($labels, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_update_info('label', $user->id) . "</total_count>\n";

        $labelRepository = self::getLabelRepository();

        foreach ($labels as $label_id) {
            $label = $labelRepository->findById((int)$label_id);
            if ($label === null) {
                continue;
            }

            $string .= "<license id=\"$label_id\">\n\t<name><![CDATA[" . $label->get_fullname() . "]]></name>\n\t<artists><![CDATA[" . $label->get_artist_count() . "]]></artists>\n\t<summary><![CDATA[" . $label->summary . "]]></summary>\n\t<external_link><![CDATA[" . $label->get_link() . "]]></external_link>\n\t<address><![CDATA[" . $label->address . "]]></address>\n\t<category><![CDATA[" . $label->category . "]]></category>\n\t<email><![CDATA[" . $label->email . "]]></email>\n\t<website><![CDATA[" . $label->website . "]]></website>\n\t<user><![CDATA[" . $label->user . "]]></user>\n</license>\n";
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * live_streams
     *
     * This returns live_streams to the user, in a pretty xml document with the information
     *
     * @param list<int|string> $live_streams
     * @param User $user
     * @param bool $full_xml whether to return a full XML document or just the node.
     * @return string
     */
    public static function live_streams(array $live_streams, User $user, bool $full_xml = true): string
    {
        if ((count($live_streams) > self::$limit || self::$offset > 0) && self::$limit) {
            $live_streams = array_splice($live_streams, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('live_stream', $user->id) . "</total_count>\n" : '';

        foreach ($live_streams as $live_stream_id) {
            $live_stream = new Live_Stream((int)$live_stream_id);
            if ($live_stream->isNew()) {
                continue;
            }

            $string .= "<live_stream id=\"" . $live_stream_id . "\">\n\t<name><![CDATA[" . $live_stream->get_fullname() . "]]></name>\n\t<url><![CDATA[" . $live_stream->url . "]]></url>\n\t<codec><![CDATA[" . $live_stream->codec . "]]></codec>\n\t<catalog>" . $live_stream->catalog . "</catalog>\n\t<site_url><![CDATA[" . $live_stream->site_url . "]]></site_url>\n</live_stream>\n";
        } // end foreach

        return Xml_Data::output_xml($string, $full_xml);
    }

    /**
     * genres
     *
     * This returns genres to the user, in a pretty xml document with the information
     *
     * @param list<int|string> $tags Genre id's to include
     * @param User $user
     * @return string
     */
    public static function genres(array $tags, User $user): string
    {
        if ((count($tags) > self::$limit || self::$offset > 0) && self::$limit) {
            $tags = array_splice($tags, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_update_info('tag', $user->id) . "</total_count>\n";

        foreach ($tags as $tag_id) {
            $tag = new Tag((int)$tag_id);
            $string .= "<genre id=\"$tag_id\">\n\t<name><![CDATA[" . $tag->name . "]]></name>\n\t<albums>" . $tag->album . "</albums>\n\t<artists>" . $tag->artist . "</artists>\n\t<songs>" . $tag->song . "</songs>\n\t<videos>" . $tag->video . "</videos>\n\t<playlists>0</playlists>\n\t<live_streams>0</live_streams>\n</genre>\n";
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty xml document with the information
     * we want
     *
     * @param list<int|string> $artists Artist id's to include
     * @param string[] $include Array of other items to include.
     * @param User $user
     * @param string $auth
     * @param bool $full_xml whether to return a full XML document or just the node.
     * @return string
     */
    public static function artists(array $artists, array $include, User $user, string $auth, bool $full_xml = true): string
    {
        if ((count($artists) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $artists = array_splice($artists, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('artist', $user->id) . "</total_count>\n" : '';

        Rating::build_cache('artist', $artists);

        foreach ($artists as $artist_id) {
            $artist = new Artist((int)$artist_id);
            if ($artist->isNew()) {
                continue;
            }

            $rating      = new Rating($artist->id, 'artist');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($artist->id, 'artist');
            $tag_string  = self::genre_string($artist->get_tags());

            // Build the Art URL, include session
            $art_url = Art::url($artist->id, 'artist', $auth);

            // Handle includes
            $albums = (in_array("albums", $include))
                ? self::albums(self::getAlbumRepository()->getAlbumByArtist($artist->id), [], $user, $auth, false)
                : '';
            $songs = (in_array("songs", $include))
                ? self::songs(self::getSongRepository()->getByArtist($artist->id), $user, $auth, false)
                : '';

            $string .= "<artist id=\"" . $artist->id . "\">\n\t<name><![CDATA[" . $artist->get_fullname() . "]]></name>\n" . $tag_string . "\t<albums>" . $albums . "</albums>\n\t<albumcount>" . $artist->album_count . "</albumcount>\n\t<songs>" . $songs . "</songs>\n\t<songcount>" . $artist->song_count . "</songcount>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . (string) $rating->get_average_rating() . "</averagerating>\n\t<mbid><![CDATA[" . $artist->mbid . "]]></mbid>\n\t<summary><![CDATA[" . $artist->summary . "]]></summary>\n\t<time><![CDATA[" . $artist->time . "]]></time>\n\t<yearformed>" . (int) $artist->yearformed . "</yearformed>\n\t<placeformed><![CDATA[" . $artist->placeformed . "]]></placeformed>\n</artist>\n";
        } // end foreach artists

        return Xml_Data::output_xml($string, $full_xml);
    }

    /**
     * albums
     *
     * This echos out a standard albums XML document, it pays attention to the limit
     *
     * @param list<int|string> $albums Album id's to include
     * @param string[] $include Array of other items to include.
     * @param User $user
     * @param string $auth
     * @param bool $full_xml whether to return a full XML document or just the node.
     * @return string
     */
    public static function albums(array $albums, array $include, User $user, string $auth, bool $full_xml = true): string
    {
        if ((count($albums) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $albums = array_splice($albums, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('album', $user->id) . "</total_count>\n" : '';

        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');

        Rating::build_cache('album', $albums);

        foreach ($albums as $album_id) {
            $album = new Album((int)$album_id);
            if ($album->isNew()) {
                continue;
            }

            $rating      = new Rating($album->id, 'album');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($album->id, 'album');
            $year        = ($original_year && $album->original_year)
                ? $album->original_year
                : $album->year;

            // Build the Art URL, include session
            $art_url = Art::url($album->id, 'album', $auth);

            $string .= "<album id=\"" . $album->id . "\">\n\t<name><![CDATA[" . $album->get_fullname() . "]]></name>\n";

            if ($album->get_artist_fullname() != "") {
                $string .= "\t<artist id=\"$album->album_artist\"><![CDATA[" . $album->get_artist_fullname() . "]]></artist>\n";
            }

            // Handle includes
            $songs = (in_array("songs", $include))
                ? self::songs(self::getSongRepository()->getByAlbum($album->id), $user, $auth, false)
                : '';

            $string .= "\t<time>" . $album->time . "</time>\n\t<year>" . $year . "</year>\n\t<tracks>" . $songs . "</tracks>\n\t<songcount>" . $album->song_count . "</songcount>\n\t<diskcount>" . $album->disk_count . "</diskcount>\n\t<type>" . $album->release_type . "</type>\n" . self::genre_string($album->get_tags()) . "\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n\t<mbid><![CDATA[" . $album->mbid . "]]></mbid>\n</album>\n";
        } // end foreach

        return Xml_Data::output_xml($string, $full_xml);
    }

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty XML document
     *
     * @param list<int|string> $playlists Playlist id's to include
     * @param User $user
     * @param string $auth
     * @return string
     */
    public static function playlists(array $playlists, User $user, string $auth): string
    {
        if ((count($playlists) > self::$limit || self::$offset > 0) && self::$limit) {
            $playlists = array_slice($playlists, self::$offset, self::$limit);
        }

        $total_count = (AmpConfig::get('hide_search', false))
            ? Catalog::get_update_info('search', $user->id) + Catalog::get_update_info('playlist', $user->id)
            : Catalog::get_update_info('playlist', $user->id);
        $string = "<total_count>" . $total_count . "</total_count>\n";

        // Foreach the playlist ids
        foreach ($playlists as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist  = 1000000
             */
            if ((int)$playlist_id === 0) {
                $playlist = new Search((int) str_replace('smart_', '', (string) $playlist_id), 'song', $user);
                if ($playlist->isNew()) {
                    continue;
                }
                $object_type    = 'search';
                $playitem_total = $playlist->last_count;
            } else {
                $playlist = new Playlist((int)$playlist_id);
                if ($playlist->isNew()) {
                    continue;
                }
                $object_type    = 'playlist';
                $playitem_total = $playlist->get_media_count('song');
            }
            $art_url       = Art::url($playlist->id, $object_type, $auth);
            $playlist_name = $playlist->get_fullname();
            $playlist_user = $playlist->username;
            $playlist_type = $playlist->type;

            $rating      = new Rating($playlist->id, $object_type);
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($playlist->id, $object_type);

            // Build this element
            $string .= "<playlist id=\"" . $playlist_id . "\">\n\t<name><![CDATA[" . $playlist_name . "]]></name>\n\t<owner><![CDATA[" . $playlist_user . "]]></owner>\n\t<items>" . (int)$playitem_total . "</items>\n\t<type>" . $playlist_type . "</type>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . (string) $rating->get_average_rating() . "</averagerating>\n</playlist>\n";
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * shares
     *
     * This returns shares to the user, in a pretty xml document with the information
     *
     * @param list<int|string> $shares Share id's to include
     * @param User $user
     * @param bool $full_xml whether to return a full XML document or just the node.
     * @return string
     */
    public static function shares(array $shares, User $user, bool $full_xml = true): string
    {
        if ((count($shares) > self::$limit || self::$offset > 0) && self::$limit) {
            $shares = array_splice($shares, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('share', $user->id) . "</total_count>\n" : '';

        foreach ($shares as $share_id) {
            $share = new Share((int)$share_id);
            $string .= "<share id=\"$share_id\">\n\t<name><![CDATA[" . $share->getObjectName() . "]]></name>\n\t<user><![CDATA[" . $share->getUserName() . "]]></user>\n\t<allow_stream>" . $share->allow_stream . "</allow_stream>\n\t<allow_download>" . $share->allow_download . "</allow_download>\n\t<creation_date>" . $share->creation_date . "</creation_date>\n\t<lastvisit_date>" . $share->lastvisit_date . "</lastvisit_date>\n\t<object_type><![CDATA[" . $share->object_type . "]]></object_type>\n\t<object_id>" . $share->object_id . "</object_id>\n\t<expire_days>" . $share->expire_days . "</expire_days>\n\t<max_counter>" . $share->max_counter . "</max_counter>\n\t<counter>" . $share->counter . "</counter>\n\t<secret><![CDATA[" . $share->secret . "]]></secret>\n\t<public_url><![CDATA[" . $share->public_url . "]]></public_url>\n\t<description><![CDATA[" . $share->description . "]]></description>\n</share>\n";
        } // end foreach

        return Xml_Data::output_xml($string, $full_xml);
    }

    /**
     * bookmarks
     *
     * This returns bookmarks to the user, in a pretty xml document with the information
     *
     * @param list<int> $bookmarks Bookmark id's to include
     */
    public static function bookmarks(array $bookmarks): string
    {
        $bookmarkRepository = self::getBookmarkRepository();

        $string = "";
        foreach ($bookmarks as $bookmark_id) {
            $bookmark = $bookmarkRepository->findById($bookmark_id);
            if ($bookmark !== null) {
                $string .= "<bookmark id=\"$bookmark_id\">\n\t<user><![CDATA[" . $bookmark->getUserName() . "]]></user>\n\t<object_type><![CDATA[" . $bookmark->object_type . "]]></object_type>\n\t<object_id>" . $bookmark->object_id . "</object_id>\n\t<position>" . $bookmark->position . "</position>\n\t<client><![CDATA[" . $bookmark->comment . "]]></client>\n\t<creation_date>" . $bookmark->creation_date . "</creation_date>\n\t<update_date><![CDATA[" . $bookmark->update_date . "]]></update_date>\n</bookmark>\n";
            }
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * catalogs
     *
     * This returns catalogs to the user, in a pretty xml document with the information
     *
     * @param int[] $catalogs group of catalog id's
     * @param User $user
     * @return string
     */
    public static function catalogs(array $catalogs, User $user): string
    {
        if ((count($catalogs) > self::$limit || self::$offset > 0) && self::$limit) {
            $catalogs = array_splice($catalogs, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_update_info('catalog', $user->id) . "</total_count>\n";

        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }
            $string .= "<catalog id=\"$catalog_id\">\n\t<name><![CDATA[" . $catalog->name . "]]></name>\n\t<type><![CDATA[" . $catalog->catalog_type . "]]></type>\n\t<gather_types><![CDATA[" . $catalog->gather_types . "]]></gather_types>\n\t<enabled>" . $catalog->enabled . "</enabled>\n\t<last_add>" . $catalog->last_add . "</last_add>\n\t<last_clean>" . $catalog->last_clean . "</last_clean>\n\t<last_update>" . $catalog->last_update . "</last_update>\n\t<path><![CDATA[" . $catalog->get_f_info() . "]]></path>\n\t<rename_pattern><![CDATA[" . $catalog->rename_pattern . "]]></rename_pattern>\n\t<sort_pattern><![CDATA[" . $catalog->sort_pattern . "]]></sort_pattern>\n</catalog>\n";
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * podcasts
     *
     * This returns podcasts to the user, in a pretty xml document with the information
     *
     * @param list<int|string> $podcasts Podcast id's to include
     * @param User $user
     * @param string $auth
     * @param bool $episodes include the episodes of the podcast //optional
     * @return string
     */
    public static function podcasts(array $podcasts, User $user, string $auth, bool $episodes = false): string
    {
        if ((count($podcasts) > self::$limit || self::$offset > 0) && self::$limit) {
            $podcasts = array_splice($podcasts, self::$offset, self::$limit);
        }

        $podcastRepository = self::getPodcastRepository();

        $string = "<total_count>" . Catalog::get_update_info('podcast', $user->id) . "</total_count>\n";

        foreach ($podcasts as $podcast_id) {
            $podcast = $podcastRepository->findById((int)$podcast_id);
            if ($podcast === null) {
                continue;
            }

            $rating      = new Rating($podcast->getId(), 'podcast');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($podcast->getId(), 'podcast');
            $art_url     = Art::url($podcast->getId(), 'podcast', $auth);
            $string .= "<podcast id=\"$podcast_id\">\n\t<name><![CDATA[" . $podcast->get_fullname() . "]]></name>\n\t<description><![CDATA[" . $podcast->get_description() . "]]></description>\n\t<language><![CDATA[" . scrub_out($podcast->getLanguage()) . "]]></language>\n\t<copyright><![CDATA[" . scrub_out($podcast->getCopyright()) . "]]></copyright>\n\t<feed_url><![CDATA[" . $podcast->getFeedUrl() . "]]></feed_url>\n\t<generator><![CDATA[" . scrub_out($podcast->getGenerator()) . "]]></generator>\n\t<website><![CDATA[" . scrub_out($podcast->getWebsite()) . "]]></website>\n\t<build_date><![CDATA[" . $podcast->getLastBuildDate()->format(DATE_ATOM) . "]]></build_date>\n\t<sync_date><![CDATA[" . $podcast->getLastSyncDate()->format(DATE_ATOM) . "]]></sync_date>\n\t<public_url><![CDATA[" . $podcast->get_link() . "]]></public_url>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . (string) $rating->get_average_rating() . "</averagerating>\n";
            if ($episodes) {
                $results = $podcast->getEpisodeIds();
                if (!empty($results)) {
                    $string .= self::podcast_episodes($results, $user, $auth, false);
                }
            }
            $string .= "\t</podcast>\n";
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * podcast_episodes
     *
     * This returns podcasts to the user, in a pretty xml document with the information
     *
     * @param list<int|string> $podcast_episodes Podcast_Episode id's to include
     * @param User $user
     * @param string $auth
     * @param bool $full_xml whether to return a full XML document or just the node.
     * @return string
     */
    public static function podcast_episodes(array $podcast_episodes, User $user, string $auth, bool $full_xml = true): string
    {
        if ((count($podcast_episodes) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $podcast_episodes = array_splice($podcast_episodes, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('podcast_episode', $user->id) . "</total_count>\n" : '';

        foreach ($podcast_episodes as $episode_id) {
            $episode = new Podcast_Episode((int)$episode_id);
            if ($episode->isNew()) {
                continue;
            }

            $rating      = new Rating($episode->id, 'podcast_episode');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($episode->id, 'podcast_episode');
            $art_url     = Art::url($episode->podcast, 'podcast', $auth);
            $string .= "\t<podcast_episode id=\"$episode_id\">\n\t\t<title><![CDATA[" . $episode->get_fullname() . "]]></title>\n\t\t<name><![CDATA[" . $episode->get_fullname() . "]]></name>\n\t\t<description><![CDATA[" . $episode->get_description() . "]]></description>\n\t\t<category><![CDATA[" . $episode->getCategory() . "]]></category>\n\t\t<author><![CDATA[" . $episode->getAuthor() . "]]></author>\n\t\t<author_full><![CDATA[" . $episode->getAuthor() . "]]></author_full>\n\t\t<website><![CDATA[" . $episode->getWebsite() . "]]></website>\n\t\t<pubdate><![CDATA[" . $episode->getPubDate()->format(DATE_ATOM) . "]]></pubdate>\n\t\t<state><![CDATA[" . $episode->getState()->toDescription() . "]]></state>\n\t\t<filelength><![CDATA[" . $episode->get_f_time(true) . "]]></filelength>\n\t\t<filesize><![CDATA[" . $episode->getSizeFormatted() . "]]></filesize>\n\t\t<filename><![CDATA[" . $episode->getFileName() . "]]></filename>\n\t\t<mime><![CDATA[" . ((isset($episode->mime)) ? $episode->mime : '') . "]]></mime>\n\t\t<time>" . (int)$episode->time . "</time>\n\t\t<size>" . (int)$episode->size . "</size>\n\t\t<public_url><![CDATA[" . $episode->get_link() . "]]></public_url>\n\t\t<url><![CDATA[" . $episode->play_url('', 'api', false, $user->getId(), $user->streamtoken) . "]]></url>\n\t\t<catalog>" . $episode->catalog . "</catalog>\n\t\t<art><![CDATA[" . $art_url . "]]></art>\n\t\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t\t<preciserating>" . $user_rating . "</preciserating>\n\t\t<rating>" . $user_rating . "</rating>\n\t\t<averagerating>" . (string) $rating->get_average_rating() . "</averagerating>\n\t\t<playcount>" . $episode->total_count . "</playcount>\n\t\t<played>" . $episode->played . "</played>\n\t</podcast_episode>\n";
        } // end foreach

        return Xml_Data::output_xml($string, $full_xml);
    }

    /**
     * songs
     *
     * This returns an xml document from an array of song ids.
     * @param list<int|string> $songs
     * @param User $user
     * @param string $auth
     * @param bool $full_xml
     * @return string
     */
    public static function songs(array $songs, User $user, string $auth, bool $full_xml = true): string
    {
        if ((count($songs) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $songs = array_slice($songs, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('song', $user->id) . "</total_count>\n" : '';

        Song::build_cache($songs);
        Stream::set_session($auth);

        $playlist_track = 0;

        // Foreach the ids!
        foreach ($songs as $song_id) {
            $song = new Song((int)$song_id);

            // If the song id is invalid/null
            if ($song->isNew()) {
                continue;
            }

            $song->fill_ext_info();
            $tag_string    = self::genre_string(Tag::get_top_tags('song', $song->id));
            $rating        = new Rating($song->id, 'song');
            $user_rating   = $rating->get_user_rating($user->getId());
            $flag          = new Userflag($song->id, 'song');
            $show_song_art = AmpConfig::get('show_song_art', false);
            $has_art       = Art::has_db($song->id, 'song');
            $art_object    = ($show_song_art && $has_art) ? $song->id : $song->album;
            $art_type      = ($show_song_art && $has_art) ? 'song' : 'album';
            $art_url       = Art::url($art_object, $art_type, $auth);
            $songMime      = $song->mime;
            $songBitrate   = $song->bitrate;
            $play_url      = $song->play_url('', 'api', false, $user->id, $user->streamtoken);
            $license       = $song->getLicense();
            $licenseLink   = (string)($license?->getExternalLink());

            $playlist_track++;

            $string .= "<song id=\"" . $song->id . "\">\n\t<title><![CDATA[" . $song->get_fullname() . "]]></title>\n\t<name><![CDATA[" . $song->get_fullname() . "]]></name>\n"
                . "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_artist_fullname() . "]]></artist>\n"
                . "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_fullname() . "]]></album>\n";
            if ($song->get_album_artist_fullname() != "") {
                $string .= "\t<albumartist id=\"" . $song->albumartist . "\"><![CDATA[" . $song->get_album_artist_fullname() . "]]></albumartist>\n";
            }
            $string .= "\t<disk><![CDATA[" . $song->disk . "]]></disk>\n\t<track>" . $song->track . "</track>\n" . $tag_string . "\t<filename><![CDATA[" . $song->file . "]]></filename>\n\t<playlisttrack>" . $playlist_track . "</playlisttrack>\n\t<time>" . $song->time . "</time>\n\t<year>" . $song->year . "</year>\n\t<bitrate>" . $songBitrate . "</bitrate>\n\t<rate>" . $song->rate . "</rate>\n\t<mode><![CDATA[" . $song->mode . "]]></mode>\n\t<mime><![CDATA[" . $songMime . "]]></mime>\n\t<url><![CDATA[" . $play_url . "]]></url>\n\t<size>" . $song->size . "</size>\n\t<mbid><![CDATA[" . $song->mbid . "]]></mbid>\n\t<album_mbid><![CDATA[" . $song->get_album_mbid() . "]]></album_mbid>\n\t<artist_mbid><![CDATA[" . $song->get_artist_mbid() . "]]></artist_mbid>\n\t<albumartist_mbid><![CDATA[" . $song->get_album_mbid() . "]]></albumartist_mbid>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . (string) $rating->get_average_rating() . "</averagerating>\n\t<playcount>" . $song->total_count . "</playcount>\n\t<catalog>" . $song->getCatalogId() . "</catalog>\n\t<composer><![CDATA[" . $song->composer . "]]></composer>\n\t<channels>" . $song->channels . "</channels>\n\t<comment><![CDATA[" . $song->comment . "]]></comment>\n\t<license><![CDATA[" . $licenseLink . "]]></license>\n\t<publisher><![CDATA[" . $song->label . "]]></publisher>\n\t<language>" . $song->language . "</language>\n\t<lyrics><![CDATA[" . $song->lyrics . "]]></lyrics>\n\t<replaygain_album_gain>" . $song->replaygain_album_gain . "</replaygain_album_gain>\n\t<replaygain_album_peak>" . $song->replaygain_album_peak . "</replaygain_album_peak>\n\t<replaygain_track_gain>" . $song->replaygain_track_gain . "</replaygain_track_gain>\n\t<replaygain_track_peak>" . $song->replaygain_track_peak . "</replaygain_track_peak>\n\t<r128_album_gain>" . $song->r128_album_gain . "</r128_album_gain>\n\t<r128_track_gain>" . $song->r128_track_gain . "</r128_track_gain>\n";

            /** @var Metadata $metadata */
            foreach ($song->getMetadata() as $metadata) {
                $field = $metadata->getField();

                if ($field !== null) {
                    $meta_name = str_replace(
                        [' ', '(', ')', '/', '\\', '#'],
                        '_',
                        $field->getName()
                    );
                    $string .= "\t<" . $meta_name . "><![CDATA[" . $metadata->getData() . "]]></" . $meta_name . ">\n";
                }
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
     * @param list<int|string> $videos Video id's to include
     * @param User $user
     * @param string $auth
     * @param bool $full_xml whether to return a full XML document or just the node.
     * @return string
     */
    public static function videos(array $videos, User $user, string $auth, bool $full_xml = true): string
    {
        if ((count($videos) > self::$limit || self::$offset > 0) && self::$limit) {
            $videos = array_slice($videos, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('video', $user->id) . "</total_count>\n" : '';

        foreach ($videos as $video_id) {
            $video = new Video((int)$video_id);
            if ($video->isNew()) {
                continue;
            }
            $rating      = new Rating($video->id, 'video');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($video->id, 'video');
            $art_url     = Art::url($video->id, 'video', $auth);

            $string .= "<video id=\"" . $video->id . "\">\n\t<title><![CDATA[" . $video->title . "]]></title>\n\t<name><![CDATA[" . $video->title . "]]></name>\n\t<mime><![CDATA[" . $video->mime . "]]></mime>\n\t<resolution><![CDATA[" . $video->get_f_resolution() . "]]></resolution>\n\t<size>" . $video->size . "</size>\n" . self::genre_string($video->get_tags()) . "\t<time><![CDATA[" . $video->time . "]]></time>\n\t<url><![CDATA[" . $video->play_url('', 'api', false, $user->getId(), $user->streamtoken) . "]]></url>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . (string) $rating->get_average_rating() . "</averagerating>\n\t<playcount>" . $video->total_count . "</playcount>\n</video>\n";
        } // end foreach

        return Xml_Data::output_xml($string, $full_xml);
    }

    /**
     * democratic
     *
     * This handles creating an xml document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param list<array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track_id: int,
     *     track: int}> $object_ids Object IDs
     * @param User $user
     * @param string $auth
     * @return string
     */
    public static function democratic(array $object_ids, User $user, string $auth): string
    {
        $democratic = Democratic::get_current_playlist($user);
        $string     = '';

        foreach ($object_ids as $row_id => $data) {
            $className = ObjectTypeToClassNameMapper::map($data['object_type']->value);
            /** @var Song $song */
            $song = new $className($data['object_id']);
            if ($song->isNew()) {
                continue;
            }
            $song->fill_ext_info();

            // FIXME: This is duplicate code and so wrong, functions need to be improved
            $tag         = new Tag((int)($song->get_tags()[0]['id'] ?? 0));
            $tag_string  = self::genre_string($song->get_tags());
            $rating      = new Rating($song->id, 'song');
            $user_rating = $rating->get_user_rating($user->getId());
            $art_url     = Art::url($song->album, 'album', $auth);
            $songMime    = $song->mime;
            $play_url    = $song->play_url('', 'api', false, $user->id, $user->streamtoken);

            $string .= "<song id=\"" . $song->id . "\">\n\t<title><![CDATA[" . $song->get_fullname() . "]]></title>\n\t<name><![CDATA[" . $song->get_fullname() . "]]></name>\n"
                . "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_artist_fullname() . "]]></artist>\n"
                . "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_fullname() . "]]></album>\n"
                . "\t<genre id=\"" . ($tag->id ?? '') . "\"><![CDATA[" . ($tag->name ?? '') . "]]></genre>\n" . $tag_string . "\t<track>" . $song->track . "</track>\n\t<time><![CDATA[" . $song->time . "]]></time>\n\t<mime><![CDATA[" . $songMime . "]]></mime>\n\t<url><![CDATA[" . $play_url . "]]></url>\n\t<size>" . $song->size . "</size>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n<playcount>" . $song->total_count . "</playcount>\n\t<vote>" . $democratic->get_vote($row_id) . "</vote>\n</song>\n";
        } // end foreach

        return Xml_Data::output_xml($string);
    }

    /**
     * user
     *
     * This handles creating an xml document for a user
     */
    public static function user(User $user, bool $fullinfo): string
    {
        $string = "<user id=\"" . (string)$user->id . "\">\n\t<username><![CDATA[" . $user->username . "]]></username>\n";
        if ($fullinfo) {
            $string .= "\t<auth><![CDATA[" . $user->apikey . "]]></auth>\n\t<email><![CDATA[" . $user->email . "]]></email>\n\t<access>" . (int) $user->access . "</access>\n\t<fullname_public>" . (int) $user->fullname_public . "</fullname_public>\n\t<validation><![CDATA[" . $user->validation . "]]></validation>\n\t<disabled>" . (int) $user->disabled . "</disabled>\n";
        }
        $string .= "\t<create_date>" . (int) $user->create_date . "</create_date>\n\t<last_seen>" . (int) $user->last_seen . "</last_seen>\n\t<link><![CDATA[" . $user->get_link() . "]]></link>\n\t<website><![CDATA[" . $user->website . "]]></website>\n\t<state><![CDATA[" . $user->state . "]]></state>\n\t<city><![CDATA[" . $user->city . "]]></city>\n";
        if ($user->fullname_public || $fullinfo) {
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
     * @param list<int|string> $users User identifier list
     */
    public static function users(array $users): string
    {
        $string = "";
        foreach ($users as $user_id) {
            $user = new User((int)$user_id);
            if ($user->isNew() === false) {
                $string .= "<user id=\"" . (string)$user->id . "\">\n\t<username><![CDATA[" . $user->username . "]]></username>\n</user>\n";
            }
        }

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
        $string = "";
        /** @var Shoutbox $shout */
        foreach ($shouts as $shout) {
            $user = $shout->getUser();
            $string .= "\t<shout id=\"" . $shout->getId() . "\">\n\t\t<date>" . $shout->getDate()->getTimestamp() . "</date>\n\t\t<text><![CDATA[" . $shout->getText() . "]]></text>\n";
            if ($user !== null) {
                $string .= "\t\t<user id=\"" . $user->getId() . "\">\n\t\t\t<username><![CDATA[" . $user->getUsername() . "]]></username>\n\t\t</user>\n";
            }
            $string .= "\t</shout>\n";
        }

        return Xml_Data::output_xml($string);
    }

    /**
     * timeline
     *
     * This handles creating an xml document for an activity list
     *
     * @param int[] $activities Activity identifier list
     */
    public static function timeline(array $activities): string
    {
        $string = "";
        foreach ($activities as $activity_id) {
            $activity = new Useractivity($activity_id);
            $user     = new User($activity->user);
            $string .= "\t<activity id=\"" . $activity_id . "\">\n\t\t<date>" . $activity->activity_date . "</date>\n\t\t<object_type><![CDATA[" . $activity->object_type . "]]></object_type>\n\t\t<object_id>" . $activity->object_id . "</object_id>\n\t\t<action><![CDATA[" . $activity->action . "]]></action>\n";
            if ($user->isNew() === false) {
                $string .= "\t\t<user id=\"" . (string)$user->id . "\">\n\t\t\t<username><![CDATA[" . $user->username . "]]></username>\n\t\t</user>\n";
            }
            $string .= "\t</activity>\n";
        }

        return self::_header() . $string . self::_footer();
    }

    /**
     * deleted
     *
     * This takes an array of deleted objects and return XML based on the type of object
     * we want
     *
     * @param string $object_type ('song', 'podcast_episode', 'video')
     * @param list<array{
     *     id: int,
     *     addition_time: int,
     *     delete_time: int,
     *     title: string,
     *     file: string,
     *     catalog: int,
     *     total_count: int,
     *     total_skip: int,
     *     update_time?: int,
     *     album?: int,
     *     artist?: int,
     *     podcast?: int,
     * }> $objects deleted object list
     */
    public static function deleted(string $object_type, array $objects): string
    {
        if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }

        $string = '';
        // here is where we call the object type
        foreach ($objects as $row) {
            switch ($object_type) {
                case 'song':
                    if (isset($row['album']) && isset($row['artist']) && isset($row['update_time'])) {
                        // id, addition_time, delete_time, title, file, `catalog`, total_count, total_skip, update_time, album, artist
                        $string .= "<deleted_song id=\"" . $row['id'] . "\">\n\t<addition_time>" . $row['addition_time'] . "</addition_time>\n\t<delete_time>" . $row['delete_time'] . "</delete_time>\n\t<title><![CDATA[" . $row['title'] . "]]></title>\n\t<file><![CDATA[" . $row['file'] . "]]></file>\n\t<catalog>" . $row['catalog'] . "</catalog>\n\t<total_count>" . $row['total_count'] . "</total_count>\n\t<total_skip>" . $row['total_skip'] . "</total_skip>\n\t<update_time>" . $row['update_time'] . "</update_time>\n\t<album>" . $row['album'] . "</album>\n\t<artist>" . $row['artist'] . "</artist>\n</deleted_song>\n";
                    }
                    break;
                case 'podcast_episode':
                    if (isset($row['podcast'])) {
                        // id, addition_time, delete_time, title, file, `catalog`, total_count, total_skip, podcast
                        $string .= "\t<deleted_podcast_episode id=\"" . $row['id'] . "\">\n\t<addition_time>" . $row['addition_time'] . "</addition_time>\n\t<delete_time>" . $row['delete_time'] . "</delete_time>\n\t<title><![CDATA[" . $row['title'] . "]]></title>\n\t<file><![CDATA[" . $row['file'] . "]]></file>\n\t<catalog>" . $row['catalog'] . "</catalog>\n\t<total_count>" . $row['total_count'] . "</total_count>\n\t<total_skip>" . $row['total_skip'] . "</total_skip>\n\t<played>" . $row['podcast'] . "</played>\n\t</deleted_podcast_episode>\n";
                    }
                    break;
                case 'video':
                    // id, addition_time, delete_time, title, file, catalog, total_count, total_skip
                    $string .= "<deleted_video id=\"" . $row['id'] . "\">\n\t<addition_time>" . $row['addition_time'] . "</addition_time>\n\t<delete_time>" . $row['delete_time'] . "</delete_time>\n\t<title><![CDATA[" . $row['title'] . "]]></title>\n\t<file><![CDATA[" . $row['file'] . "]]></file>\n\t<catalog>" . $row['catalog'] . "</catalog>\n\t<total_count>" . $row['total_count'] . "</total_count>\n\t<total_skip>" . $row['total_skip'] . "</total_skip>\n</deleted_video>\n";
            }
        } // end foreach objects

        return Xml_Data::output_xml($string);
    }

    /**
     * _header
     *
     * this returns a standard header, there are a few types
     * so we allow them to pass a type if they want to
     */
    private static function _header(?string $title = null): string
    {
        switch (self::$type) {
            case 'xspf':
                $header = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<playlist version = \"1\" xmlns=\"http://xspf.org/ns/0/\">\n<title>" . ($title ?? T_("Ampache XSPF Playlist")) . "</title>\n<creator>" . scrub_out(AmpConfig::get('site_title')) . "</creator>\n<annotation>" . scrub_out(AmpConfig::get('site_title')) . "</annotation>\n<info>" . AmpConfig::get_web_path() . "</info>\n<trackList>\n";
                break;
            case 'itunes':
                $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<!-- XML Generated by Ampache v." . AmpConfig::get('version') . " -->\n";
                break;
            case 'rss':
                $header = "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset', 'UTF-8') . "\" ?>\n <!-- RSS Generated by Ampache v." . AmpConfig::get('version') . " on " . date("r", time()) . "-->\n<rss version=\"2.0\">\n<channel>\n";
                break;
            default:
                $header = "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset', 'UTF-8') . "\" ?>\n<root>\n";
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

    /**
     * @deprecated Inject by constructor
     */
    private static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getLicenseRepository(): LicenseRepositoryInterface
    {
        global $dic;

        return $dic->get(LicenseRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getBookmarkRepository(): BookmarkRepositoryInterface
    {
        global $dic;

        return $dic->get(BookmarkRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getLabelRepository(): LabelRepositoryInterface
    {
        global $dic;

        return $dic->get(LabelRepositoryInterface::class);
    }
}
