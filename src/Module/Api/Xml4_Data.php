<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\LibraryItemEnum;
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
 * Xml4_Data Class
 *
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls
 *
 */
class Xml4_Data
{
    private static ?int $count  = null;
    private static ?int $limit  = 5000;
    private static int $offset  = 0;

    /**
     * constructor
     *
     * We don't use this, as its really a static class
     */
    private function __construct() {}

    /**
     * albums
     *
     * This echos out a standard albums XML document, it pays attention to the limit
     *
     * @param array<int|string> $objects
     * @param string[] $include Array of other items to include
     * @param bool $full_xml whether to return a full XML document or just the node
     */
    public static function albums(array $objects, array $include, User $user, string $auth, bool $full_xml = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $full_xml);

        $string = ($full_xml) ? "<total_count>" . self::$count . "</total_count>\n" : '';

        Rating::build_cache('album', $objects);

        foreach ($objects as $album_id) {
            $album = new Album((int) $album_id);
            if ($album->isNew()) {
                continue;
            }

            $rating      = new Rating($album->id, 'album');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($album->id, 'album');

            // Build the Art URL, include session
            $art_url = Art::url($album->id, 'album', $auth);

            $string .= "<album id=\"" . $album->id . "\">\n\t<name><![CDATA[" . $album->get_fullname() . "]]></name>\n";

            if ($album->get_parent_fullname() != "") {
                $string .= "\t<artist id=\"$album->album_artist\"><![CDATA[" . $album->get_parent_fullname() . "]]></artist>\n";
            }
            // Handle includes
            if (in_array("songs", $include)) {
                $songs = self::songs(self::getAlbumRepository()->getSongs($album->id), $user, $auth, false);
            } else {
                $songs = $album->song_count;
            }

            $string .= "\t<time>" . $album->time . "</time>\n\t<year>" . $album->year . "</year>\n\t<tracks>" . $songs . "</tracks>\n\t<songcount>" . $album->song_count . "</songcount>\n\t<type>" . $album->release_type . "</type>\n\t<disk>" . $album->disk_count . "</disk>\n" . self::_tags_string($album->get_tags()) . "\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . ($rating->get_average_rating() ?? null) . "</averagerating>\n\t<mbid><![CDATA[" . $album->mbid . "]]></mbid>\n</album>\n";
        }

