<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
 */

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Playlist\Search\AlbumDiskSearch;
use Ampache\Module\Playlist\Search\AlbumSearch;
use Ampache\Module\Playlist\Search\ArtistSearch;
use Ampache\Module\Playlist\Search\LabelSearch;
use Ampache\Module\Playlist\Search\PlaylistSearch;
use Ampache\Module\Playlist\Search\PodcastEpisodeSearch;
use Ampache\Module\Playlist\Search\PodcastSearch;
use Ampache\Module\Playlist\Search\SongSearch;
use Ampache\Module\Playlist\Search\TagSearch;
use Ampache\Module\Playlist\Search\UserSearch;
use Ampache\Module\Playlist\Search\VideoSearch;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Metadata\Repository\MetadataField;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Search-related voodoo.  Beware tentacles.
 */
class Search extends playlist_object
{
    protected const DB_TABLENAME = 'search';
    public const VALID_TYPES     = array('song', 'album', 'album_disk', 'song_artist', 'album_artist', 'artist', 'genre', 'label', 'playlist', 'podcast', 'podcast_episode', 'tag', 'user', 'video');

    public $objectType; // the type of object you want to return (self::VALID_TYPES)

    public $logic_operator = 'AND';
    public $type           = 'public';
    public $random         = 0;
    public $limit          = 0;
    public $last_count     = 0;
    public $last_duration  = 0;
    public $date           = 0;

    public $types     = array(); // rules that are available to the objectType (title, year, rating, etc)
    public $rules     = array(); // rules used to run a search (User chooses rules from available types for that object)
    public $basetypes = array(); // rule operator subtypes (numeric, text, boolean, etc)

    public $search_user; // user running the search

    private $searchType; // generate sql for the object type (Ampache\Module\Playlist\Search\*)
    private $stars;
    private $order_by;

    /**
     * constructor
     * @param integer $search_id // saved searches have rules already
     * @param string $object_type // map to self::VALID_TYPES
     * @param User|null $user
     */
    public function __construct($search_id = 0, $object_type = 'song', ?User $user = null)
    {
        $this->search_user = ($user !== null)
            ? $user
            : Core::get_global('user');

        //debug_event(self::class, "SearchID: $search_id; Search Type: $object_type\n" . print_r($this, true), 5);
        $this->objectType = (in_array(strtolower($object_type), self::VALID_TYPES))
            ? strtolower($object_type)
            : 'song';
        if ($search_id > 0) {
            $info = $this->get_info($search_id);
            foreach ($info as $key => $value) {
                if ($key == 'rules') {
                    $this->rules = json_decode((string)$value, true);
                    if (!is_array($this->rules)) {
                        debug_event(__CLASS__, "Can't decode key 'rules'. Not a valid json.", 1);
                        $this->rules = array();
                    }
                } else {
                    $this->$key = $value;
                }
            }
            // make sure saved rules match the correct names
            $rule_count = 0;
            foreach ($this->rules as $rule) {
                $this->rules[$rule_count][0] = $this->_get_rule_name($rule[0]);
                $rule_count++;
            }
            // When loading a search use the owner ID for the search
            if ($this->user > 0) {
                $this->search_user = new User($this->user);
            }
        }
        $this->date  = time();
        $this->stars = array(
            T_('0 Stars'),
            T_('1 Star'),
            T_('2 Stars'),
            T_('3 Stars'),
            T_('4 Stars'),
            T_('5 Stars')
        );

        // Define our basetypes
        $this->_set_basetypes();

        switch ($this->objectType) {
            case 'album':
                $this->_set_types_album();
                $this->searchType = new AlbumSearch();
                $this->order_by   = '`album`.`name`';
                break;
            case 'album_disk':
                $this->_set_types_album();
                $this->searchType = new AlbumDiskSearch();
                $this->order_by   = '`album`.`name`';
                break;
            case 'artist':
            case 'album_artist':
            case 'song_artist':
                $this->_set_types_artist();
                $this->searchType = new ArtistSearch($this->objectType);
                $this->order_by   = '`artist`.`name`';
                $this->objectType = 'artist';
                break;
            case 'label':
                $this->_set_types_label();
                $this->searchType = new LabelSearch();
                $this->order_by   = '`label`.`name`';
                break;
            case 'playlist':
                $this->_set_types_playlist();
                $this->searchType = new PlaylistSearch();
                $this->order_by   = '`playlist`.`name`';
                break;
            case 'podcast':
                $this->_set_types_podcast();
                $this->searchType = new PodcastSearch();
                $this->order_by   = '`podcast`.`title`';
                break;
            case 'podcast_episode':
                $this->_set_types_podcast_episode();
                $this->searchType = new PodcastEpisodeSearch();
                $this->order_by   = '`podcast_episode`.`pubdate` DESC';
                break;
            case 'song':
                $this->_set_types_song();
                $this->searchType = new SongSearch();
                $this->order_by   = '`song`.`file`';
                break;
            case 'tag':
            case 'genre':
                $this->_set_types_tag();
                $this->searchType = new TagSearch();
                $this->order_by   = '`tag`.`name`';
                break;
            case 'user':
                $this->_set_types_user();
                $this->searchType = new UserSearch();
                $this->order_by   = '`user`.`username`';
                break;
            case 'video':
                $this->_set_types_video();
                $this->searchType = new VideoSearch();
                $this->order_by   = '`video`.`file`';
                break;
        } // end switch on objectType
    } // end constructor

    public function getId(): int
    {
        return (int)$this->id;
    }