        return Api::output_xml($string, $full_xml);
    }

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty xml document with the information
     * we want
     *
     * @param array<int|string> $objects
     * @param string[] $include Array of other items to include
     * @param bool $full_xml whether to return a full XML document or just the node
     */
    public static function artists(array $objects, array $include, User $user, string $auth, bool $full_xml = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $full_xml);

        $string = ($full_xml) ? "<total_count>" . self::$count . "</total_count>\n" : '';

        Rating::build_cache('artist', $objects);

        foreach ($objects as $artist_id) {
            $artist = new Artist((int) $artist_id);
            if ($artist->isNew()) {
                continue;
            }

            $rating      = new Rating($artist->id, 'artist');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($artist->id, 'artist');
            $tag_string  = self::_tags_string($artist->get_tags());

            // Build the Art URL, include session
            $art_url = Art::url($artist->id, 'artist', $auth);

            // Handle includes
            if (in_array("albums", $include)) {
                $albums = self::albums(self::getAlbumRepository()->getAlbumByArtist($artist->id), [], $user, $auth, false);
            } else {
                $albums = $artist->album_count;
            }
            if (in_array("songs", $include)) {
                $songs = self::songs(self::getSongRepository()->getByArtist($artist->id), $user, $auth, false);
            } else {
                $songs = $artist->song_count;
            }

            $string .= "<artist id=\"" . $artist->id . "\">\n\t<name><![CDATA[" . $artist->get_fullname() . "]]></name>\n" . $tag_string . "\t<albums>" . $albums . "</albums>\n\t<albumcount>" . $artist->album_count . "</albumcount>\n\t<songs>" . $songs . "</songs>\n\t<songcount>" . $artist->song_count . "</songcount>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->id) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . ($rating->get_average_rating() ?? null) . "</averagerating>\n\t<mbid><![CDATA[" . $artist->mbid . "]]></mbid>\n\t<summary><![CDATA[" . $artist->summary . "]]></summary>\n\t<time><![CDATA[" . $artist->time . "]]></time>\n\t<yearformed>" . $artist->yearformed . "</yearformed>\n\t<placeformed><![CDATA[" . $artist->placeformed . "]]></placeformed>\n</artist>\n";
        }

        return Api::output_xml($string, $full_xml);
    }

    /**
     * catalogs
     *
     * This returns catalogs to the user, in a pretty xml document with the information
     *
     * @param int[] $objects group of catalog id's
     */
    public static function catalogs(array $objects): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $string = "<total_count>" . self::$count . "</total_count>\n";

        foreach ($objects as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }
            $string .= "<catalog id=\"$catalog_id\">\n\t<name><![CDATA[" . $catalog->name . "]]></name>\n\t<type><![CDATA[" . $catalog->catalog_type . "]]></type>\n\t<gather_types><![CDATA[" . $catalog->gather_types . "]]></gather_types>\n\t<enabled>" . $catalog->enabled . "</enabled>\n\t<last_add><![CDATA[" . $catalog->get_f_add() . "]]></last_add>\n\t<last_clean><![CDATA[" . $catalog->get_f_clean() . "]]></last_clean>\n\t<last_update><![CDATA[" . $catalog->get_f_update() . "]]></last_update>\n\t<path><![CDATA[" . $catalog->get_f_info() . "]]></path>\n\t<rename_pattern><![CDATA[" . $catalog->rename_pattern . "]]></rename_pattern>\n\t<sort_pattern><![CDATA[" . $catalog->sort_pattern . "]]></sort_pattern>\n</catalog>\n";
        }

        return Api::output_xml($string);
    }

    /**
     * democratic
     *
     * This handles creating an xml document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track_id: int,
     *     track: int
     * }> $object_ids Object IDs
     */
    public static function democratic(array $object_ids, User $user, string $auth): string
    {
        $democratic = Democratic::get_current_playlist($user);
        $string     = '';

        foreach ($object_ids as $data) {
            $className = ObjectTypeToClassNameMapper::map($data['object_type']->value);
            /** @var Song $song */
            $song = new $className($data['object_id']);
            if ($song->isNew()) {
                continue;
            }
            $song->fill_ext_info();

            // FIXME: This is duplicate code and so wrong, functions need to be improved
            $tag        = new Tag((int) ($song->get_tags()[0]['id'] ?? 0));
            $tag_string = self::_tags_string($song->get_tags());
            $rating     = new Rating($song->id, 'song');
            $art_url    = Art::url($song->album, 'album', $auth);
            $songMime   = $song->mime;
            $play_url   = $song->play_url('', 'api', false, $user->id, $user->streamtoken);

            $string .= "<song id=\"" . $song->id . "\">\n\t<title><![CDATA[" . $song->title . "]]></title>\n\t<name><![CDATA[" . $song->title . "]]></name>\n"
                . "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_parent_fullname() . "]]></artist>\n"
                . "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_fullname() . "]]></album>\n"
                . "\t<genre id=\"" . ($tag->id ?: '') . "\"><![CDATA[" . ($tag->name ?: '') . "]]></genre>\n" . $tag_string . "\t<track>" . $song->track . "</track>\n\t<time><![CDATA[" . $song->time . "]]></time>\n\t<mime><![CDATA[" . $songMime . "]]></mime>\n\t<url><![CDATA[" . $play_url . "]]></url>\n\t<size>" . $song->size . "</size>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<preciserating>" . ($rating->get_user_rating($user->id) ?? null) . "</preciserating>\n\t<rating>" . ($rating->get_user_rating($user->id) ?? null) . "</rating>\n\t<averagerating>" . ($rating->get_average_rating() ?? null) . "</averagerating>\n\t<vote>" . $democratic->get_vote($data['track_id']) . "</vote>\n</song>\n";
        }

        return Api::output_xml($string);
    }

    /**
     * error
     *
     * This generates a standard XML Error message
     */
    public static function error(string $code, string $message): string
    {
        $xml_string = "\t<error code=\"$code\"><![CDATA[" . $message . "]]></error>";

        return Api::output_xml($xml_string);
    }

    /**
     * indexes
     *
     * This takes an array of object_ids and return XML based on the type of object
     * we want
     *
     * @param array<int|string> $objects Array of object_ids (Mixed string|int)
     * @param string $object_type 'artist'|'album'|'song'|'playlist'|'share'|'podcast'|'podcast_episode'|'video'
     * @param bool $full_xml whether to return a full XML document or just the node
     * @param bool $include include episodes from podcasts or tracks in a playlist
     */
    public static function indexes(array $objects, string $object_type, User $user, string $auth, bool $full_xml = true, bool $include = false): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $full_xml);

        $string = ($full_xml) ? "<total_count>" . self::$count . "</total_count>\n" : '';

        // here is where we call the object type
        switch ($object_type) {
            case 'artist':
                foreach ($objects as $object_id) {
                    if ($include) {
                        $string .= self::artists([(int) $object_id], ['songs', 'albums'], $user, $auth, false);
                    } else {
                        $artist = new Artist((int) $object_id);
                        if ($artist->isNew()) {
                            break;
                        }
                        $albums = self::getAlbumRepository()->getAlbumByArtist((int) $object_id);
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
                        $string .= self::albums([(int) $object_id], ['songs'], $user, $auth, false);
                    } else {
                        $album = new Album((int) $object_id);
                        $string .= "<$object_type id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $album->get_fullname() . "]]></name>\n"
                            . "\t\t<artist id=\"" . $album->album_artist . "\"><![CDATA[" . $album->get_parent_fullname() . "]]></artist>\n</$object_type>\n";
                    }
                }
                break;
            case 'song':
                foreach ($objects as $object_id) {
                    $song = new Song((int) $object_id);
                    $song->fill_ext_info();
                    $string .= "<$object_type id=\"" . $object_id . "\">\n\t<title><![CDATA[" . $song->title . "]]></title>\n\t<name><![CDATA[" . $song->get_fullname() . "]]></name>\n"
                        . "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_parent_fullname() . "]]></artist>\n"
                        . "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_fullname() . "]]></album>\n"
                        . "\t<albumartist id=\"" . $song->albumartist . "\"><![CDATA[" . $song->get_album_artist_fullname() . "]]></albumartist>\n\t<disk><![CDATA[" . $song->disk . "]]></disk>\n\t<track>" . $song->track . "</track>\n</$object_type>\n";
                }
                break;
            case 'playlist':
                foreach ($objects as $object_id) {
                    if ((int) $object_id === 0) {
                        $playlist = new Search((int) str_replace('smart_', '', (string) $object_id), 'song', $user);
                        if ($playlist->isNew()) {
                            break;
                        }

                        $playlist_user = ($playlist->type !== 'public')
                            ? $playlist->username
                            : $playlist->type;
                        $playitem_total = $playlist->last_count;
                    } else {
                        $playlist = new Playlist((int) $object_id);
                        if ($playlist->isNew()) {
                            break;
                        }

                        $playlist_user  = $playlist->username;
                        $playitem_total = $playlist->get_media_count('song');
                    }
                    $playlist_name = $playlist->get_fullname();
                    $songs         = ($include) ? $playlist->get_items() : [];
                    $string .= "<$object_type id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $playlist_name . "]]></name>\n\t<items>" . (int) $playitem_total . "</items>\n\t<owner><![CDATA[" . $playlist_user . "]]></owner>\n\t<type><![CDATA[" . $playlist->type . "]]></type>\n";
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
                    $podcast = self::getPodcastRepository()->findById((int) $object_id);
                    if ($podcast !== null) {
                        $string .= "<podcast id=\"$object_id\">\n\t<name><![CDATA[" . $podcast->get_fullname() . "]]></name>\n\t<description><![CDATA[" . $podcast->get_description() . "]]></description>\n\t<language><![CDATA[" . scrub_out($podcast->getLanguage()) . "]]></language>\n\t<copyright><![CDATA[" . scrub_out($podcast->getCopyright()) . "]]></copyright>\n\t<feed_url><![CDATA[" . $podcast->getFeedUrl() . "]]></feed_url>\n\t<generator><![CDATA[" . scrub_out($podcast->getGenerator()) . "]]></generator>\n\t<website><![CDATA[" . scrub_out($podcast->getWebsite()) . "]]></website>\n\t<build_date><![CDATA[" . $podcast->getLastBuildDate()->format(DATE_ATOM) . "]]></build_date>\n\t<sync_date><![CDATA[" . $podcast->getLastSyncDate()->format(DATE_ATOM) . "]]></sync_date>\n\t<public_url><![CDATA[" . $podcast->get_link() . "]]></public_url>\n";
                        if ($include) {
                            $episodes = $podcast->getEpisodeIds();
                            foreach ($episodes as $episode_id) {
                                $string .= self::podcast_episodes([$episode_id], $user, $auth, false);
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
        }

        return Api::output_xml($string, $full_xml);
    }

    /**
     * licenses
     *
     * This returns licenses to the user, in a pretty xml document with the information
     *
     * @param array<int|string> $objects
     */
    public static function licenses(array $objects): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $string = "<total_count>" . self::$count . "</total_count>\n";

        $licenseRepository = self::getLicenseRepository();

        foreach ($objects as $license_id) {
            $license = $licenseRepository->findById((int) $license_id);
            if ($license !== null) {
                $string .= "<license id=\"$license_id\">\n\t<name><![CDATA[" . $license->getName() . "]]></name>\n\t<description><![CDATA[" . $license->getDescription() . "]]></description>\n\t<external_link><![CDATA[" . $license->getExternalLink() . "]]></external_link>\n</license>\n";
            }
        }

        return Api::output_xml($string);
    }

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty XML document
     *
     * @param array<int|string> $objects Playlist id's to include
     */
    public static function playlists(array $objects, User $user, string $auth): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $string = "<total_count>" . self::$count . "</total_count>\n";

        // Foreach the playlist ids
        foreach ($objects as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist = 1000000
             */
            if ((int) $playlist_id === 0) {
                $playlist = new Search((int) str_replace('smart_', '', (string) $playlist_id), 'song', $user);
                if ($playlist->isNew()) {
                    continue;
                }
                $object_type    = 'search';
                $playitem_total = $playlist->last_count;
            } else {
                $playlist = new Playlist((int) $playlist_id);
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
            $string .= "<playlist id=\"" . $playlist_id . "\">\n\t<name><![CDATA[" . $playlist_name . "]]></name>\n\t<owner><![CDATA[" . $playlist_user . "]]></owner>\n\t<items>" . (int) $playitem_total . "</items>\n\t<type>" . $playlist_type . "</type>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . ($rating->get_average_rating() ?? null) . "</averagerating>\n</playlist>\n";
        }

        return Api::output_xml($string);
    }

    /**
     * podcast_episodes
     *
     * This returns podcasts to the user, in a pretty xml document with the information
     *
     * @param array<int|string> $objects Podcast_Episode id's to include
     * @param bool $full_xml whether to return a full XML document or just the node
     */
    public static function podcast_episodes(array $objects, User $user, string $auth, bool $full_xml = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $full_xml);

        $string = ($full_xml) ? "<total_count>" . self::$count . "</total_count>\n" : '';

        foreach ($objects as $episode_id) {
            $episode = new Podcast_Episode((int) $episode_id);
            if ($episode->isNew()) {
                continue;
            }

            $rating      = new Rating($episode->id, 'podcast_episode');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($episode->id, 'podcast_episode');
            $art_url     = Art::url($episode->podcast, 'podcast', $auth);
            $string .= "\t<podcast_episode id=\"$episode_id\">\n\t\t<title><![CDATA[" . $episode->get_fullname() . "]]></title>\n\t\t<name><![CDATA[" . $episode->get_fullname() . "]]></name>\n\t\t<description><![CDATA[" . $episode->get_description() . "]]></description>\n\t\t<category><![CDATA[" . $episode->getCategory() . "]]></category>\n\t\t<author><![CDATA[" . $episode->getAuthor() . "]]></author>\n\t\t<author_full><![CDATA[" . $episode->getAuthor() . "]]></author_full>\n\t\t<website><![CDATA[" . $episode->getWebsite() . "]]></website>\n\t\t<pubdate><![CDATA[" . $episode->getPubDate()->format(DATE_ATOM) . "]]></pubdate>\n\t\t<state><![CDATA[" . $episode->getState()->toDescription() . "]]></state>\n\t\t<filelength><![CDATA[" . $episode->get_f_time(true) . "]]></filelength>\n\t\t<filesize><![CDATA[" . $episode->getSizeFormatted() . "]]></filesize>\n\t\t<filename><![CDATA[" . $episode->getFileName() . "]]></filename>\n\t\t<mime><![CDATA[" . ((isset($episode->mime)) ? $episode->mime : '') . "]]></mime>\n\t\t<public_url><![CDATA[" . $episode->get_link() . "]]></public_url>\n\t\t<url><![CDATA[" . $episode->play_url('', 'api', false, $user->getId(), $user->streamtoken) . "]]></url>\n\t\t<catalog>" . $episode->catalog . "</catalog>\n\t\t<art><![CDATA[" . $art_url . "]]></art>\n\t\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t\t<preciserating>" . $user_rating . "</preciserating>\n\t\t<rating>" . $user_rating . "</rating>\n\t\t<averagerating>" . ($rating->get_average_rating() ?? null) . "</averagerating>\n\t\t<played>" . $episode->played . "</played>\n\t</podcast_episode>\n";
        }

        return Api::output_xml($string, $full_xml);
    }

    /**
     * podcasts
     *
     * This returns podcasts to the user, in a pretty xml document with the information
     *
     * @param array<int|string> $objects
     * @param bool $episodes include the episodes of the podcast //optional
     * @param bool $full_xml whether to return a full XML document or just the node
     */
    public static function podcasts(array $objects, User $user, string $auth, bool $episodes = false, bool $full_xml = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $full_xml);

        $string = ($full_xml) ? "<total_count>" . self::$count . "</total_count>\n" : '';

        $podcastRepository = self::getPodcastRepository();

        foreach ($objects as $podcast_id) {
            $podcast = $podcastRepository->findById((int) $podcast_id);
            if ($podcast === null) {
                continue;
            }
            $rating      = new Rating($podcast->getId(), 'podcast');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($podcast->getId(), 'podcast');
            $art_url     = Art::url($podcast->getId(), 'podcast', $auth);
            $string .= "<podcast id=\"$podcast_id\">\n\t<name><![CDATA[" . $podcast->get_fullname() . "]]></name>\n\t<description><![CDATA[" . $podcast->get_description() . "]]></description>\n\t<language><![CDATA[" . scrub_out($podcast->getLanguage()) . "]]></language>\n\t<copyright><![CDATA[" . scrub_out($podcast->getCopyright()) . "]]></copyright>\n\t<feed_url><![CDATA[" . $podcast->getFeedUrl() . "]]></feed_url>\n\t<generator><![CDATA[" . scrub_out($podcast->getGenerator()) . "]]></generator>\n\t<website><![CDATA[" . scrub_out($podcast->getWebsite()) . "]]></website>\n\t<build_date><![CDATA[" . $podcast->getLastBuildDate()->format(DATE_ATOM) . "]]></build_date>\n\t<sync_date><![CDATA[" . $podcast->getLastSyncDate()->format(DATE_ATOM) . "]]></sync_date>\n\t<public_url><![CDATA[" . $podcast->get_link() . "]]></public_url>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . ($rating->get_average_rating() ?? null) . "</averagerating>\n";
            if ($episodes) {
                $results = $podcast->getEpisodeIds();
                if (!empty($results)) {
                    $string .= self::podcast_episodes($results, $user, $auth, false);
                }
            }
            $string .= "\t</podcast>\n";
        }

        return Api::output_xml($string, $full_xml);
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

        self::$limit = (strtolower((string) $limit) == "none") ? null : (int) $limit;

        return true;
    }

    /**
     * set_offset
     *
     * This takes an int and changes the offset
     *
     * @param int|string $offset Change the starting position of your results. (e.g 5001 when selecting in groups of 5000)
     */
    public static function set_offset(int|string $offset): void
    {
        self::$offset = (int) $offset;
    }

    /**
     * shares
     *
     * This returns shares to the user, in a pretty xml document with the information
     *
     * @param array<int|string> $objects
     * @param bool $full_xml whether to return a full XML document or just the node, bool $full_xml = true
     */
    public static function shares(array $objects, User $user, bool $full_xml = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $full_xml);

        $string = ($full_xml) ? "<total_count>" . self::$count . "</total_count>\n" : '';

        foreach ($objects as $share_id) {
            $share = new Share((int) $share_id);
            if ($share->isNew() || !$share->isAccessible($user)) {
                continue;
            }

            $string .= "<share id=\"$share_id\">\n\t<name><![CDATA[" . $share->getObjectName() . "]]></name>\n\t<user><![CDATA[" . $share->getUserName() . "]]></user>\n\t<allow_stream>" . (int) $share->allow_stream . "</allow_stream>\n\t<allow_download>" . (int) $share->allow_download . "</allow_download>\n\t<creation_date><![CDATA[" . $share->creation_date . "]]></creation_date>\n\t<lastvisit_date><![CDATA[" . $share->lastvisit_date . "]]></lastvisit_date>\n\t<object_type><![CDATA[" . $share->object_type . "]]></object_type>\n\t<object_id>" . $share->object_id . "</object_id>\n\t<expire_days>" . $share->expire_days . "</expire_days>\n\t<max_counter>" . $share->max_counter . "</max_counter>\n\t<counter>" . $share->counter . "</counter>\n\t<secret><![CDATA[" . $share->secret . "]]></secret>\n\t<public_url><![CDATA[" . $share->public_url . "]]></public_url>\n\t<description><![CDATA[" . $share->description . "]]></description>\n</share>\n";
        }

        return Api::output_xml($string, $full_xml);
    }

    /**
     * shouts
     *
     * This handles creating a xml document for a shout list
     *
     * @param Traversable<Shoutbox> $objects Shout identifier list
     */
    public static function shouts(Traversable $objects): string
    {
        $string = "<shouts>\n";
        /** @var Shoutbox $shout */
        foreach ($objects as $shout) {
            $user = $shout->getUser();
            $string .= "\t<shout id=\"" . $shout->getId() . "\">\n\t\t<date>" . $shout->getDate()->getTimestamp() . "</date>\n\t\t<text><![CDATA[" . $shout->getText() . "]]></text>\n";
            if ($user !== null) {
                $string .= "\t\t<user id=\"" . $user->getId() . "\">\n\t\t\t<username><![CDATA[" . $user->getUsername() . "]]></username>\n\t\t</user>\n";
            }
            $string .= "\t</shout>\n";
        }
        $string .= "</shouts>\n";

        return Api::output_xml($string);
    }

    /**
     * songs
     *
     * This returns an xml document from an array of song ids. (Spiffy isn't it!)
     * @param array<int|string> $objects
     */
    public static function songs(array $objects, User $user, string $auth, bool $full_xml = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $full_xml);

        $string = ($full_xml) ? "<total_count>" . self::$count . "</total_count>\n" : '';

        Song::build_cache($objects);
        Stream::set_session($auth);

        $playlist_track = 0;

        // Foreach the ids!
        foreach ($objects as $song_id) {
            $song = new Song((int) $song_id);

            // If the song id is invalid/null
            if ($song->isNew()) {
                continue;
            }

            $song->fill_ext_info();
            $tag_string  = self::_tags_string(Tag::get_top_tags('song', $song->id));
            $rating      = new Rating($song->id, 'song');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($song->id, 'song');
            $art_url     = Art::url($song->album, 'album', $auth);
            $songMime    = $song->mime;
            $songBitrate = $song->bitrate;
            $play_url    = $song->play_url('', 'api', false, $user->id, $user->streamtoken);
            $license     = $song->getLicense();
            $licenseLink = (string) ($license?->getExternalLink());

            $playlist_track++;

            $string .= "<song id=\"" . $song->id . "\">\n\t<title><![CDATA[" . $song->title . "]]></title>\n\t<name><![CDATA[" . $song->title . "]]></name>\n"
                . "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_parent_fullname() . "]]></artist>\n"
                . "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_fullname() . "]]></album>\n"
                . "\t<albumartist id=\"" . $song->albumartist . "\"><![CDATA[" . $song->get_album_artist_fullname() . "]]></albumartist>\n\t<disk><![CDATA[" . $song->disk . "]]></disk>\n\t<track>" . $song->track . "</track>\n" . $tag_string . "\t<filename><![CDATA[" . $song->file . "]]></filename>\n\t<playlisttrack>" . $playlist_track . "</playlisttrack>\n\t<time>" . $song->time . "</time>\n\t<year>" . $song->year . "</year>\n\t<bitrate>" . $songBitrate . "</bitrate>\n\t<rate>" . $song->rate . "</rate>\n\t<mode><![CDATA[" . $song->mode . "]]></mode>\n\t<mime><![CDATA[" . $songMime . "]]></mime>\n\t<url><![CDATA[" . $play_url . "]]></url>\n\t<size>" . $song->size . "</size>\n\t<mbid><![CDATA[" . $song->mbid . "]]></mbid>\n\t<album_mbid><![CDATA[" . $song->get_album_mbid() . "]]></album_mbid>\n\t<artist_mbid><![CDATA[" . $song->get_artist_mbid() . "]]></artist_mbid>\n\t<albumartist_mbid><![CDATA[" . $song->get_album_mbid() . "]]></albumartist_mbid>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . ($rating->get_average_rating() ?? null) . "</averagerating>\n\t<playcount>" . $song->played . "</playcount>\n\t<catalog>" . $song->getCatalogId() . "</catalog>\n\t<composer><![CDATA[" . $song->composer . "]]></composer>\n\t<channels>" . $song->channels . "</channels>\n\t<comment><![CDATA[" . $song->comment . "]]></comment>\n\t<license><![CDATA[" . $licenseLink . "]]></license>\n\t<publisher><![CDATA[" . $song->label . "]]></publisher>\n\t<language>" . $song->language . "</language>\n\t<replaygain_album_gain>" . $song->replaygain_album_gain . "</replaygain_album_gain>\n\t<replaygain_album_peak>" . $song->replaygain_album_peak . "</replaygain_album_peak>\n\t<replaygain_track_gain>" . $song->replaygain_track_gain . "</replaygain_track_gain>\n\t<replaygain_track_peak>" . $song->replaygain_track_peak . "</replaygain_track_peak>\n";

            /** @var Metadata $metadata */
            foreach ($song->getMetadata() as $metadata) {
                $field = $metadata->getField();

                if ($field !== null) {
                    $meta_name = str_replace([' ', '(', ')', '/', '\\', '#'], '_', $field->getName());
                    $string .= "\t<" . $meta_name . "><![CDATA[" . $metadata->getData() . "]]></" . $meta_name . ">\n";
                }
            }

            foreach ($song->get_tags() as $tag) {
                $string .= "\t<genre><![CDATA[" . $tag['name'] . "]]></genre>\n";
            }

            $string .= "</song>\n";
        }

        return Api::output_xml($string, $full_xml);
    }

    /**
     * success
     *
     * This generates a standard XML Success message
     *
     * @param string $string success message
     */
    public static function success(string $string): string
    {
        $xml_string = "\t<success code=\"1\"><![CDATA[" . $string . "]]></success>";

        return Api::output_xml($xml_string);
    }

    /**
     * tags
     *
     * This returns tags to the user, in a pretty xml document with the information
     *
     * @param array<int|string> $objects
     */
    public static function tags(array $objects): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $string = "<total_count>" . self::$count . "</total_count>\n";

        foreach ($objects as $tag_id) {
            $tag = new Tag((int) $tag_id);
            $string .= "<tag id=\"$tag_id\">\n\t<name><![CDATA[" . $tag->name . "]]></name>\n\t<albums>" . $tag->album . "</albums>\n\t<artists>" . $tag->artist . "</artists>\n\t<songs>" . $tag->song . "</songs>\n\t<videos>" . $tag->video . "</videos>\n\t<playlists>0</playlists>\n\t<stream>0</stream>\n</tag>\n";
        }

        return Api::output_xml($string);
    }

    /**
     * timeline
     *
     * This handles creating an xml document for an activity list
     *
     * @param int[] $objects    Activity identifier list
     */
    public static function timeline(array $objects): string
    {
        $string = "<timeline>\n";
        foreach ($objects as $activity_id) {
            $activity = new Useractivity($activity_id);
            $user     = new User($activity->user);
            $string .= "\t<activity id=\"" . $activity_id . "\">\n\t\t<date>" . $activity->activity_date . "</date>\n\t\t<object_type><![CDATA[" . $activity->object_type . "]]></object_type>\n\t\t<object_id>" . $activity->object_id . "</object_id>\n\t\t<action><![CDATA[" . $activity->action . "]]></action>\n";
            if ($user->isNew() === false) {
                $string .= "\t\t<user id=\"" . $user->id . "\">\n\t\t\t<username><![CDATA[" . $user->username . "]]></username>\n\t\t</user>\n";
            }
            $string .= "\t</activity>\n";
        }
        $string .= "</timeline>";

        return Api::header() . $string . Api::footer();
    }

    /**
     * user
     *
     * This handles creating an xml document for a user
     */
    public static function user(User $user, bool $fullinfo): string
    {
        $string = "<user id=\"" . $user->id . "\">\n\t<username><![CDATA[" . $user->username . "]]></username>\n";
        if ($fullinfo) {
            $string .= "\t<auth><![CDATA[" . $user->apikey . "]]></auth>\n\t<email><![CDATA[" . $user->email . "]]></email>\n\t<access><![CDATA[" . $user->access . "]]></access>\n\t<fullname_public><![CDATA[" . $user->fullname_public . "]]></fullname_public>\n\t<validation><![CDATA[" . $user->validation . "]]></validation>\n\t<disabled><![CDATA[" . $user->disabled . "]]></disabled>\n";
        }
        $string .= "\t<create_date><![CDATA[" . $user->create_date . "]]></create_date>\n\t<last_seen><![CDATA[" . $user->last_seen . "]]></last_seen>\n\t<link><![CDATA[" . $user->link . "]]></link>\n\t<website><![CDATA[" . $user->website . "]]></website>\n\t<state><![CDATA[" . $user->state . "]]></state>\n\t<city><![CDATA[" . $user->city . "]]></city>\n";
        if ($user->fullname_public || $fullinfo) {
            $string .= "\t<fullname><![CDATA[" . $user->fullname . "]]></fullname>\n";
        }
        $string .= "</user>\n";

        return Api::output_xml($string);
    }

    /**
     * users
     *
     * This handles creating an xml document for a user list
     *
     * @param array<int|string> $objects    User identifier list
     */
    public static function users(array $objects): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $string = "<users>\n";
        foreach ($objects as $user_id) {
            $user = new User((int) $user_id);
            if ($user->isNew() === false) {
                $string .= "<user id=\"" . $user->id . "\">\n\t<username><![CDATA[" . $user->username . "]]></username>\n</user>\n";
            }
        }
        $string .= "</users>\n";

        return Api::output_xml($string);
    }

    /**
     * videos
     *
     * This builds the xml document for displaying video objects
     *
     * @param array<int|string> $objects
     * @param bool $full_xml whether to return a full XML document or just the node
     */
    public static function videos(array $objects, User $user, string $auth, bool $full_xml = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $full_xml);

        $string = ($full_xml) ? "<total_count>" . self::$count . "</total_count>\n" : '';

        foreach ($objects as $video_id) {
            $video = new Video((int) $video_id);
            if ($video->isNew()) {
                continue;
            }
            $rating      = new Rating($video->id, 'video');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($video->id, 'video');
            $art_url     = Art::url($video->id, 'video', $auth);

            $string .= "<video id=\"" . $video->id . "\">\n\t<title><![CDATA[" . $video->title . "]]></title>\n\t<name><![CDATA[" . $video->title . "]]></name>\n\t<mime><![CDATA[" . $video->mime . "]]></mime>\n\t<resolution><![CDATA[" . $video->get_f_resolution() . "]]></resolution>\n\t<size>" . $video->size . "</size>\n" . self::_tags_string($video->get_tags()) . "\t<time><![CDATA[" . $video->time . "]]></time>\n\t<url><![CDATA[" . $video->play_url('', 'api', false, $user->getId(), $user->streamtoken) . "]]></url>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<preciserating>" . $user_rating . "</preciserating>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . ($rating->get_average_rating() ?? null) . "</averagerating>\n</video>\n";
        }

        return Api::output_xml($string, $full_xml);
    }

    /**
     * tags_string
     *
     * This returns the formatted 'tags' string for an xml document
     * @param array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags
     */
    private static function _tags_string(array $tags): string
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
    private static function getLicenseRepository(): LicenseRepositoryInterface
    {
        global $dic;

        return $dic->get(LicenseRepositoryInterface::class);
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
     * @deprecated
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