    /**
     * _set_basetypes
     *
     * Function called during construction to set the different types and rules for search
     */
    private function _set_basetypes()
    {
        $this->basetypes['numeric'][] = array(
            'name' => 'gte',
            'description' => T_('is greater than or equal to'),
            'sql' => '>='
        );

        $this->basetypes['numeric'][] = array(
            'name' => 'lte',
            'description' => T_('is less than or equal to'),
            'sql' => '<='
        );

        $this->basetypes['numeric'][] = array(
            'name' => 'equal',
            'description' => T_('equals'),
            'sql' => '<=>'
        );

        $this->basetypes['numeric'][] = array(
            'name' => 'ne',
            'description' => T_('does not equal'),
            'sql' => '<>'
        );

        $this->basetypes['numeric'][] = array(
            'name' => 'gt',
            'description' => T_('is greater than'),
            'sql' => '>'
        );

        $this->basetypes['numeric'][] = array(
            'name' => 'lt',
            'description' => T_('is less than'),
            'sql' => '<'
        );

        $this->basetypes['is_true'][] = array(
            'name' => 'true',
            'description' => T_('is true'),
            'sql' => '1'
        );

        $this->basetypes['boolean'][] = array(
            'name' => 'true',
            'description' => T_('is true'),
            'sql' => '1'
        );

        $this->basetypes['boolean'][] = array(
            'name' => 'false',
            'description' => T_('is false'),
            'sql' => '0'
        );

        $this->basetypes['text'][] = array(
            'name' => 'contain',
            'description' => T_('contains'),
            'sql' => 'LIKE',
            'preg_match' => array('/^/', '/$/'),
            'preg_replace' => array('%', '%')
        );

        $this->basetypes['text'][] = array(
            'name' => 'notcontain',
            'description' => T_('does not contain'),
            'sql' => 'NOT LIKE',
            'preg_match' => array('/^/', '/$/'),
            'preg_replace' => array('%', '%')
        );

        $this->basetypes['text'][] = array(
            'name' => 'start',
            'description' => T_('starts with'),
            'sql' => 'LIKE',
            'preg_match' => '/$/',
            'preg_replace' => '%'
        );

        $this->basetypes['text'][] = array(
            'name' => 'end',
            'description' => T_('ends with'),
            'sql' => 'LIKE',
            'preg_match' => '/^/',
            'preg_replace' => '%'
        );

        $this->basetypes['text'][] = array(
            'name' => 'equal',
            'description' => T_('is'),
            'sql' => '='
        );

        $this->basetypes['text'][] = array(
            'name' => 'not equal',
            'description' => T_('is not'),
            'sql' => '!='
        );

        $this->basetypes['text'][] = array(
            'name' => 'sounds',
            'description' => T_('sounds like'),
            'sql' => 'SOUNDS LIKE'
        );

        $this->basetypes['text'][] = array(
            'name' => 'notsounds',
            'description' => T_('does not sound like'),
            'sql' => 'NOT SOUNDS LIKE'
        );

        $this->basetypes['text'][] = array(
            'name' => 'regexp',
            'description' => T_('matches regular expression'),
            'sql' => 'REGEXP'
        );

        $this->basetypes['text'][] = array(
            'name' => 'notregexp',
            'description' => T_('does not match regular expression'),
            'sql' => 'NOT REGEXP'
        );

        $this->basetypes['tags'][] = array(
            'name' => 'contain',
            'description' => T_('contains'),
            'sql' => 'LIKE',
            'preg_match' => array('/^/', '/$/'),
            'preg_replace' => array('%', '%')
        );

        $this->basetypes['tags'][] = array(
            'name' => 'notcontain',
            'description' => T_('does not contain'),
            'sql' => 'NOT LIKE',
            'preg_match' => array('/^/', '/$/'),
            'preg_replace' => array('%', '%')
        );

        $this->basetypes['tags'][] = array(
            'name' => 'start',
            'description' => T_('starts with'),
            'sql' => 'LIKE',
            'preg_match' => '/$/',
            'preg_replace' => '%'
        );

        $this->basetypes['tags'][] = array(
            'name' => 'end',
            'description' => T_('ends with'),
            'sql' => 'LIKE',
            'preg_match' => '/^/',
            'preg_replace' => '%'
        );

        $this->basetypes['tags'][] = array(
            'name' => 'equal',
            'description' => T_('is'),
            'sql' => '>'
        );

        $this->basetypes['tags'][] = array(
            'name' => 'not equal',
            'description' => T_('is not'),
            'sql' => '='
        );

        $this->basetypes['boolean_numeric'][] = array(
            'name' => 'equal',
            'description' => T_('is'),
            'sql' => '<=>'
        );

        $this->basetypes['boolean_numeric'][] = array(
            'name' => 'ne',
            'description' => T_('is not'),
            'sql' => '<>'
        );

        $this->basetypes['boolean_subsearch'][] = array(
            'name' => 'equal',
            'description' => T_('is'),
            'sql' => ''
        );

        $this->basetypes['boolean_subsearch'][] = array(
            'name' => 'ne',
            'description' => T_('is not'),
            'sql' => 'NOT'
        );

        $this->basetypes['date'][] = array(
            'name' => 'lt',
            'description' => T_('before'),
            'sql' => '<'
        );

        $this->basetypes['date'][] = array(
            'name' => 'gt',
            'description' => T_('after'),
            'sql' => '>'
        );

        $this->basetypes['days'][] = array(
            'name' => 'lt',
            'description' => T_('before (x) days ago'),
            'sql' => '<'
        );

        $this->basetypes['days'][] = array(
            'name' => 'gt',
            'description' => T_('after (x) days ago'),
            'sql' => '>'
        );

        $this->basetypes['recent_played'][] = array(
            'name' => 'ply',
            'description' => T_('Limit'),
            'sql' => '`date`'
        );
        $this->basetypes['recent_added'][] = array(
            'name' => 'add',
            'description' => T_('Limit'),
            'sql' => '`addition_time`'
        );

        $this->basetypes['recent_updated'][] = array(
            'name' => 'upd',
            'description' => T_('Limit'),
            'sql' => '`update_time`'
        );

        $this->basetypes['user_numeric'][] = array(
            'name' => 'love',
            'description' => T_('has loved'),
            'sql' => 'userflag'
        );

        $this->basetypes['user_numeric'][] = array(
            'name' => '5star',
            'description' => T_('has rated 5 stars'),
            'sql' => '`rating` = 5'
        );

        $this->basetypes['user_numeric'][] = array(
            'name' => '4star',
            'description' => T_('has rated 4 stars'),
            'sql' => '`rating` = 4'
        );

        $this->basetypes['user_numeric'][] = array(
            'name' => '3star',
            'description' => T_('has rated 3 stars'),
            'sql' => '`rating` = 3'
        );

        $this->basetypes['user_numeric'][] = array(
            'name' => '2star',
            'description' => T_('has rated 2 stars'),
            'sql' => '`rating` = 2'
        );

        $this->basetypes['user_numeric'][] = array(
            'name' => '1star',
            'description' => T_('has rated 1 star'),
            'sql' => '`rating` = 1'
        );

        $this->basetypes['user_numeric'][] = array(
            'name' => 'unrated',
            'description' => T_('has not rated'),
            'sql' => 'unrated'
        );
        $this->basetypes['multiple'] = array_merge($this->basetypes['text'], $this->basetypes['numeric']);
    }

    /**
     * _add_type_numeric
     *
     * Generic integer searches rules
     * @param string $name
     * @param string $label
     * @param string $type
     * @param string $group
     */
    private function _add_type_numeric($name, $label, $type = 'numeric', $group = '')
    {
        $this->types[] = array(
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'widget' => array('input', 'number'),
            'title' => $group
        );
    }

    /**
     * _add_type_date
     *
     * Generic date searches rules
     * @param string $name
     * @param string $label
     * @param string $group
     */
    private function _add_type_date($name, $label, $group = '')
    {
        $this->types[] = array(
            'name' => $name,
            'label' => $label,
            'type' => 'date',
            'widget' => array('input', 'datetime-local'),
            'title' => $group
        );
    }

    /**
     * _add_type_text
     *
     * Generic text rules
     * @param string $name
     * @param string $label
     * @param string $group
     */
    private function _add_type_text($name, $label, $group = '')
    {
        $this->types[] = array(
            'name' => $name,
            'label' => $label,
            'type' => 'text',
            'widget' => array('input', 'text'),
            'title' => $group
        );
    }

    /**
     * _add_type_select
     *
     * Generic rule to select from a list
     * @param string $name
     * @param string $label
     * @param string $type
     * @param array $array
     * @param string $group
     */
    private function _add_type_select($name, $label, $type, $array, $group = '')
    {
        $this->types[] = array(
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'widget' => array('select', $array),
            'title' => $group
        );
    }

    /**
     * _add_type_boolean
     *
     * True or false generic searches
     * @param string $name
     * @param string $label
     * @param string $type
     * @param string $group
     */
    private function _add_type_boolean($name, $label, $type = 'boolean', $group = '')
    {
        $this->types[] = array(
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'widget' => array('input', 'hidden'),
            'title' => $group
        );
    }

    /**
     * _set_types_song
     *
     * this is where all the available rules for songs are defined
     */
    private function _set_types_song()
    {
        $this->_add_type_text('anywhere', T_('Any searchable text'));

        $t_song_data = T_('Song Data');
        $this->_add_type_text('title', T_('Title'), $t_song_data);
        $this->_add_type_text('album', T_('Album'), $t_song_data);
        $this->_add_type_text('artist', T_('Song Artist'), $t_song_data);
        $this->_add_type_text('album_artist', T_('Album Artist'), $t_song_data);
        $this->_add_type_text('composer', T_('Composer'), $t_song_data);
        $this->_add_type_numeric('track', T_('Track'), 'numeric', $t_song_data);
        $this->_add_type_numeric('year', T_('Year'), 'numeric', $t_song_data);
        $this->_add_type_numeric('time', T_('Length (in minutes)'), 'numeric', $t_song_data);
        $this->_add_type_text('label', T_('Label'), $t_song_data);
        $this->_add_type_text('comment', T_('Comment'), $t_song_data);
        $this->_add_type_text('lyrics', T_('Lyrics'), $t_song_data);

        $t_ratings = T_('Ratings');
        if (AmpConfig::get('ratings')) {
            $this->_add_type_select('myrating', T_('My Rating'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_select('rating', T_('Rating (Average)'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_select('albumrating', T_('My Rating (Album)'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_select('artistrating', T_('My Rating (Artist)'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_boolean('my_flagged', T_('My Favorite Songs'), 'boolean', $t_ratings);
            $this->_add_type_boolean('my_flagged_album', T_('My Favorite Albums'), 'boolean', $t_ratings);
            $this->_add_type_boolean('my_flagged_artist', T_('My Favorite Artists'), 'boolean', $t_ratings);
            $this->_add_type_text('favorite', T_('Favorites'), $t_ratings);
            $this->_add_type_text('favorite_album', T_('Favorites (Album)'), $t_ratings);
            $this->_add_type_text('favorite_artist', T_('Favorites (Artist)'), $t_ratings);
            $users = $this->getUserRepository()->getValidArray();
            $this->_add_type_select('other_user', T_('Another User'), 'user_numeric', $users, $t_ratings);
            $this->_add_type_select('other_user_album', T_('Another User (Album)'), 'user_numeric', $users, $t_ratings);
            $this->_add_type_select('other_user_artist', T_('Another User (Artist)'), 'user_numeric', $users, $t_ratings);
        }

        $t_play_data = T_('Play History');
        /* HINT: Number of times object has been played */
        $this->_add_type_numeric('played_times', T_('# Played'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $this->_add_type_numeric('skipped_times', T_('# Skipped'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $this->_add_type_numeric('played_or_skipped_times', T_('# Played or Skipped'), 'numeric', $t_play_data);
        /* HINT: Percentage of (Times Played / Times skipped) * 100 */
        $this->_add_type_numeric('play_skip_ratio', T_('Played/Skipped ratio'), 'numeric', $t_play_data);
        $this->_add_type_numeric('last_play', T_('My Last Play'), 'days', $t_play_data);
        $this->_add_type_numeric('last_skip', T_('My Last Skip'), 'days', $t_play_data);
        $this->_add_type_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days', $t_play_data);
        $this->_add_type_boolean('played', T_('Played'), 'boolean', $t_play_data);
        $this->_add_type_boolean('myplayed', T_('Played by Me'), 'boolean', $t_play_data);
        $this->_add_type_boolean('myplayedalbum', T_('Played by Me (Album)'), 'boolean', $t_play_data);
        $this->_add_type_boolean('myplayedartist', T_('Played by Me (Artist)'), 'boolean', $t_play_data);
        $this->_add_type_numeric('recent_played', T_('Recently played'), 'recent_played', $t_play_data);

        $t_genre = T_('Genre');
        $this->_add_type_text('genre', $t_genre, $t_genre);
        $this->_add_type_text('album_genre', T_('Album Genre'), $t_genre);
        $this->_add_type_text('artist_genre', T_('Artist Genre'), $t_genre);
        $this->_add_type_boolean('no_genre', T_('No Genre'), 'is_true', $t_genre);

        $t_playlists = T_('Playlists');
        $playlists   = Playlist::get_playlist_array($this->user);
        if (!empty($playlists)) {
            $this->_add_type_select('playlist', T_('Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }
        $playlists = self::get_search_array($this->user);
        if (!empty($playlists)) {
            $this->_add_type_select('smartplaylist', T_('Smart Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }
        $this->_add_type_text('playlist_name', T_('Playlist Name'), $t_playlists);

        $t_file_data = T_('File Data');
        $this->_add_type_text('file', T_('Filename'), $t_file_data);
        $bitrate_array = array(
            '32',
            '40',
            '48',
            '56',
            '64',
            '80',
            '96',
            '112',
            '128',
            '160',
            '192',
            '224',
            '256',
            '320',
            '640',
            '1280'
        );
        $this->_add_type_select('bitrate', T_('Bitrate'), 'numeric', $bitrate_array, $t_file_data);
        $this->_add_type_date('added', T_('Added'), $t_file_data);
        $this->_add_type_date('updated', T_('Updated'), $t_file_data);
        if (AmpConfig::get('licensing')) {
            $licenses = array();
            foreach ($this->getLicenseRepository()->getAll() as $license_id) {
                $license               = new License($license_id);
                $licenses[$license_id] = $license->name;
            }
            $this->_add_type_select('license', T_('Music License'), 'boolean_numeric', $licenses, $t_file_data);
        }
        $this->_add_type_numeric('recent_added', T_('Recently added'), 'recent_added', $t_file_data);
        $this->_add_type_numeric('recent_updated', T_('Recently updated'), 'recent_updated', $t_file_data);
        $this->_add_type_boolean('possible_duplicate', T_('Possible Duplicate'), 'is_true', $t_file_data);
        $this->_add_type_boolean('duplicate_tracks', T_('Duplicate Album Tracks'), 'is_true', $t_file_data);
        $this->_add_type_boolean('possible_duplicate_album', T_('Possible Duplicate Albums'), 'is_true', $t_file_data);
        $this->_add_type_boolean('orphaned_album', T_('Orphaned Album'), 'is_true', $t_file_data);
        $catalogs = array();
        foreach (Catalog::get_catalogs('music', $this->user) as $catid) {
            $catalog          = Catalog::create_from_id($catid);
            $catalogs[$catid] = $catalog->name;
        }
        if (!empty($catalogs)) {
            $this->_add_type_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs, $t_file_data);
        }

        $t_musicbrainz = T_('MusicBrainz');
        $this->_add_type_text('mbid', T_('MusicBrainz ID'), $t_musicbrainz);
        $this->_add_type_text('mbid_album', T_('MusicBrainz ID (Album)'), $t_musicbrainz);
        $this->_add_type_text('mbid_artist', T_('MusicBrainz ID (Artist)'), $t_musicbrainz);

        $t_metadata = T_('Metadata');
        if (AmpConfig::get('enable_custom_metadata')) {
            $metadataFields          = array();
            $metadataFieldRepository = new MetadataField();
            foreach ($metadataFieldRepository->findAll() as $metadata) {
                $metadataFields[$metadata->getId()] = $metadata->getName();
            }
            $this->types[] = array(
                'name' => 'metadata',
                'label' => $t_metadata,
                'type' => 'multiple',
                'subtypes' => $metadataFields,
                'widget' => array('subtypes', array('input', 'text')),
                'title' => $t_metadata
            );
        }
    }

    /**
     * _set_types_artist
     *
     * this is where all the available rules for artists are defined
     */
    private function _set_types_artist()
    {
        $t_artist_data = T_('Artist Data');
        $this->_add_type_text('title', T_('Name'), $t_artist_data);
        $this->_add_type_text('album', T_('Album Title'), $t_artist_data);
        $this->_add_type_text('song', T_('Song Title'), $t_artist_data);
        $this->_add_type_text('summary', T_('Summary'), $t_artist_data);
        $this->_add_type_numeric('yearformed', T_('Year Formed'), 'numeric', $t_artist_data);
        $this->_add_type_text('placeformed', T_('Place Formed'), $t_artist_data);
        $this->_add_type_numeric('time', T_('Length (in minutes)'), 'numeric', $t_artist_data);
        $this->_add_type_numeric('album_count', T_('Album Count'), 'numeric', $t_artist_data);
        $this->_add_type_numeric('song_count', T_('Song Count'), 'numeric', $t_artist_data);

        $t_ratings = T_('Ratings');
        if (AmpConfig::get('ratings')) {
            $this->_add_type_select('myrating', T_('My Rating'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_select('rating', T_('Rating (Average)'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_select('songrating', T_('My Rating (Song)'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_select('albumrating', T_('My Rating (Album)'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_text('favorite', T_('Favorites'), $t_ratings);
            $users = $this->getUserRepository()->getValidArray();
            $this->_add_type_select('other_user', T_('Another User'), 'user_numeric', $users, $t_ratings);
        }

        $t_play_data = T_('Play History');
        /* HINT: Number of times object has been played */
        $this->_add_type_numeric('played_times', T_('# Played'), 'numeric', $t_play_data);
        $this->_add_type_numeric('last_play', T_('My Last Play'), 'days', $t_play_data);
        $this->_add_type_numeric('last_skip', T_('My Last Skip'), 'days', $t_play_data);
        $this->_add_type_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days', $t_play_data);
        $this->_add_type_boolean('played', T_('Played'), 'boolean', $t_play_data);
        $this->_add_type_boolean('myplayed', T_('Played by Me'), 'boolean', $t_play_data);
        $this->_add_type_numeric('recent_played', T_('Recently played'), 'recent_played', $t_play_data);

        $t_genre = T_('Genre');
        $this->_add_type_text('genre', $t_genre, $t_genre);
        $this->_add_type_text('song_genre', T_('Song Genre'), $t_genre);
        $this->_add_type_boolean('no_genre', T_('No Genre'), 'is_true', $t_genre);

        $t_playlists = T_('Playlists');
        $playlists   = Playlist::get_playlist_array($this->user);
        if (!empty($playlists)) {
            $this->_add_type_select('playlist', T_('Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }
        $this->_add_type_text('playlist_name', T_('Playlist Name'), $t_playlists);

        $t_file_data = T_('File Data');
        $this->_add_type_text('file', T_('Filename'), $t_file_data);
        $this->_add_type_boolean('has_image', T_('Local Image'), 'boolean', $t_file_data);
        $this->_add_type_numeric('image_width', T_('Image Width'), 'numeric', $t_file_data);
        $this->_add_type_numeric('image_height', T_('Image Height'), 'numeric', $t_file_data);
        $this->_add_type_boolean('possible_duplicate', T_('Possible Duplicate'), 'is_true', $t_file_data);
        $this->_add_type_boolean('possible_duplicate_album', T_('Possible Duplicate Albums'), 'is_true', $t_file_data);
        $catalogs = array();
        foreach (Catalog::get_catalogs('music', $this->user) as $catid) {
            $catalog          = Catalog::create_from_id($catid);
            $catalogs[$catid] = $catalog->name;
        }
        if (!empty($catalogs)) {
            $this->_add_type_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs, $t_file_data);
        }

        $t_musicbrainz = T_('MusicBrainz');
        $this->_add_type_text('mbid', T_('MusicBrainz ID'), $t_musicbrainz);
        $this->_add_type_text('mbid_album', T_('MusicBrainz ID (Album)'), $t_musicbrainz);
        $this->_add_type_text('mbid_song', T_('MusicBrainz ID (Song)'), $t_musicbrainz);
    } // artisttypes

    /**
     * _set_types_album
     *
     * this is where all the available rules for albums are defined
     */
    private function _set_types_album()
    {
        $t_album_data = T_('Album Data');
        $this->_add_type_text('title', T_('Title'), $t_album_data);
        $this->_add_type_text('artist', T_('Album Artist'), $t_album_data);
        $this->_add_type_text('song_artist', T_('Song Artist'), $t_album_data);
        $this->_add_type_text('song', T_('Song Title'), $t_album_data);
        $this->_add_type_numeric('year', T_('Year'), 'numeric', $t_album_data);
        $this->_add_type_numeric('original_year', T_('Original Year'), 'numeric', $t_album_data);
        $this->_add_type_numeric('time', T_('Length (in minutes)'), 'numeric', $t_album_data);
        $this->_add_type_text('release_type', T_('Release Type'), $t_album_data);
        $this->_add_type_text('release_status', T_('Release Status'), $t_album_data);
        $this->_add_type_text('subtitle', T_('Release Comment'), $t_album_data);
        $this->_add_type_text('barcode', T_('Barcode'), $t_album_data);
        $this->_add_type_text('catalog_number', T_('Catalog Number'), $t_album_data);
        $this->_add_type_numeric('song_count', T_('Song Count'), 'numeric', $t_album_data);

        $t_ratings = T_('Ratings');
        if (AmpConfig::get('ratings')) {
            $this->_add_type_select('myrating', T_('My Rating'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_select('rating', T_('Rating (Average)'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_select('songrating', T_('My Rating (Song)'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_select('artistrating', T_('My Rating (Artist)'), 'numeric', $this->stars, $t_ratings);
            $this->_add_type_text('favorite', T_('Favorites'), $t_ratings);
            $users = $this->getUserRepository()->getValidArray();
            $this->_add_type_select('other_user', T_('Another User'), 'user_numeric', $users, $t_ratings);
        }

        $t_play_data = T_('Play History');
        /* HINT: Number of times object has been played */
        $this->_add_type_numeric('played_times', T_('# Played'), 'numeric', $t_play_data);
        $this->_add_type_numeric('last_play', T_('My Last Play'), 'days', $t_play_data);
        $this->_add_type_numeric('last_skip', T_('My Last Skip'), 'days', $t_play_data);
        $this->_add_type_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days', $t_play_data);
        $this->_add_type_boolean('played', T_('Played'), 'boolean', $t_play_data);
        $this->_add_type_boolean('myplayed', T_('Played by Me'), 'boolean', $t_play_data);
        $this->_add_type_boolean('myplayedartist', T_('Played by Me (Artist)'), 'boolean', $t_play_data);
        $this->_add_type_numeric('recent_played', T_('Recently played'), 'recent_played', $t_play_data);

        $t_genre = T_('Genre');
        $this->_add_type_text('genre', $t_genre, $t_genre);
        $this->_add_type_text('song_genre', T_('Song Genre'), $t_genre);
        $this->_add_type_boolean('no_genre', T_('No Genre'), 'is_true', $t_genre);

        $t_playlists = T_('Playlists');
        $playlists   = Playlist::get_playlist_array($this->user);
        if (!empty($playlists)) {
            $this->_add_type_select('playlist', T_('Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }
        $playlists = self::get_search_array($this->user);
        if (!empty($playlists)) {
            $this->_add_type_select('smartplaylist', T_('Smart Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }
        $this->_add_type_text('playlist_name', T_('Playlist Name'), $t_playlists);

        $t_file_data = T_('File Data');
        $this->_add_type_text('file', T_('Filename'), $t_file_data);
        $this->_add_type_boolean('has_image', T_('Local Image'), 'boolean', $t_file_data);
        $this->_add_type_numeric('image_width', T_('Image Width'), 'numeric', $t_file_data);
        $this->_add_type_numeric('image_height', T_('Image Height'), 'numeric', $t_file_data);
        $this->_add_type_boolean('possible_duplicate', T_('Possible Duplicate'), 'is_true', $t_file_data);
        $this->_add_type_boolean('duplicate_tracks', T_('Duplicate Album Tracks'), 'is_true', $t_file_data);
        $this->_add_type_boolean('duplicate_mbid_group', T_('Duplicate MusicBrainz Release Group'), 'is_true', $t_file_data);
        $this->_add_type_numeric('recent_added', T_('Recently added'), 'recent_added', $t_file_data);
        $catalogs = array();
        foreach (Catalog::get_catalogs('music', $this->user) as $catid) {
            $catalog          = Catalog::create_from_id($catid);
            $catalogs[$catid] = $catalog->name;
        }
        if (!empty($catalogs)) {
            $this->_add_type_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs, $t_file_data);
        }

        $t_musicbrainz = T_('MusicBrainz');
        $this->_add_type_text('mbid', T_('MusicBrainz ID'), $t_musicbrainz);
        $this->_add_type_text('mbid_artist', T_('MusicBrainz ID (Artist)'), $t_musicbrainz);
        $this->_add_type_text('mbid_song', T_('MusicBrainz ID (Song)'), $t_musicbrainz);
    } // albumtypes

    /**
     * _set_types_video
     *
     * this is where all the available rules for videos are defined
     */
    private function _set_types_video()
    {
        $this->_add_type_text('file', T_('Filename'));
    }

    /**
     * _set_types_playlist
     *
     * this is where all the available rules for playlists are defined
     */
    private function _set_types_playlist()
    {
        $t_playlist = T_('Playlist');
        $this->_add_type_text('title', T_('Name'), $t_playlist);
        $playlist_types = array(
            0 => T_('public'),
            1 => T_('private')
        );
        $this->_add_type_select('type', T_('Type'), 'boolean_numeric', $playlist_types, $t_playlist);
        $users = $this->getUserRepository()->getValidArray();
        $this->_add_type_select('owner', T_('Owner'), 'user_numeric', $users, $t_playlist);
    }

    /**
     * _set_types_podcast
     *
     * this is where all the available rules for podcasts are defined
     */
    private function _set_types_podcast()
    {
        $t_podcasts = T_('Podcast');
        $this->_add_type_text('title', T_('Name'), $t_podcasts);
        $this->_add_type_numeric('episode_count', T_('Episode Count'), 'numeric', $t_podcasts);

        $t_podcast_episodes = T_('Podcast Episodes');
        $this->_add_type_text('podcast_episode', T_('Podcast Episode'), $t_podcast_episodes);
        $episode_states = array(
            0 => T_('skipped'),
            1 => T_('pending'),
            2 => T_('completed')
        );
        $this->_add_type_select('state', T_('State'), 'boolean_numeric', $episode_states, $t_podcast_episodes);
        $this->_add_type_numeric('time', T_('Length (in minutes)'), 'numeric', $t_podcast_episodes);

        $t_play_data = T_('Play History');
        /* HINT: Number of times object has been played */
        $this->_add_type_numeric('played_times', T_('# Played'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $this->_add_type_numeric('skipped_times', T_('# Skipped'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $this->_add_type_numeric('played_or_skipped_times', T_('# Played or Skipped'), 'numeric', $t_play_data);
        $this->_add_type_numeric('last_play', T_('My Last Play'), 'days', $t_play_data);
        $this->_add_type_numeric('last_skip', T_('My Last Skip'), 'days', $t_play_data);
        $this->_add_type_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days', $t_play_data);
        $this->_add_type_boolean('played', T_('Played'), 'boolean', $t_play_data);
        $this->_add_type_boolean('myplayed', T_('Played by Me'), 'boolean', $t_play_data);
        $this->_add_type_numeric('recent_played', T_('Recently played'), 'recent_played', $t_play_data);

        $t_file_data = T_('File Data');
        $this->_add_type_text('file', T_('Filename'), $t_file_data);
        $this->_add_type_date('pubdate', T_('Publication Date'), $t_file_data);
        $this->_add_type_date('added', T_('Added'), $t_file_data);
    }

    /**
     * _set_types_podcast_episode
     *
     * this is where all the available rules for podcast_episodes are defined
     */
    private function _set_types_podcast_episode()
    {
        $t_podcast_episodes = T_('Podcast Episode');
        $this->_add_type_text('title', T_('Name'), $t_podcast_episodes);
        $this->_add_type_text('podcast', T_('Podcast'), $t_podcast_episodes);
        $episode_states = array(
            0 => T_('skipped'),
            1 => T_('pending'),
            2 => T_('completed')
        );
        $this->_add_type_select('state', T_('State'), 'boolean_numeric', $episode_states, $t_podcast_episodes);
        $this->_add_type_numeric('time', T_('Length (in minutes)'), 'numeric', $t_podcast_episodes);

        $t_play_data = T_('Play History');
        /* HINT: Number of times object has been played */
        $this->_add_type_numeric('played_times', T_('# Played'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $this->_add_type_numeric('skipped_times', T_('# Skipped'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $this->_add_type_numeric('played_or_skipped_times', T_('# Played or Skipped'), 'numeric', $t_play_data);
        $this->_add_type_numeric('last_play', T_('My Last Play'), 'days', $t_play_data);
        $this->_add_type_numeric('last_skip', T_('My Last Skip'), 'days', $t_play_data);
        $this->_add_type_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days', $t_play_data);
        $this->_add_type_boolean('played', T_('Played'), 'boolean', $t_play_data);
        $this->_add_type_boolean('myplayed', T_('Played by Me'), 'boolean', $t_play_data);
        $this->_add_type_numeric('recent_played', T_('Recently played'), 'recent_played', $t_play_data);

        $t_file_data = T_('File Data');
        $this->_add_type_text('file', T_('Filename'), $t_file_data);
        $this->_add_type_date('pubdate', T_('Publication Date'), $t_file_data);
        $this->_add_type_date('added', T_('Added'), $t_file_data);
    }

    /**
     * _set_types_label
     *
     * this is where all the available rules for labels are defined
     */
    private function _set_types_label()
    {
        $t_label = T_('Label');
        $this->_add_type_text('title', T_('Name'), $t_label);
        $this->_add_type_text('category', T_('Category'), $t_label);
    }

    /**
     * _set_types_user
     *
     * this is where all the available rules for users are defined
     */
    private function _set_types_user()
    {
        $this->_add_type_text('username', T_('Username'));
    }

    /**
     * _set_types_tag
     *
     * this is where all the available rules for Genres are defined
     */
    private function _set_types_tag()
    {
        $this->_add_type_text('title', T_('Genre'));
    }

    /**
     * _filter_request
     *
     * Sanitizes raw search data
     * @param array $data
     * @return array
     */
    private static function _filter_request($data)
    {
        $request = array();
        foreach ($data as $key => $value) {
            $prefix = substr($key, 0, 4);
            $value  = (string)$value;

            if ($prefix == 'rule' && strlen((string)$value)) {
                $request[$key] = Dba::escape($value);
            }
        }
        // Figure out if they want an AND based search or an OR based search
        $operator = $data['operator'] ?? '';
        switch (strtolower($operator)) {
            case 'or':
                $request['operator'] = 'OR';
                break;
            case 'and':
            default:
                $request['operator'] = 'AND';
                break;
        }
        if (array_key_exists('limit', $data)) {
            $request['limit'] = $data['limit'];
        }
        if (array_key_exists('offset', $data)) {
            $request['offset'] = $data['offset'];
        }
        if (array_key_exists('random', $data)) {
            $request['random'] = $data['random'];
        }

        // Verify the type
        $search_type = strtolower($data['type'] ?? '');
        //Search::VALID_TYPES = array('song', 'album', 'album_disk', 'song_artist', 'album_artist', 'artist', 'label', 'playlist', 'podcast', 'podcast_episode', 'tag', 'user', 'video')
        switch ($search_type) {
            case 'song':
            case 'album':
            case 'album_disk':
            case 'song_artist':
            case 'album_artist':
            case 'artist':
            case 'label':
            case 'playlist':
            case 'podcast':
            case 'podcast_episode':
            case 'tag':  // for Genres
            case 'user':
            case 'video':
                $request['type'] = $search_type;
                break;
            case 'genre':
                $request['type'] = 'tag';
                break;
            default:
                debug_event(self::class, "_filter_request: search_type '$search_type' reset to: song", 5);
                $request['type'] = 'song';
                break;
        }

        return $request;
    } // end _filter_request

    /**
     * get_searches
     *
     * Return the IDs of all saved searches accessible by the current user.
     * @param integer $user_id
     * @return array
     */
    public static function get_searches($user_id = null)
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }
        $key     = 'searches';
        if (parent::is_cached($key, $user_id)) {
            return parent::get_from_cache($key, $user_id);
        }
        $is_admin = (Access::check('interface', 100, $user_id) || $user_id == -1);
        $sql      = "SELECT `id` FROM `search` ";
        $params   = array();

        if (!$is_admin) {
            $sql .= "WHERE (`user` = ? OR `type` = 'public') ";
            $params[] = $user_id;
        }
        $sql .= "ORDER BY `name`";

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        parent::add_to_cache($key, $user_id, $results);

        return $results;
    }

    /**
     * get_search_array
     * Returns a list of searches accessible by the user with formatted name.
     * @param integer $user_id
     * @return array
     */
    public static function get_search_array($user_id = null)
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }
        $key = 'searcharray';
        if (parent::is_cached($key, $user_id)) {
            return parent::get_from_cache($key, $user_id);
        }
        $is_admin = (Access::check('interface', 100, $user_id) || $user_id == -1);
        $sql      = "SELECT `id`, IF(`user` = ?, `name`, CONCAT(`name`, ' (', `username`, ')')) AS `name` FROM `search` ";
        $params   = array($user_id);

        if (!$is_admin) {
            $sql .= "WHERE (`user` = ? OR `type` = 'public') ";
            $params[] = $user_id;
        }
        $sql .= "ORDER BY `name`";
        //debug_event(self::class, 'get_searches query: ' . $sql . "\n" . print_r($params, true), 5);

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        parent::add_to_cache($key, $user_id, $results);

        return $results;
    } // get_smartlist_array

    /**
     * run
     *
     * This function actually runs the search and returns an array of the
     * results.
     * @param array $data
     * @param User $user
     * @return integer[]
     */
    public static function run($data, $user = null)
    {
        $limit  = (int)($data['limit'] ?? 0);
        $offset = (int)($data['offset'] ?? 0);
        $random = ((int)($data['random'] ?? 0) > 0) ? 1 : 0;
        $search = new Search(null, $data['type'], $user);
        $search->set_rules($data);

        // Generate BASE SQL
        $limit_sql = "";
        if ($limit > 0) {
            $limit_sql = ' LIMIT ';
            if ($offset > 0) {
                $limit_sql .= $offset . ", ";
            }
            $limit_sql .= $limit;
        }

        $search_info = $search->to_sql();
        $sql         = $search_info['base'] . ' ' . $search_info['table_sql'];
        if (!empty($search_info['where_sql'])) {
            $sql .= ' WHERE ' . $search_info['where_sql'];
        }
        if (!empty($search_info['group_sql'])) {
            $sql .= ' GROUP BY ' . $search_info['group_sql'];
            if (!empty($search_info['having_sql'])) {
                $sql .= ' HAVING ' . $search_info['having_sql'];
            }
        }
        $sql .= ($random > 0) ? " ORDER BY RAND()" : " ORDER BY " . $search->order_by;
        $sql .= ' ' . $limit_sql;
        $sql = trim((string)$sql);
        //debug_event(self::class, 'SQL run: ' . $sql . "\n" . print_r($search_info['parameters'], true), 5);

        $db_results = Dba::read($sql, $search_info['parameters']);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * delete
     *
     * Does what it says on the tin.
     * @return boolean
     */
    public function delete()
    {
        $sql = "DELETE FROM `search` WHERE `id` = ?";
        Dba::write($sql, array($this->id));
        Catalog::count_table('search');

        return true;
    }

    /**
     * format
     * Gussy up the data
     * @param boolean $details
     */
    public function format($details = true)
    {
        parent::format($details);
    }

    /**
     * get_items
     *
     * Return an array of the items output by our search (part of the
     * playlist interface).
     * @return array
     */
    public function get_items()
    {
        $results = array();

        $sqltbl = $this->to_sql();
        $sql    = $sqltbl['base'] . ' ' . $sqltbl['table_sql'];
        if (!empty($sqltbl['where_sql'])) {
            $sql .= ' WHERE ' . $sqltbl['where_sql'];
        }
        if (!empty($sqltbl['group_sql'])) {
            $sql .= ' GROUP BY ' . $sqltbl['group_sql'];
        }
        if (!empty($sqltbl['having_sql'])) {
            $sql .= ' HAVING ' . $sqltbl['having_sql'];
        }

        $sql .= ($this->random > 0) ? " ORDER BY RAND()" : " ORDER BY " . $this->order_by;
        if ($this->limit > 0) {
            $sql .= " LIMIT " . (string)($this->limit);
        }
        //debug_event(self::class, 'SQL get_items: ' . $sql . "\n" . print_r($sqltbl['parameters'], true), 5);

        $count      = 1;
        $db_results = Dba::read($sql, $sqltbl['parameters']);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array(
                'object_id' => $row['id'],
                'object_type' => $this->objectType,
                'track' => $count++,
                'track_id' => $row['id'],
            );
        }
        $this->date = time();
        $this->set_last(count($results), 'last_count');
        $this->set_last(self::get_total_duration($results), 'last_duration');

        return $results;
    }

    /**
     * get_subsearch
     *
     * get SQL for an item subsearch
     * @return array
     */
    public function get_subsearch($table)
    {
        $sqltbl = $this->to_sql();
        $sql    = "SELECT DISTINCT(`$table`.`id`) FROM `$table` " . $sqltbl['table_sql'];
        if (!empty($sqltbl['where_sql'])) {
            $sql .= ' WHERE ' . $sqltbl['where_sql'];
        }
        if (!empty($sqltbl['group_sql'])) {
            $sql .= ' GROUP BY ' . $sqltbl['group_sql'];
        }
        if (!empty($sqltbl['having_sql'])) {
            $sql .= ' HAVING ' . $sqltbl['having_sql'];
        }

        //$sql .= ($this->random > 0) ? " ORDER BY RAND()" : " ORDER BY " . $this->order_by; // MYSQL would want file for order by
        //if ($this->limit > 0) { // FIXME MYSQL 'This version of MariaDB doesn't yet support 'LIMIT & IN/ALL/ANY/SOME subquery''
        //    $sql .= " LIMIT " . (string)($this->limit);
        //}
        //debug_event(self::class, 'SQL get_subsearch: ' . $sql . "\n" . print_r($sqltbl['parameters'], true), 5);

        return array(
            'sql' => $sql,
            'parameters' => $sqltbl['parameters']
        );
    }

    /**
     * set_last
     *
     * @param integer $count
     * @param string $column
     */
    private function set_last($count, $column)
    {
        if (in_array($column, array('last_count', 'last_duration'))) {
            $search_id = Dba::escape($this->id);
            $sql       = "UPDATE `search` SET `" . Dba::escape($column) . "` = ? WHERE `id` = ?";
            Dba::write($sql, array($count, $search_id));
        }
    }

    /**
     * get_random_items
     *
     * Returns a randomly sorted array (with an optional limit) of the items
     * output by our search (part of the playlist interface)
     * @param integer $limit
     * @return array
     */
    public function get_random_items($limit = null)
    {
        $results = array();

        $sqltbl = $this->to_sql();
        $sql    = $sqltbl['base'] . ' ' . $sqltbl['table_sql'];
        if (!empty($sqltbl['where_sql'])) {
            $sql .= ' WHERE ' . $sqltbl['where_sql'];
        }
        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && !empty(Core::get_global('user')) && Core::get_global('user')->id > 0) {
            $user_id = Core::get_global('user')->id;
            $sql .= (empty($sqltbl['where_sql']))
                ? " WHERE "
                : " AND ";
            $sql .= "`" . $this->objectType . "`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '" . $this->objectType . "' AND `rating`.`rating` <=$rating_filter AND `rating`.`user` = $user_id)";
        }
        if (!empty($sqltbl['group_sql'])) {
            $sql .= ' GROUP BY ' . $sqltbl['group_sql'];
        }
        if (!empty($sqltbl['having_sql'])) {
            $sql .= ' HAVING ' . $sqltbl['having_sql'];
        }

        $sql .= " ORDER BY RAND()";
        $sql .= ($limit)
            ? " LIMIT " . (string) ($limit)
            : "";
        //debug_event(self::class, 'SQL get_random_items: ' . $sql . "\n" . print_r($sqltbl['parameters'], true), 5);

        $db_results = Dba::read($sql, $sqltbl['parameters']);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array(
                'object_id' => $row['id'],
                'object_type' => $this->objectType
            );
        }

        return $results;
    }

    /**
     * get_total_duration
     * Get the total duration of all songs.
     * @param array $songs
     * @return integer
     */
    public static function get_total_duration($songs)
    {
        $song_ids = array();
        foreach ($songs as $objects) {
            $song_ids[] = (string)$objects['object_id'];
        }
        $idlist = '(' . implode(',', $song_ids) . ')';
        if ($idlist == '()') {
            return 0;
        }
        $sql = "SELECT SUM(`time`) FROM `song` WHERE `id` IN $idlist";

        $db_results = Dba::read($sql);
        $row        = Dba::fetch_row($db_results);

        return (int)($row[0] ?? 0);
    } // get_total_duration

    /**
     * _get_rule_name
     *
     * Iterate over $this->types to validate the rule name and return the rule type
     * (text, date, etc)
     * @param string $name
     * @return string
     */
    private function _get_rule_name($name)
    {
        // check that the rule you sent is not an alias (needed for pulling details from the rule)
        switch ($this->objectType) {
            case 'song':
                switch ($name) {
                    case 'name':
                    case 'song':
                    case 'song_title':
                        $name = 'title';
                        break;
                    case 'album_title':
                        $name = 'album';
                        break;
                    case 'album_artist_title':
                        $name = 'album_artist';
                        break;
                    case 'song_artist_title':
                        $name = 'song_artist';
                        break;
                    case 'tag':
                    case 'song_tag':
                    case 'song_genre':
                        $name = 'genre';
                        break;
                    case 'album_tag':
                        $name = 'album_genre';
                        break;
                    case 'artist_tag':
                        $name = 'artist_genre';
                        break;
                    case 'no_tag':
                        $name = 'no_genre';
                        break;
                    case 'mbid_song':
                        $name = 'mbid';
                        break;
                    default:
                        break;
                }
                break;
            case 'album':
            case 'album_disk':
                switch ($name) {
                    case 'name':
                    case 'album_title':
                        $name = 'title';
                        break;
                    case 'song_title':
                        $name = 'song';
                        break;
                    case 'album_artist':
                    case 'album_artist_title':
                        $name = 'artist';
                        break;
                    case 'tag':
                    case 'album_tag':
                    case 'album_genre':
                        $name = 'genre';
                        break;
                    case 'song_tag':
                        $name = 'song_genre';
                        break;
                    case 'no_tag':
                        $name = 'no_genre';
                        break;
                    case 'mbid_album':
                        $name = 'mbid';
                        break;
                    case 'possible_duplicate_album':
                        $name = 'possible_duplicate';
                        break;
                    case 'release_comment':
                        $name = 'subtitle';
                        break;
                    default:
                        break;
                }
                break;
            case 'artist':
                switch ($name) {
                    case 'name':
                    case 'artist_title':
                        $name = 'title';
                        break;
                    case 'album_title':
                        $name = 'album';
                        break;
                    case 'song_title':
                        $name = 'song';
                        break;
                    case 'tag':
                    case 'artist_tag':
                    case 'artist_genre':
                        $name = 'genre';
                        break;
                    case 'song_tag':
                        $name = 'song_genre';
                        break;
                    case 'no_tag':
                        $name = 'no_genre';
                        break;
                    case 'mbid_artist':
                        $name = 'mbid';
                        break;
                    default:
                        break;
                }
                break;
            case 'podcast':
                switch ($name) {
                    case 'name':
                        $name = 'title';
                        break;
                    case 'podcast_episode_title':
                        $name = 'podcast_episode';
                        break;
                    default:
                        break;
                }
                break;
            case 'podcast_episode':
                switch ($name) {
                    case 'name':
                        $name = 'title';
                        break;
                    case 'podcast_title':
                        $name = 'podcast';
                        break;
                    default:
                        break;
                }
                break;
            case 'genre':
            case 'tag':
            case 'label':
            case 'playlist':
                switch ($name) {
                    case 'name':
                        $name = 'title';
                        break;
                    default:
                        break;
                }
                break;
        }
        //debug_event(self::class, '__get_rule_name: ' . $name, 5);

        return $name;
    }

    /**
     * get_rule_type
     *
     * Iterate over $this->types to validate the rule name and return the rule type
     * (text, date, etc)
     * @param string $name
     * @return string|false
     */
    public function get_rule_type($name)
    {
        //debug_event(self::class, 'get_rule_type: ' . $name, 5);
        foreach ($this->types as $type) {
            if ($type['name'] == $name) {
                return $type['type'];
            }
        }

        return false;
    }

    /**
     * set_rules
     *
     * Takes an array of sanitized search data from the form and generates our real array from it.
     * @param array $data
     */
    public function set_rules($data)
    {
        $data        = self::_filter_request($data);
        $this->rules = array();
        $user_rules  = array();
        // check that a limit or random flag and operator have been sent
        $this->random         = (isset($data['random'])) ? (int) $data['random'] : $this->random;
        $this->limit          = (isset($data['limit'])) ? (int) $data['limit'] : $this->limit;
        $this->logic_operator = $data['operator'] ?? 'AND';
        // match the numeric rules you send (e.g. rule_1, rule_6000)
        foreach ($data as $rule => $value) {
            if (preg_match('/^rule_(\d+)$/', $rule, $ruleID)) {
                $user_rules[] = $ruleID[1];
            }
        }
        // get the data for each rule group the user sent
        foreach ($user_rules as $ruleID) {
            $rule_name     = $this->_get_rule_name($data["rule_" . $ruleID]);
            $rule_type     = $this->get_rule_type($rule_name);
            $rule_input    = (string)($data['rule_' . $ruleID . '_input'] ?? '');
            $rule_operator = $this->basetypes[$rule_type][$data['rule_' . $ruleID . '_operator']]['name'] ?? '';
            // keep vertical bar in regular expression
            $is_regex = in_array($rule_operator, ['regexp', 'notregexp']);
            if ($is_regex) {
                $rule_input = str_replace("|", "\0", $rule_input);
            }
            // attach the rules to the search
            foreach (explode('|', $rule_input) as $input) {
                $this->rules[] = array(
                    $rule_name, // name
                    $rule_operator, // operator
                    ($is_regex) ? str_replace("\0", "|", $input) : $input, // input
                    $data['rule_' . $ruleID . '_subtype'] ?? null, // subtype
                );
            }
        }
    }

    /**
     * create
     *
     * Save this search to the database for use as a smart playlist
     * @return string|null
     */
    public function create()
    {
        $user = Core::get_global('user');
        // Make sure we have a unique name
        if (!$this->name) {
            $this->name = $user->username . ' - ' . get_datetime(time());
        }
        $sql        = "SELECT `id` FROM `search` WHERE `name` = ? AND `user` = ? AND `type` = ?;";
        $db_results = Dba::read($sql, array($this->name, $user->id, $this->type));
        if (Dba::num_rows($db_results)) {
            $this->name .= uniqid('', true);
        }

        $sql = "INSERT INTO `search` (`name`, `type`, `user`, `username`, `rules`, `logic_operator`, `random`, `limit`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array(
            $this->name,
            $this->type,
            $user->id,
            $user->username,
            json_encode($this->rules),
            $this->logic_operator,
            ($this->random > 0) ? 1 : 0,
            $this->limit
        ));
        $insert_id = Dba::insert_id();
        $this->id  = (int)$insert_id;
        Catalog::count_table('search');

        return $insert_id;
    }

    /**
     * to_js
     *
     * Outputs the javascript necessary to re-show the current set of rules.
     * @return string
     */
    public function to_js()
    {
        $javascript = "";
        foreach ($this->rules as $rule) {
            $javascript .= '<script>' . 'SearchRow.add("' . $rule[0] . '","' . $rule[1] . '","' . $rule[2] . '", "' . $rule[3] . '"); </script>';
        }

        return $javascript;
    }

    /**
     * to_sql
     *
     * Call the appropriate real function.
     * @return array
     */
    public function to_sql()
    {
        return $this->searchType->getSql($this);
    }

    /**
     * update
     *
     * This function updates the saved search with the current settings.
     * @param array|null $data
     * @return integer
     */
    public function update(array $data = null)
    {
        if ($data && is_array($data)) {
            $this->name   = $data['name'] ?? $this->name;
            $this->type   = $data['pl_type'] ?? $this->type;
            $this->user   = $data['pl_user'] ?? $this->user;
            $this->random = ((int)($data['random'] ?? 0) > 0) ? 1 : 0;
            $this->limit  = $data['limit'] ?? $this->limit;
        }
        $this->username = User::get_username($this->user);

        if (!$this->id) {
            return 0;
        }

        $sql = "UPDATE `search` SET `name` = ?, `type` = ?, `user` = ?, `username` = ?, `rules` = ?, `logic_operator` = ?, `random` = ?, `limit` = ? WHERE `id` = ?";
        Dba::write($sql, array(
            $this->name,
            $this->type,
            $this->user,
            $this->username,
            json_encode($this->rules),
            $this->logic_operator,
            $this->random,
            $this->limit,
            $this->id
        ));
        // reformat after an update
        $this->format();

        return $this->id;
    }

    /**
     * filter_data
     *
     * Private convenience function.  Mangles the input according to a set
     * of predefined rules so that we don't have to include this logic in
     * _get_sql_foo.
     * @param array|string $data
     * @param string|false $type
     * @param array $operator
     * @return array|boolean|integer|string|string[]|null
     */
    public function filter_data($data, $type, $operator)
    {
        if (array_key_exists('preg_match', $operator)) {
            $data = preg_replace($operator['preg_match'], $operator['preg_replace'], $data);
        }

        if ($type == 'numeric' || $type == 'days') {
            return (int)($data);
        }

        if ($type == 'boolean') {
            return make_bool($data);
        }

        return $data;
    }

    /**
     * year_search
     *
     * Build search rules for year -> year album searches for subsonic.
     * @param $fromYear
     * @param $toYear
     * @param int $size
     * @param int $offset
     * @return array
     */
    public static function year_search($fromYear, $toYear, $size, $offset)
    {
        $search           = array();
        $search['limit']  = $size;
        $search['offset'] = $offset;
        $search['type']   = "album";
        if ($fromYear) {
            $search['rule_0_input']    = $fromYear;
            $search['rule_0_operator'] = 0;
            $search['rule_0']          = "original_year";
        }
        if ($toYear) {
            $search['rule_1_input']    = $toYear;
            $search['rule_1_operator'] = 1;
            $search['rule_1']          = "original_year";
        }

        return $search;
    }

    /**
     * @deprecated
     */
    private function getLicenseRepository(): LicenseRepositoryInterface
    {
        global $dic;

        return $dic->get(LicenseRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
