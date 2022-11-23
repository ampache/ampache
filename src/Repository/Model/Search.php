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

use Ampache\Module\Authorization\Access;
use Ampache\Repository\Model\Metadata\Repository\MetadataField;
use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Search-related voodoo.  Beware tentacles.
 */
class Search extends playlist_object
{
    protected const DB_TABLENAME = 'search';
    public const VALID_TYPES     = array('song', 'album', 'song_artist', 'album_artist', 'artist', 'genre', 'label', 'playlist', 'podcast', 'podcast_episode', 'tag', 'user', 'video');

    public $searchType;
    public $objectType;
    public $rules          = array(); // rules used to actually search
    public $logic_operator = 'AND';
    public $type           = 'public';
    public $random         = 0;
    public $limit          = 0;
    public $last_count     = 0;
    public $last_duration  = 0;
    public $date           = 0;

    public $basetypes;
    public $types; // rules that are available to the objectType

    public $search_user;

    private $stars;
    private $order_by;

    /**
     * constructor
     * @param integer $search_id
     * @param string $searchType
     * @param User|null $user
     */
    public function __construct($search_id = 0, $searchType = 'song', ?User $user = null)
    {
        if ($user !== null) {
            $this->search_user = $user;
        } else {
            $this->search_user = Core::get_global('user');
        }
        //debug_event(self::class, "SearchID: $search_id; Search Type: $searchType\n" . print_r($this, true), 5);
        $searchType       = (in_array(strtolower($searchType), self::VALID_TYPES))
            ? strtolower($searchType)
            : 'song';
        $this->searchType = $searchType;
        $this->objectType = $searchType;
        if ($search_id > 0) {
            $info = $this->get_info($search_id);
            foreach ($info as $key => $value) {
                $this->$key = ($key == 'rules')
                    ? json_decode((string)$value, true)
                    : $value;
            }
            // make sure saved rules match the correct names
            $rule_count = 0;
            foreach ($this->rules as $rule) {
                $this->rules[$rule_count][0] = $this->_get_rule_name($rule[0]);
                $rule_count++;
            }
        }
        $this->date = time();

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

        $this->types = array();
        switch ($searchType) {
            case 'song':
                $this->_set_types_song();
                $this->order_by = '`song`.`file`';
                break;
            case 'album':
                $this->_set_types_album();
                $this->order_by = (AmpConfig::get('album_group')) ? '`album`.`name`' : '`album`.`name`, `album`.`disk`';
                break;
            case 'video':
                $this->_set_types_video();
                $this->order_by = '`video`.`file`';
                break;
            case 'album_artist':
            case 'song_artist':
                $this->_set_types_artist();
                $this->order_by   = '`artist`.`name`';
                $this->objectType = 'artist';
                break;
            case 'artist':
                $this->_set_types_artist();
                $this->order_by = '`artist`.`name`';
                break;
            case 'playlist':
                $this->_set_types_playlist();
                $this->order_by = '`playlist`.`name`';
                break;
            case 'podcast':
                $this->_set_types_podcast();
                $this->order_by = '`podcast`.`title`';
                break;
            case 'podcast_episode':
                $this->_set_types_podcast_episode();
                $this->order_by = '`podcast_episode`.`pubdate` DESC';
                break;
            case 'label':
                $this->_set_types_label();
                $this->order_by = '`label`.`name`';
                break;
            case 'user':
                $this->_set_types_user();
                $this->order_by = '`user`.`username`';
                break;
            case 'tag':
            case 'genre':
                $this->_set_types_tag();
                $this->order_by = '`tag`.`name`';
                break;
        } // end switch on searchType
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
     * this is where all the searchTypes for songs are defined
     */
    private function _set_types_song()
    {
        $user_id = $this->search_user->id ?? 0;
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
        $playlists   = Playlist::get_playlist_array($user_id);
        if (!empty($playlists)) {
            $this->_add_type_select('playlist', T_('Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }
        $playlists = self::get_search_array($user_id);
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
        $this->_add_type_boolean('possible_duplicate_album', T_('Possible Duplicate Albums'), 'is_true', $t_file_data);
        $this->_add_type_boolean('orphaned_album', T_('Orphaned Album'), 'is_true', $t_file_data);
        $catalogs = array();
        foreach (Catalog::get_catalogs('music', $user_id) as $catid) {
            $catalog = Catalog::create_from_id($catid);
            $catalog->format();
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
     * this is where all the searchTypes for artists are defined
     */
    private function _set_types_artist()
    {
        $user_id       = $this->search_user->id ?? 0;
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
        $playlists   = Playlist::get_playlist_array($user_id);
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
        foreach (Catalog::get_catalogs('music', $user_id) as $catid) {
            $catalog = Catalog::create_from_id($catid);
            $catalog->format();
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
     * this is where all the searchTypes for albums are defined
     */
    private function _set_types_album()
    {
        $user_id      = $this->search_user->id ?? 0;
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
        $playlists   = Playlist::get_playlist_array($user_id);
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
        $this->_add_type_boolean('duplicate_mbid_group', T_('Duplicate MusicBrainz Release Group'), 'is_true', $t_file_data);
        $this->_add_type_numeric('recent_added', T_('Recently added'), 'recent_added', $t_file_data);
        $catalogs = array();
        foreach (Catalog::get_catalogs('music', $user_id) as $catid) {
            $catalog = Catalog::create_from_id($catid);
            $catalog->format();
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
     * this is where all the searchTypes for videos are defined
     */
    private function _set_types_video()
    {
        $this->_add_type_text('file', T_('Filename'));
    }

    /**
     * _set_types_playlist
     *
     * this is where all the searchTypes for playlists are defined
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
     * this is where all the searchTypes for podcasts are defined
     */
    private function _set_types_podcast()
    {
        $t_podcasts = T_('Podcast');
        $this->_add_type_text('title', T_('Name'), $t_podcasts);

        $t_podcast_episodes = T_('Podcast Episodes');
        $this->_add_type_text('podcast_episode', T_('Podcast Episode'), $t_podcast_episodes);
        $episode_states = array(
            0 => T_('skipped'),
            1 => T_('pending'),
            2 => T_('completed')
        );
        $this->_add_type_select('state', T_('State'), 'boolean_numeric', $episode_states, $t_podcast_episodes);
        $this->_add_type_numeric('time', T_('Length (in minutes)'), 'numeric', $t_podcast_episodes);

        $t_file_data = T_('File Data');
        $this->_add_type_text('file', T_('Filename'), $t_file_data);
        $this->_add_type_date('pubdate', T_('Publication Date'), $t_file_data);
        $this->_add_type_date('added', T_('Added'), $t_file_data);
    }

    /**
     * _set_types_podcast_episode
     *
     * this is where all the searchTypes for podcast_episodes are defined
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

        $t_file_data = T_('File Data');
        $this->_add_type_text('file', T_('Filename'), $t_file_data);
        $this->_add_type_date('pubdate', T_('Publication Date'), $t_file_data);
        $this->_add_type_date('added', T_('Added'), $t_file_data);
    }

    /**
     * _set_types_label
     *
     * this is where all the searchTypes for labels are defined
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
     * this is where all the searchTypes for users are defined
     */
    private function _set_types_user()
    {
        $this->_add_type_text('username', T_('Username'));
    }

    /**
     * _set_types_tag
     *
     * this is where all the searchTypes for Genres are defined
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
        //Search::VALID_TYPES = array('song', 'album', 'song_artist', 'album_artist', 'artist', 'label', 'playlist', 'podcast', 'podcast_episode', 'tag', 'user', 'video')
        switch ($search_type) {
            case 'song':
            case 'album':
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
     * get_name_byid
     *
     * Returns the name of the saved search corresponding to the given ID
     * @param string $search_id
     * @return string
     */
    public static function get_name_byid($search_id)
    {
        $sql        = "SELECT `name` FROM `search` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($search_id));
        $row        = Dba::fetch_assoc($db_results);

        return $row['name'];
    }

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
        $search_id = Dba::escape($this->id);
        $sql       = "DELETE FROM `search` WHERE `id` = ?";
        Dba::write($sql, array($search_id));
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
        parent::format();
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
            if (empty($sqltbl['where_sql'])) {
                $sql .= " WHERE ";
            } else {
                $sql .= " AND ";
            }
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
     * _get_rule_type
     *
     * Iterate over $this->types to validate the rule name and return the rule type
     * (text, date, etc)
     * @param string $name
     * @return string|false
     */
    private function _get_rule_type($name)
    {
        //debug_event(self::class, '_get_rule_type: ' . $name, 5);
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
            $rule_type     = $this->_get_rule_type($rule_name);
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
                    $rule_name,
                    $rule_operator,
                    ($is_regex) ? str_replace("\0", "|", $input) : $input,
                    $data['rule_' . $ruleID . '_subtype'] ?? null
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
        return call_user_func(array($this, '_get_sql_' . $this->searchType));
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
     * _filter_input
     *
     * Private convenience function.  Mangles the input according to a set
     * of predefined rules so that we don't have to include this logic in
     * _get_sql_foo.
     * @param array|string $data
     * @param string|false $type
     * @param array $operator
     * @return array|boolean|integer|string|string[]|null
     */
    private function _filter_input($data, $type, $operator)
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
     * _get_sql_album
     *
     * Handles the generation of the SQL for album searches.
     * @return array
     */
    private function _get_sql_album()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id ?? 0;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $parameters  = array();
        $join['tag'] = array();
        $groupdisks  = AmpConfig::get('album_group');

        foreach ($this->rules as $rule) {
            $type     = $this->_get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_filter_input($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'] ?? '';
            if ($groupdisks) {
                /** 'album_group' DEFAULT:
                 * `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group`
                 */
                $group[] = "`album`.`prefix`";
                $group[] = "`album`.`name`";
                $group[] = "`album`.`album_artist`";
                $group[] = "`album`.`release_type`";
                $group[] = "`album`.`release_status`";
                $group[] = "`album`.`mbid`";
                $group[] = "`album`.`year`";
                $group[] = "`album`.`original_year`";
                $group[] = "`album`.`mbid_group`";
            } else {
                $group[] = "`album`.`id`";
                $group[] = "`album`.`disk`";
            }
            switch ($rule[0]) {
                case 'title':
                    $where[]    = "(`album`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator ?)";
                    $parameters = array_merge($parameters, array($input, $input));
                    break;
                case 'year':
                case 'release_type':
                case 'release_status':
                case 'catalog':
                    $where[]      = "`album`.`" . $rule[0] . "` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'original_year':
                    $where[]    = "(`album`.`original_year` $sql_match_operator ? OR (`album`.`original_year` IS NULL AND `album`.`year` $sql_match_operator ?))";
                    $parameters = array_merge($parameters, array($input, $input));
                    break;
                case 'time':
                    $input        = $input * 60;
                    $where[]      = "`album`.`time` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "`average_rating`.`avg` $sql_match_operator ?";
                    $parameters[]     = $input;
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='album' GROUP BY `object_id`) AS `average_rating` ON `average_rating`.`object_id` = `album`.`id` ";
                    break;
                case 'favorite':
                    $where[]    = "(`album`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator ?) AND `favorite_album_$user_id`.`user` = $user_id AND `favorite_album_$user_id`.`object_type` = 'album'";
                    $parameters = array_merge($parameters, array($input, $input));
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_album_$user_id"))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $user_id) AS `favorite_album_$user_id` ON `album`.`id` = `favorite_album_$user_id`.`object_id` AND `favorite_album_$user_id`.`object_type` = 'album'"
                        : "";
                    break;
                case 'myrating':
                case 'artistrating':
                    // combine these as they all do the same thing just different tables
                    $looking = str_replace('rating', '', $rule[0]);
                    $column  = ($looking == 'my') ? '`album`.`id`' : '`album_map`.`object_id`';
                    $my_type = ($looking == 'my') ? 'album' : $looking;
                    if ($input == 0 && $sql_match_operator == '>=') {
                        break;
                    }
                    if ($input == 0 && $sql_match_operator == '<') {
                        $input              = -1;
                        $sql_match_operator = '<=>';
                    }
                    if ($input == 0 && $sql_match_operator == '<>') {
                        $input              = 1;
                        $sql_match_operator = '>=';
                    }
                    if (($input == 0 && $sql_match_operator != '>') || ($input == 1 && $sql_match_operator == '<')) {
                        $where[] = "`rating_" . $my_type . "_" . $user_id . "`.`rating` IS NULL";
                    } elseif (in_array($sql_match_operator, array('<>', '<', '<=', '!='))) {
                        $where[]      = "(`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator ? OR `rating_" . $my_type . "_" . $user_id . "`.`rating` IS NULL)";
                        $parameters[] = $input;
                    } else {
                        $where[]      = "`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator ?";
                        $parameters[] = $input;
                    }
                    // rating once per user
                    if (!array_key_exists('rating', $table)) {
                        $table['rating'] = '';
                    }
                    $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = $user_id AND `object_type`='$my_type') AS `rating_" . $my_type . "_" . $user_id . "` ON `rating_" . $my_type . "_" . $user_id . "`.`object_id` = $column"
                        : "";
                    if ($my_type == 'artist') {
                        $join['album_map'] = true;
                    }
                    break;
                case 'songrating':
                    if ($input == 0 && $sql_match_operator == '>=') {
                        break;
                    }
                    if ($input == 0 && $sql_match_operator == '<') {
                        $input              = -1;
                        $sql_match_operator = '<=>';
                    }
                    if ($input == 0 && $sql_match_operator == '<>') {
                        $input              = 1;
                        $sql_match_operator = '>=';
                    }
                    if (($input == 0 && $sql_match_operator != '>') || ($input == 1 && $sql_match_operator == '<')) {
                        $where[] = "`album`.`id` IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` NOT IN (SELECT `object_id` FROM `rating` WHERE `user` = $user_id AND `object_type`='song')))";
                    } elseif (in_array($sql_match_operator, array('<>', '<', '<=', '!='))) {
                        $where[]      = "`album`.`id` IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = $user_id AND `object_type`='song' AND `rating` $sql_match_operator ?))) OR `album`.`id` NOT IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = $user_id AND `object_type`='song')))";
                        $parameters[] = $input;
                    } else {
                        $where[]      = "`album`.`id` IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = $user_id AND `object_type`='song' AND `rating` $sql_match_operator ?)))";
                        $parameters[] = $input;
                    }
                    break;
                case 'myplayed':
                case 'myplayedartist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('myplayed', '', $rule[0]);
                    $column       = ($looking == 'artist') ? 'album_artist' : 'id';
                    $my_type      = ($looking == 'artist') ? 'artist' : 'album';
                    $operator_sql = ((int)$sql_match_operator == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $user_id . "` ON `album`.`$column` = `myplayed_" . $my_type . "_" . $user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`myplayed_" . $my_type . "_" . $user_id . "`.`object_id` $operator_sql";
                    break;
                case 'played':
                    $column       = 'id';
                    $my_type      = 'album';
                    $operator_sql = ((int)$sql_match_operator == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('played', $table)) {
                        $table['played'] = '';
                    }
                    $table['played'] .= (!strpos((string) $table['played'], "played_" . $my_type))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' GROUP BY `object_id`, `object_type`, `user`) AS `played_" . $my_type . "` ON `album`.`$column` = `played_" . $my_type . "`.`object_id` AND `played_" . $my_type . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`played_" . $my_type . "`.`object_id` $operator_sql";
                    break;
                case 'last_play':
                    $my_type = 'album';
                    if (!array_key_exists('last_play', $table)) {
                        $table['last_play'] = '';
                    }
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $user_id . "` ON `album`.`id` = `last_play_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[]      = "`last_play_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - (? * 86400))";
                    $parameters[] = $input;
                    break;
                case 'last_skip':
                    $my_type = 'album';
                    if (!array_key_exists('last_skip', $table)) {
                        $table['last_skip'] = '';
                    }
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id` = `last_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_skip_" . $my_type . "_" . $user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`last_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - (? * 86400))";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'last_play_or_skip':
                    $my_type = 'album';
                    if (!array_key_exists('last_play_or_skip', $table)) {
                        $table['last_play_or_skip'] = '';
                    }
                    $table['last_play_or_skip'] .= (!strpos((string) $table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id` = `last_play_or_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_or_skip_" . $my_type . "_" . $user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`last_play_or_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - (? * 86400))";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'played_times':
                    if ($groupdisks) {
                        $table['play_count'] = "LEFT JOIN (SELECT MIN(`album`.`id`) AS `id`, SUM(`total_count`) AS `total_count` FROM `album` GROUP BY `album`.`prefix`,`album`.`name`,`album`.`album_artist`,`album`.`release_type`,`album`.`release_status`,`album`.`mbid`,`album`.`year`,`album`.`original_year`,`album`.`mbid_group`,`album`.`prefix`,`album`.`name`,`album`.`album_artist`,`album`.`release_type`,`album`.`release_status`,`album`.`mbid`,`album`.`year`,`album`.`original_year`,`album`.`mbid_group`) AS `album_total_count` ON `album`.`id` = `album_total_count`.`id`";
                        $where[]             = "(`album_total_count`.`total_count` $sql_match_operator ?)";
                    } else {
                        $where[] = "(`album`.`total_count` $sql_match_operator ?)";
                    }
                    $parameters[]        = $input;
                    break;
                case 'song_count':
                    if ($groupdisks) {
                        $table['play_count'] = "LEFT JOIN (SELECT MIN(`album`.`id`) AS `id`, SUM(`song_count`) AS `song_count` FROM `album` GROUP BY `album`.`prefix`,`album`.`name`,`album`.`album_artist`,`album`.`release_type`,`album`.`release_status`,`album`.`mbid`,`album`.`year`,`album`.`original_year`,`album`.`mbid_group`,`album`.`prefix`,`album`.`name`,`album`.`album_artist`,`album`.`release_type`,`album`.`release_status`,`album`.`mbid`,`album`.`year`,`album`.`original_year`,`album`.`mbid_group`) AS `album_song_count` ON `album`.`id` = `album_song_count`.`id`";
                        $where[]             = "(`album_song_count`.`song_count` $sql_match_operator ?)";
                    } else {
                        $where[]      = "(`album`.`song_count` $sql_match_operator ?)";
                    }
                    $parameters[] = $input;
                    break;
                case 'other_user':
                    $other_userid = $input;
                    if ($sql_match_operator == 'userflag') {
                        $where[] = "`favorite_album_$other_userid`.`user` = $other_userid AND `favorite_album_$other_userid`.`object_type` = 'album'";
                        // flag once per user
                        if (!array_key_exists('favorite', $table)) {
                            $table['favorite'] = '';
                        }
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_album_$other_userid"))
                            ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $other_userid) AS `favorite_album_$other_userid` ON `album`.`id` = `favorite_album_$other_userid`.`object_id` AND `favorite_album_$other_userid`.`object_type` = 'album'"
                            : "";
                    } else {
                        $column  = 'id';
                        $my_type = 'album';
                        $where[] = "`rating_album_" . $other_userid . '`.' . $sql_match_operator . " AND `rating_album_$other_userid`.`user` = $other_userid AND `rating_album_$other_userid`.`object_type` = 'album'";
                        // rating once per user
                        if (!array_key_exists('rating', $table)) {
                            $table['rating'] = '';
                        }
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $user_id))
                            ? "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $user_id . "` ON `rating_" . $my_type . "_" . $user_id . "`.`object_type`='$my_type' AND `rating_" . $my_type . "_" . $user_id . "`.`object_id` = `$my_type`.`$column` AND `rating_" . $my_type . "_" . $user_id . "`.`user` = $user_id "
                            : "";
                    }
                    break;
                case 'recent_played':
                    $key                     = md5($input . $sql_match_operator);
                    $where[]                 = "`played_$key`.`object_id` IS NOT NULL";
                    $table['played_' . $key] = "LEFT JOIN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'album' ORDER BY $sql_match_operator DESC LIMIT " . (int)$input . ") AS `played_$key` ON `album`.`id` = `played_$key`.`object_id`";
                    break;
                case 'recent_added':
                    $key                       = md5($input . $sql_match_operator);
                    $where[]                   = "`addition_time_$key`.`id` IS NOT NULL";
                    $table['addition_' . $key] = "LEFT JOIN (SELECT `id` FROM `album` ORDER BY $sql_match_operator DESC LIMIT $input) AS `addition_time_$key` ON `album`.`id` = `addition_time_$key`.`id`";
                    break;
                case 'genre':
                    $where[]      = "`album`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator ? WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)";
                    $parameters[] = $input;
                    break;
                case 'no_genre':
                    $where[] = "`album`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)";
                    break;
                case 'song_genre':
                    $where[]      = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'playlist_name':
                    $where[]      = "`album`.`id` IN (SELECT `song`.`album` FROM `playlist_data` LEFT JOIN `playlist` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' WHERE `playlist`.`name` $sql_match_operator ?)";
                    $parameters[] = $input;
                    break;
                case 'playlist':
                    $where[]      = "`album`.`id` $sql_match_operator IN (SELECT `song`.`album` FROM `playlist_data` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' WHERE `playlist_data`.`playlist` = ?)";
                    $parameters[] = $input;
                    break;
                case 'file':
                    $where[]      = "`song`.`file` $sql_match_operator ?";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'has_image':
                    $where[]            = ($sql_match_operator == '1') ? "`has_image`.`object_id` IS NOT NULL" : "`has_image`.`object_id` IS NULL";
                    $table['has_image'] = "LEFT JOIN (SELECT `object_id` FROM `image` WHERE `object_type` = 'album') AS `has_image` ON `album`.`id` = `has_image`.`object_id`";
                    break;
                case 'image_height':
                case 'image_width':
                    $looking       = strpos($rule[0], "image_") ? str_replace('image_', '', $rule[0]) : str_replace('image ', '', $rule[0]);
                    $where[]       = "`image`.`$looking` $sql_match_operator ?";
                    $parameters[]  = $input;
                    $join['image'] = true;
                    break;
                case 'artist':
                case 'album_artist':
                    $where[]           = "((`artist`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator ?) AND `album_map`.`object_type` = 'album')";
                    $parameters        = array_merge($parameters, array($input, $input));
                    $join['album_map'] = true;
                    break;
                case 'song':
                    $where[]      = "`song`.`title` $sql_match_operator ?";
                    $parameters   = array_merge($parameters, array($input));
                    $join['song'] = true;
                    break;
                case 'song_artist':
                    $where[]           = "((`artist`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator ?) AND `album_map`.`object_type` = 'song')";
                    $parameters        = array_merge($parameters, array($input, $input));
                    $join['album_map'] = true;
                    break;
                case 'mbid':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($sql_match_operator, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`album`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`album`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]      = "`album`.`mbid` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'mbid_song':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($sql_match_operator, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`song`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`song`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]      = "`song`.`mbid` $sql_match_operator ?";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'mbid_artist':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($sql_match_operator, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`artist`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`artist`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]           = "`artist`.`mbid` $sql_match_operator ?";
                    $parameters[]      = $input;
                    $join['album_map'] = true;
                    break;
                case 'possible_duplicate':
                    $where[]               = "(`dupe_search1`.`dupe_id1` IS NOT NULL OR `dupe_search2`.`dupe_id2` IS NOT NULL)";
                    $table['dupe_search1'] = "LEFT JOIN (SELECT MIN(`id`) AS `dupe_id1`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk`, `year`, `release_type`, `release_status` HAVING `Counting` > 1) AS `dupe_search1` ON `album`.`id` = `dupe_search1`.`dupe_id1`";
                    $table['dupe_search2'] = "LEFT JOIN (SELECT MAX(`id`) AS `dupe_id2`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk`, `year`, `release_type`, `release_status` HAVING `Counting` > 1) AS `dupe_search2` ON `album`.`id` = `dupe_search2`.`dupe_id2`";
                    break;
                case 'duplicate_mbid_group':
                    $where[] = "`mbid_group` IN (SELECT `mbid_group` FROM `album` WHERE `disk` = 1 GROUP BY `mbid_group`, `disk` HAVING COUNT(`mbid_group`) > 1)";
                    break;
                default:
                    break;
            } // switch on ruletype album
        } // foreach rule

        $join['song']        = array_key_exists('song', $join);
        $join['catalog']     = $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if (array_key_exists('album_map', $join)) {
            $table['0_album_map'] = "LEFT JOIN `album_map` ON `album`.`id` = `album_map`.`album_id`";
            $table['artist']      = "LEFT JOIN `artist` ON `artist`.`id` = `album_map`.`object_id`";
        }
        if ($join['song']) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`album` = `album`.`id`";
        }
        if ($join['catalog']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_album` ON `catalog_map_album`.`object_type` = 'album' AND `catalog_map_album`.`object_id` = `album`.`id`";
            $table['3_catalog']     = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `catalog_map_album`.`catalog_id`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1'";
            } else {
                $where_sql = "`catalog_se`.`enabled` = '1'";
            }
        }
        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        if (array_key_exists('count', $join)) {
            $table['object_count'] = "LEFT JOIN (SELECT `object_count`.`object_id`, MAX(`object_count`.`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`user`='" . $user_id . "' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` ON `object_count`.`object_id` = `album`.`id`";
        }
        if (array_key_exists('image', $join)) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`album` = `album`.`id` LEFT JOIN `image` ON `image`.`object_id` = `album`.`id`";
            $where_sql       = "(" . $where_sql . ") AND `image`.`object_type`='album' AND `image`.`size`='original'";
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => ($groupdisks) ? 'SELECT MIN(`album`.`id`) AS `id`, MAX(`album`.`disk`) AS `disk` FROM `album`' : 'SELECT `album`.`id` AS `id` FROM `album`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql,
            'parameters' => $parameters
        );
    }

    /**
     * _get_sql_song_artist
     *
     * Handles the generation of the SQL for song_artist searches.
     * @return array
     */
    private function _get_sql_song_artist()
    {
        return self::_get_sql_artist();
    }

    /**
     * _get_sql_album_artist
     *
     * Handles the generation of the SQL for album_artist searches.
     * @return array
     */
    private function _get_sql_album_artist()
    {
        return self::_get_sql_artist();
    }

    /**
     * _get_sql_artist
     *
     * Handles the generation of the SQL for artist searches.
     * @return array
     */
    private function _get_sql_artist()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id ?? 0;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');
        $album_artist       = ($this->searchType == 'album_artist');
        $song_artist        = ($this->searchType == 'song_artist');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $parameters  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->_get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_filter_input($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'title':
                    $where[]    = "(`artist`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator ?)";
                    $parameters = array_merge($parameters, array($input, $input));
                    break;
                case 'yearformed':
                case 'placeformed':
                    $where[]      = "`artist`.`$rule[0]` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'time':
                    $input        = $input * 60;
                    $where[]      = "`artist`.`time` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'genre':
                    $where[]      = "`artist`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator ? WHERE `tag_map`.`object_type`='artist' AND `tag`.`id` IS NOT NULL)";
                    $parameters[] = $input;
                    break;
                case 'song_genre':
                    $where[]      = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'no_genre':
                    $where[] = "`artist`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 WHERE `tag_map`.`object_type`='artist' AND `tag`.`id` IS NOT NULL)";
                    break;
                case 'playlist_name':
                    $where[]    = "(`artist`.`id` IN (SELECT `artist_map`.`artist_id` FROM `playlist_data` LEFT JOIN `playlist` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' WHERE `playlist`.`name` $sql_match_operator ?) OR `artist`.`id` IN (SELECT `artist_map`.`artist_id` FROM `playlist_data` LEFT JOIN `playlist` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`album` AND `artist_map`.`object_type` = 'album' WHERE `playlist`.`name` $sql_match_operator ?))";
                    $parameters = array_merge($parameters, array($input, $input));
                    break;
                case 'playlist':
                    $where[]    = "(`artist`.`id` $sql_match_operator IN (SELECT `artist_map`.`artist_id` FROM `playlist_data` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' WHERE `playlist_data`.`playlist` = ?) OR `artist`.`id` $sql_match_operator IN (SELECT `artist_map`.`artist_id` FROM `playlist_data` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' WHERE `playlist_data`.`playlist` = ?))";
                    $parameters = array_merge($parameters, array($input, $input));
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "`average_rating`.`avg` $sql_match_operator ?";
                    $parameters[]     = $input;
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='artist' GROUP BY `object_id`) AS `average_rating` ON `average_rating`.`object_id` = `artist`.`id` ";
                    break;
                case 'favorite':
                    $where[]    = "(`artist`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator ?) AND `favorite_artist_$user_id`.`user` = $user_id AND `favorite_artist_$user_id`.`object_type` = 'artist'";
                    $parameters = array_merge($parameters, array($input, $input));
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_artist_$user_id"))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $user_id) AS `favorite_artist_$user_id` ON `artist`.`id` = `favorite_artist_$user_id`.`object_id` AND `favorite_artist_$user_id`.`object_type` = 'artist'"
                        : "";
                    break;
                case 'file':
                    $where[]      = "`song`.`file` $sql_match_operator ?";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'has_image':
                    $where[]            = ($sql_match_operator == '1') ? "`has_image`.`object_id` IS NOT NULL" : "`has_image`.`object_id` IS NULL";
                    $table['has_image'] = "LEFT JOIN (SELECT `object_id` FROM `image` WHERE `object_type` = 'artist') AS `has_image` ON `artist`.`id` = `has_image`.`object_id`";
                    break;
                case 'image_height':
                case 'image_width':
                    $looking       = strpos($rule[0], "image_") ? str_replace('image_', '', $rule[0]) : str_replace('image ', '', $rule[0]);
                    $where[]       = "`image`.`$looking` $sql_match_operator ?";
                    $parameters[]  = $input;
                    $join['image'] = true;
                    break;
                case 'myrating':
                    $column  = 'id';
                    $my_type = 'artist';
                    if ($input == 0 && $sql_match_operator == '>=') {
                        break;
                    }
                    if ($input == 0 && $sql_match_operator == '<') {
                        $input              = -1;
                        $sql_match_operator = '=';
                    }
                    if ($input == 0 && $sql_match_operator == '<>') {
                        $input              = 1;
                        $sql_match_operator = '>=';
                    }
                    if (($input == 0 && $sql_match_operator != '>') || ($input == 1 && $sql_match_operator == '<')) {
                        $where[] = "`rating_" . $my_type . "_" . $user_id . "`.`rating` IS NULL";
                    } elseif (in_array($sql_match_operator, array('<>', '<', '<=', '!='))) {
                        $where[]      = "(`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator ? OR `rating_" . $my_type . "_" . $user_id . "`.`rating` IS NULL)";
                        $parameters[] = $input;
                    } else {
                        $where[]      = "`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator ?";
                        $parameters[] = $input;
                    }
                    // rating once per user
                    if (!array_key_exists('rating', $table)) {
                        $table['rating'] = '';
                    }
                    $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = $user_id AND `object_type`='$my_type') AS `rating_" . $my_type . "_" . $user_id . "` ON `rating_" . $my_type . "_" . $user_id . "`.`object_id` = `artist`.`$column`"
                        : "";
                    break;
                case 'albumrating':
                case 'songrating':
                    $looking = str_replace('rating', '', $rule[0]);
                    $column  = ($looking == 'album') ? 'album_artist' : 'artist';
                    if ($input == 0 && $sql_match_operator == '>=') {
                        break;
                    }
                    if ($input == 0 && $sql_match_operator == '<') {
                        $input              = -1;
                        $sql_match_operator = '<=>';
                    }
                    if ($input == 0 && $sql_match_operator == '<>') {
                        $input              = 1;
                        $sql_match_operator = '>=';
                    }
                    if (($input == 0 && $sql_match_operator != '>') || ($input == 1 && $sql_match_operator == '<')) {
                        $where[] = "`artist`.`id` IN (SELECT `id` FROM `artist` WHERE `id` IN (SELECT `$looking`.`$column` FROM `$looking` WHERE `id` NOT IN (SELECT `object_id` FROM `rating` WHERE `user` = $user_id AND `object_type`='$looking')))";
                    } elseif (in_array($sql_match_operator, array('<>', '<', '<=', '!='))) {
                        $where[]      = "`artist`.`id` IN (SELECT `id` FROM `artist` WHERE `id` IN (SELECT `$looking`.`$column` FROM `$looking` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = $user_id AND `object_type`='$looking' AND `rating` $sql_match_operator ?))) OR `$looking`.`$column` NOT IN (SELECT `$column` FROM `$looking` WHERE `id` IN (SELECT `$column` FROM `$looking` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = $user_id AND `object_type`='$looking')))";
                        $parameters[] = $input;
                    } else {
                        $where[]      = "`artist`.`id` IN (SELECT `id` FROM `artist` WHERE `id` IN (SELECT `$looking`.`$column` FROM `$looking` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = $user_id AND `object_type`='$looking' AND `rating` $sql_match_operator ?)))";
                        $parameters[] = $input;
                    }
                    break;
                case 'myplayed':
                    $column       = 'id';
                    $my_type      = 'artist';
                    $operator_sql = ((int)$sql_match_operator == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT DISTINCT `artist_map`.`artist_id`, `object_count`.`user` FROM `object_count` LEFT JOIN `artist_map` ON `object_count`.`object_type` = `artist_map`.`object_type` AND `artist_map`.`object_id` = `object_count`.`object_id` WHERE `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `artist_map`.`artist_id`, `user`) AS `myplayed_" . $my_type . "_" . $user_id . "` ON `artist`.`$column` = `myplayed_" . $my_type . "_" . $user_id . "`.`artist_id`"
                        : "";
                    $where[] = "`myplayed_" . $my_type . "_" . $user_id . "`.`artist_id` $operator_sql";
                    break;
                case 'played':
                    $column       = 'id';
                    $my_type      = 'artist';
                    $operator_sql = ((int)$sql_match_operator == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('played', $table)) {
                        $table['played'] = '';
                    }
                    $table['played'] .= (!strpos((string) $table['played'], "played_" . $my_type))
                        ? "LEFT JOIN (SELECT DISTINCT `artist_map`.`artist_id`, `object_count`.`user` FROM `object_count` LEFT JOIN `artist_map` ON `object_count`.`object_type` = `artist_map`.`object_type` AND `artist_map`.`object_id` = `object_count`.`object_id` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' GROUP BY `artist_map`.`artist_id`, `user`) AS `played_" . $my_type . "` ON `artist`.`$column` = `played_" . $my_type . "`.`artist_id`"
                        : "";
                    $where[] = "`played_" . $my_type . "`.`artist_id` $operator_sql";
                    break;
                case 'last_play':
                    $my_type = 'artist';
                    if (!array_key_exists('last_play', $table)) {
                        $table['last_play'] = '';
                    }
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $user_id . "` ON `artist`.`id` = `last_play_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[]      = "`last_play_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - (? * 86400))";
                    $parameters[] = $input;
                    break;
                case 'last_skip':
                    $my_type = 'artist';
                    if (!array_key_exists('last_skip', $table)) {
                        $table['last_skip'] = '';
                    }
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id` = `last_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_skip_" . $my_type . "_" . $user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`last_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - (? * 86400))";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'last_play_or_skip':
                    $my_type = 'artist';
                    if (!array_key_exists('last_play_or_skip', $table)) {
                        $table['last_play_or_skip'] = '';
                    }
                    $table['last_play_or_skip'] .= (!strpos((string) $table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id` = `last_play_or_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_or_skip_" . $my_type . "_" . $user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`last_play_or_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - (? * 86400))";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'played_times':
                    $where[]      = "(`artist`.`total_count` $sql_match_operator ?)";
                    $parameters[] = $input;
                    break;
                case 'summary':
                    $where[]      = "`artist`.`summary` $sql_match_operator ?";
                    $parameters   = array_merge($parameters, array($input));
                    break;
                case 'album':
                    $where[]       = "(`album`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator ?) AND `artist_map`.`artist_id` IS NOT NULL";
                    $parameters    = array_merge($parameters, array($input, $input));
                    $join['album'] = true;
                    break;
                case 'song':
                    $where[]      = "`song`.`title` $sql_match_operator ?";
                    $parameters   = array_merge($parameters, array($input));
                    $join['song'] = true;
                    break;
                case 'album_count':
                    $group_column = (AmpConfig::get('album_group')) ? '`artist`.`album_group_count`' : '`artist`.`album_count`';
                    $where[]      = "($group_column $sql_match_operator ?)";
                    $parameters[] = $input;
                    break;
                case 'song_count':
                    $where[]      = "(`artist`.`song_count` $sql_match_operator ?)";
                    $parameters[] = $input;
                    break;
                case 'other_user':
                    $other_userid = $input;
                    if ($sql_match_operator == 'userflag') {
                        $where[] = "`favorite_artist_$other_userid`.`user` = $other_userid AND `favorite_artist_$other_userid`.`object_type` = 'artist'";
                        // flag once per user
                        if (!array_key_exists('favorite', $table)) {
                            $table['favorite'] = '';
                        }
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_artist_$other_userid"))
                            ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $other_userid) AS `favorite_artist_$other_userid` ON `artist`.`id` = `favorite_artist_$other_userid`.`object_id` AND `favorite_artist_$other_userid`.`object_type` = 'artist'"
                            : "";
                    } else {
                        $column  = 'id';
                        $my_type = 'artist';
                        $where[] = "`rating_artist_" . $other_userid . '`.' . $sql_match_operator . " AND `rating_artist_$other_userid`.`user` = $other_userid AND `rating_artist_$other_userid`.`object_type` = 'artist'";
                        // rating once per user
                        if (!array_key_exists('rating', $table)) {
                            $table['rating'] = '';
                        }
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $user_id))
                            ? "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $user_id . "` ON `rating_" . $my_type . "_" . $user_id . "`.`object_type`='$my_type' AND `rating_" . $my_type . "_" . $user_id . "`.`object_id` = `$my_type`.`$column` AND `rating_" . $my_type . "_" . $user_id . "`.`user` = $user_id "
                            : "";
                    }
                    break;
                case 'recent_played':
                    $key                     = md5($input . $sql_match_operator);
                    $where[]                 = "`played_$key`.`object_id` IS NOT NULL";
                    $table['played_' . $key] = "LEFT JOIN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'artist' ORDER BY $sql_match_operator DESC LIMIT $input) AS `played_$key` ON `artist`.`id` = `played_$key`.`object_id`";
                    break;
                case 'catalog':
                    $where[]         = "`catalog_se`.`id` $sql_match_operator ?";
                    $parameters[]    = $input;
                    $join['catalog'] = true;
                    break;
                case 'mbid':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($sql_match_operator, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`artist`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`artist`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]      = "`artist`.`mbid` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'mbid_album':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($sql_match_operator, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`album`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`album`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]       = "`album`.`mbid` $sql_match_operator ?";
                    $parameters[]  = $input;
                    $join['album'] = true;
                    break;
                case 'mbid_song':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($sql_match_operator, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`song`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`song`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]      = "`song`.`mbid` $sql_match_operator ?";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'possible_duplicate':
                    $where[]               = "(`dupe_search1`.`dupe_id1` IS NOT NULL OR `dupe_search2`.`dupe_id2` IS NOT NULL)";
                    $table['dupe_search1'] = "LEFT JOIN (SELECT MIN(`id`) AS `dupe_id1`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`))) AS `Counting` FROM `artist` GROUP BY `fullname` HAVING `Counting` > 1) AS `dupe_search1` ON `artist`.`id` = `dupe_search1`.`dupe_id1`";
                    $table['dupe_search2'] = "LEFT JOIN (SELECT MAX(`id`) AS `dupe_id2`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`))) AS `Counting` FROM `artist` GROUP BY `fullname` HAVING `Counting` > 1) AS `dupe_search2` ON `artist`.`id` = `dupe_search2`.`dupe_id2`";
                    break;
                case 'possible_duplicate_album':
                    $where[]                     = "((`dupe_album_search1`.`dupe_album_id1` IS NOT NULL OR `dupe_album_search2`.`dupe_album_id2` IS NOT NULL))";
                    $table['dupe_album_search1'] = "LEFT JOIN (SELECT `album_artist`, MIN(`id`) AS `dupe_album_id1`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk`, `year`, `release_type`, `release_status` HAVING `Counting` > 1) AS `dupe_album_search1` ON `artist`.`id` = `dupe_album_search1`.`album_artist`";
                    $table['dupe_album_search2'] = "LEFT JOIN (SELECT `album_artist`, MAX(`id`) AS `dupe_album_id2`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk`, `year`, `release_type`, `release_status` HAVING `Counting` > 1) AS `dupe_album_search2` ON `artist`.`id` = `dupe_album_search2`.`album_artist`";
                    break;
                default:
                    break;
            } // switch on ruletype artist
        } // foreach rule

        $join['catalog']     = array_key_exists('catalog', $join) || $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if (array_key_exists('song', $join)) {
            $table['0_artist_map'] = "LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `artist`.`id`";
            $table['1_song']       = "LEFT JOIN `song` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song'";
        }
        if (array_key_exists('album', $join)) {
            $table['0_artist_map'] = "LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `artist`.`id`";
            $table['4_album_map']  = "LEFT JOIN `album_map` ON `album_map`.`object_id` = `artist`.`id` AND `artist_map`.`object_type` = `album_map`.`object_type`";
            $table['album']        = "LEFT JOIN `album` ON `album_map`.`album_id` = `album`.`id`";
        }
        if ($join['catalog']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_artist` ON `catalog_map_artist`.`object_id` = `artist`.`id` AND `catalog_map_artist`.`object_type` = 'artist'";
            $table['3_catalog']     = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `catalog_map_artist`.`catalog_id`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1'";
            } else {
                $where_sql = "`catalog_se`.`enabled` = '1'";
            }
        }
        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        if (array_key_exists('count', $join)) {
            $table['object_count'] = "LEFT JOIN (SELECT `object_count`.`object_id`, MAX(`object_count`.`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'artist' AND `object_count`.`user`='" . $user_id . "' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` ON `object_count`.`object_id` = `artist`.`id`";
        }
        if (array_key_exists('image', $join)) {
            $table['0_artist_map'] = "LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `artist`.`id`";
            $table['1_song']       = "LEFT JOIN `song` ON `artist_map`.`artist_id` = `artist`.`id` AND `artist_map`.`object_type` = 'song'";
            $where_sql             = "(" . $where_sql . ") AND `image`.`object_type`='artist' AND `image`.`size`='original'";
        }
        if ($album_artist) {
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `artist`.`album_count` > 0";
            } else {
                $where_sql = "`artist`.`album_count` > 0";
            }
        }
        if ($song_artist) {
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `artist`.`song_count` > 0";
            } else {
                $where_sql = "`artist`.`song_count` > 0";
            }
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => "SELECT DISTINCT(`artist`.`id`), `artist`.`name` FROM `artist`",
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql,
            'parameters' => $parameters
        );
    }

    /**
     * _get_sql_song
     * Handles the generation of the SQL for song searches.
     * @return array
     */
    private function _get_sql_song()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id ?? 0;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $parameters  = array();
        $metadata    = array();

        foreach ($this->rules as $rule) {
            $type     = $this->_get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_filter_input($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'anywhere':
                    // 'anywhere' searches song title, song filename, song genre, album title, artist title, label title and song comment
                    $tag_string   = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                    $parameters[] = $input;
                    // we want AND NOT and like for this query to really exclude them
                    if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT'))) {
                        $where[] = "NOT ((`artist`.`name` LIKE ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) LIKE ?) OR (`album`.`name` LIKE ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) LIKE ?) OR `song_data`.`comment` LIKE ? OR `song_data`.`label` LIKE ? OR `song`.`file` LIKE ? OR `song`.`title` LIKE ? OR NOT " . $tag_string . ')';
                    } else {
                        $where[] = "((`artist`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator ?) OR (`album`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator ?) OR `song_data`.`comment` $sql_match_operator ? OR `song_data`.`label` $sql_match_operator ? OR `song`.`file` $sql_match_operator ? OR `song`.`title` $sql_match_operator ? OR " . $tag_string . ')';
                    }
                    $parameters = array_merge($parameters, array($input, $input, $input, $input, $input, $input, $input, $input));
                    // join it all up
                    $join['album']     = true;
                    $join['artist']    = true;
                    $join['song_data'] = true;
                    break;
                case 'title':
                    $where[]      = "`song`.`title` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'genre':
                    $where[]      = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                    $parameters[] = $input;
                    break;
                case 'album_genre':
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album` = `album`.`id`";
                    $where[]        = "`album`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator ? WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)";
                    $parameters[]   = $input;
                    break;
                case 'artist_genre':
                    $where[]         = "`artist`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator ? WHERE `tag_map`.`object_type`='artist' AND `tag`.`id` IS NOT NULL)";
                    $parameters[]    = $input;
                    $join['artist']  = true;
                    break;
                case 'no_genre':
                    $where[] = "`song`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                    break;
                case 'album':
                    $where[]        = "(`album`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator ?)";
                    $parameters     = array_merge($parameters, array($input, $input));
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album` = `album`.`id`";
                    break;
                case 'artist':
                    $where[]        = "(`artist`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator ?)";
                    $parameters     = array_merge($parameters, array($input, $input));
                    $join['artist'] = true;
                    break;
                case 'album_artist':
                    $where[]               = "(`album_artist`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`album_artist`.`prefix`, ''), ' ', `album_artist`.`name`)) $sql_match_operator ?)";
                    $parameters            = array_merge($parameters, array($input, $input));
                    $table['album']        = "LEFT JOIN `album` ON `song`.`album` = `album`.`id`";
                    $table['album_artist'] = "LEFT JOIN `artist` AS `album_artist` ON `album`.`album_artist` = `album_artist`.`id`";
                    break;
                case 'time':
                    $input        = $input * 60;
                    $where[]      = "`song`.`time` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'file':
                case 'composer':
                case 'year':
                case 'track':
                case 'catalog':
                case 'license':
                    $where[]      = "`song`.`$rule[0]` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'comment':
                    $where[]           = "`song_data`.`comment` $sql_match_operator ?";
                    $parameters[]      = $input;
                    $join['song_data'] = true;
                    break;
                case 'label':
                    $join['song_data'] = true;
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($sql_match_operator, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`song_data`.`label` IS NULL";
                            break;
                        }
                        if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`song_data`.`label` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]      = "`song_data`.`label` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'lyrics':
                    $where[]           = "`song_data`.`lyrics` $sql_match_operator ?";
                    $parameters[]      = $input;
                    $join['song_data'] = true;
                    break;
                case 'played':
                    $where[] = "`song`.`played` = '$sql_match_operator'";
                    break;
                case 'last_play':
                    $my_type = 'song';
                    if (!array_key_exists('last_play', $table)) {
                        $table['last_play'] = '';
                    }
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $user_id . "` ON `song`.`id` = `last_play_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`last_play_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_skip':
                    $my_type = 'song';
                    if (!array_key_exists('last_skip', $table)) {
                        $table['last_skip'] = '';
                    }
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id` = `last_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_skip_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type' "
                        : "";
                    $where[] = "`last_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_play_or_skip':
                    $my_type = 'song';
                    if (!array_key_exists('last_play_or_skip', $table)) {
                        $table['last_play_or_skip'] = '';
                    }
                    $table['last_play_or_skip'] .= (!strpos((string) $table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id` = `last_play_or_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_or_skip_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`last_play_or_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'played_times':
                    $where[]      = "(`song`.`total_count` $sql_match_operator ?)";
                    $parameters[] = $input;
                    break;
                case 'skipped_times':
                    $where[]      = "(`song`.`total_skip` $sql_match_operator ?)";
                    $parameters[] = $input;
                    break;
                case 'played_or_skipped_times':
                    $where[]      = "((`song`.`total_count` + `song`.`total_skip`) $sql_match_operator ?)";
                    $parameters[] = $input;
                    break;
                case 'play_skip_ratio':
                    $where[]      = "(((`song`.`total_count`/`song`.`total_skip`) * 100) $sql_match_operator ?)";
                    $parameters[] = $input;
                    break;
                case 'myplayed':
                case 'myplayedalbum':
                case 'myplayedartist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('myplayed', '', $rule[0]);
                    $column       = ($looking == '') ? 'id' : $looking;
                    $my_type      = ($looking == '') ? 'song' : $looking;
                    $operator_sql = ((int) $sql_match_operator == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $user_id . "` ON `song`.`$column` = `myplayed_" . $my_type . "_" . $user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`myplayed_" . $my_type . "_" . $user_id . "`.`object_id` $operator_sql";
                    break;
                case 'bitrate':
                    $input        = $input * 1000;
                    $where[]      = "`song`.`bitrate` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "`average_rating`.`avg` $sql_match_operator ?";
                    $parameters[]     = $input;
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='song' GROUP BY `object_id`) AS `average_rating` ON `average_rating`.`object_id` = `song`.`id` ";
                    break;
                case 'favorite':
                    $where[]      = "`song`.`title` $sql_match_operator ? AND `favorite_song_$user_id`.`user` = $user_id AND `favorite_song_$user_id`.`object_type` = 'song'";
                    $parameters[] = $input;
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_song_$user_id"))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $user_id) AS `favorite_song_$user_id` ON `song`.`id` = `favorite_song_$user_id`.`object_id` AND `favorite_song_$user_id`.`object_type` = 'song'"
                        : "";
                    break;
                case 'favorite_album':
                    $where[]    = "(`album`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator ?) AND `favorite_album_$user_id`.`user` = $user_id AND `favorite_album_$user_id`.`object_type` = 'album'";
                    $parameters = array_merge($parameters, array($input, $input));
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_album_$user_id"))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $user_id) AS `favorite_album_$user_id` ON `album`.`id` = `favorite_album_$user_id`.`object_id` AND `favorite_album_$user_id`.`object_type` = 'album'"
                        : "";
                    $join['album'] = true;
                    break;
                case 'favorite_artist':
                    $where[]    = "(`artist`.`name` $sql_match_operator ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator ?) AND `favorite_artist_$user_id`.`user` = $user_id AND `favorite_artist_$user_id`.`object_type` = 'artist'";
                    $parameters = array_merge($parameters, array($input, $input));
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_artist_$user_id"))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $user_id) AS `favorite_artist_$user_id` ON `artist`.`id` = `favorite_artist_$user_id`.`object_id` AND `favorite_artist_$user_id`.`object_type` = 'artist'"
                        : "";
                    $join['artist'] = true;
                    break;
                case 'myrating':
                case 'albumrating':
                case 'artistrating':
                    // combine these as they all do the same thing just different tables
                    $looking = str_replace('rating', '', $rule[0]);
                    $column  = ($looking == 'my') ? 'id' : $looking;
                    $my_type = ($looking == 'my') ? 'song' : $looking;
                    if ($input == 0 && $sql_match_operator == '>=') {
                        break;
                    }
                    if ($input == 0 && $sql_match_operator == '<') {
                        $input              = -1;
                        $sql_match_operator = '=';
                    }
                    if ($input == 0 && $sql_match_operator == '<>') {
                        $input              = 1;
                        $sql_match_operator = '>=';
                    }
                    if (($input == 0 && $sql_match_operator != '>') || ($input == 1 && $sql_match_operator == '<')) {
                        $where[] = "`rating_" . $my_type . "_" . $user_id . "`.`rating` IS NULL";
                    } elseif (in_array($sql_match_operator, array('<>', '<', '<=', '!='))) {
                        $where[]      = "(`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator ? OR `rating_" . $my_type . "_" . $user_id . "`.`rating` IS NULL)";
                        $parameters[] = $input;
                    } else {
                        $where[]      = "`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator ?";
                        $parameters[] = $input;
                    }
                    // rating once per user
                    if (!array_key_exists('rating', $table)) {
                        $table['rating'] = '';
                    }
                    $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = $user_id AND `object_type`='$my_type') AS `rating_" . $my_type . "_" . $user_id . "` ON `rating_" . $my_type . "_" . $user_id . "`.`object_id` = `song`.`$column`"
                        : "";
                    break;
                case 'other_user':
                case 'other_user_album':
                case 'other_user_artist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('other_user_', '', $rule[0]);
                    $column       = ($looking == 'other_user') ? 'id' : $looking;
                    $my_type      = ($looking == 'other_user') ? 'song' : $looking;
                    $other_userid = $input;
                    if ($sql_match_operator == 'userflag') {
                        $where[] = "`favorite_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid AND `favorite_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type'";
                        // flag once per user
                        if (!array_key_exists('favorite', $table)) {
                            $table['favorite'] = '';
                        }
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_" . $my_type . "_" . $other_userid))
                            ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $other_userid) AS `favorite_" . $my_type . "_" . $other_userid . "` ON `song`.`$column` = `favorite_" . $my_type . "_" . $other_userid . "`.`object_id` AND `favorite_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type'"
                            : "";
                    } else {
                        $unrated = ($sql_match_operator == 'unrated');
                        $where[] = ($unrated) ? "`song`.`$column` NOT IN (SELECT `object_id` FROM `rating` WHERE `object_type` = '$my_type' AND `user` = $other_userid)" : "`rating_" . $my_type . "_" . $other_userid . "`.$sql_match_operator AND `rating_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid AND `rating_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type'";
                        // rating once per user
                        if (!array_key_exists('rating', $table)) {
                            $table['rating'] = '';
                        }
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $other_userid))
                            ? "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $other_userid . "` ON `rating_" . $my_type . "_" . $other_userid . "`.`object_type`='$my_type' AND `rating_" . $my_type . "_" . $other_userid . "`.`object_id` = `song`.`$column` AND `rating_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid "
                            : "";
                    }
                    break;
                case 'playlist_name':
                    $join['playlist']      = true;
                    $join['playlist_data'] = true;
                    $where[]               = "`playlist`.`name` $sql_match_operator ?";
                    $parameters[]          = $input;
                    break;
                case 'playlist':
                    $where[]      = "`song`.`id` $sql_match_operator IN (SELECT `object_id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = 'song')";
                    $parameters[] = $input;
                    break;
                case 'smartplaylist':
                    //debug_event(self::class, '_get_sql_song: SUBSEARCH ' . $input, 5);
                    $subsearch  = new Search($input, 'song', $this->search_user);
                    $results    = $subsearch->get_items();
                    $itemstring = '';
                    if (count($results) > 0) {
                        foreach ($results as $item) {
                            $itemstring .= $item['object_id'] . ',';
                        }
                        $where[]  = "`song`.`id` $sql_match_operator IN (" . substr($itemstring, 0, -1) . ")";
                    }
                    break;
                case 'added':
                    $input        = strtotime((string) $input);
                    $where[]      = "`song`.`addition_time` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'updated':
                    $input        = strtotime((string) $input);
                    $where[]      = "`song`.`update_time` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'recent_played':
                    $key                     = md5($input . $sql_match_operator);
                    $where[]                 = "`played_$key`.`object_id` IS NOT NULL";
                    $table['played_' . $key] = "LEFT JOIN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'song' ORDER BY $sql_match_operator DESC LIMIT $input) AS `played_$key` ON `song`.`id` = `played_$key`.`object_id`";
                    break;
                case 'recent_added':
                    $key                       = md5($input . $sql_match_operator);
                    $where[]                   = "`addition_time_$key`.`id` IS NOT NULL";
                    $table['addition_' . $key] = "LEFT JOIN (SELECT `id` FROM `song` ORDER BY $sql_match_operator DESC LIMIT $input) AS `addition_time_$key` ON `song`.`id` = `addition_time_$key`.`id`";
                    break;
                case 'recent_updated':
                    $key                     = md5($input . $sql_match_operator);
                    $where[]                 = "`update_time_$key`.`id` IS NOT NULL";
                    $table['update_' . $key] = "LEFT JOIN (SELECT `id` FROM `song` ORDER BY $sql_match_operator DESC LIMIT $input) AS `update_time_$key` ON `song`.`id` = `update_time_$key`.`id`";
                    break;
                case 'mbid':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($sql_match_operator, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`song`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`song`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]      = "`song`.`mbid` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'mbid_album':
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album` = `album`.`id`";
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($sql_match_operator, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`album`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`album`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]        = "`album`.`mbid` $sql_match_operator ?";
                    $parameters[]   = $input;
                    break;
                case 'mbid_artist':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($sql_match_operator, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`artist`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($sql_match_operator, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`artist`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]        = "`artist`.`mbid` $sql_match_operator ?";
                    $parameters[]   = $input;
                    $join['artist'] = true;
                    break;
                case 'possible_duplicate':
                    $where[]               = "(`dupe_search1`.`dupe_id1` IS NOT NULL OR `dupe_search2`.`dupe_id2` IS NOT NULL)";
                    $table['dupe_search1'] = "LEFT JOIN (SELECT MIN(`song`.`id`) AS `dupe_id1`, CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`disk`, `song`.`title`) AS `fullname`, COUNT(CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`disk`, `song`.`title`)) AS `counting` FROM `song` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' LEFT JOIN `artist` ON `artist_map`.`artist_id` = `artist`.`id` GROUP BY `fullname` HAVING `Counting` > 1) AS `dupe_search1` ON `song`.`id` = `dupe_search1`.`dupe_id1` ";
                    $table['dupe_search2'] = "LEFT JOIN (SELECT MAX(`song`.`id`) AS `dupe_id2`, CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`disk`, `song`.`title`) AS `fullname`, COUNT(CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`disk`, `song`.`title`)) AS `counting` FROM `song` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' LEFT JOIN `artist` ON `artist_map`.`artist_id` = `artist`.`id` GROUP BY `fullname` HAVING `Counting` > 1) AS `dupe_search2` ON `song`.`id` = `dupe_search2`.`dupe_id2`";
                    break;
                case 'possible_duplicate_album':
                    $where[]                     = "((`dupe_album_search1`.`dupe_album_id1` IS NOT NULL OR `dupe_album_search2`.`dupe_album_id2` IS NOT NULL))";
                    $table['dupe_album_search1'] = "LEFT JOIN (SELECT `album_artist`, MIN(`id`) AS `dupe_album_id1`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk`, `year`, `release_type`, `release_status` HAVING `Counting` > 1) AS `dupe_album_search1` ON `album`.`id` = `dupe_album_search1`.`dupe_album_id1`";
                    $table['dupe_album_search2'] = "LEFT JOIN (SELECT `album_artist`, MAX(`id`) AS `dupe_album_id2`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk`, `year`, `release_type`, `release_status` HAVING `Counting` > 1) AS `dupe_album_search2` ON `album`.`id` = `dupe_album_search2`.`dupe_album_id2`";
                    $join['album']               = true;
                    break;
                case 'orphaned_album':
                    $where[] = "`song`.`album` IN (SELECT `album_id` FROM `album_map` WHERE `album_id` NOT IN (SELECT `id` from `album`))";
                    break;
                case 'metadata':
                    $field = (int)$rule[3];
                    if ($sql_match_operator === '=' && strlen($input) == 0) {
                        $where[] = "NOT EXISTS (SELECT NULL FROM `metadata` WHERE `metadata`.`object_id` = `song`.`id` AND `metadata`.`field` = {$field})";
                    } else {
                        $parsedInput = is_numeric($input) ? $input : '"' . $input . '"';
                        if (!array_key_exists($field, $metadata)) {
                            $metadata[$field] = array();
                        }
                        $metadata[$field][] = "`metadata`.`data` $sql_match_operator ?";
                        $parameters[]       = $parsedInput;
                    }
                    break;
                default:
                    break;
            } // switch on ruletype song
        } // foreach over rules

        // translate metadata queries into sql for each field
        foreach ($metadata as $metadata_field => $metadata_queries) {
            $metadata_sql = "EXISTS (SELECT NULL FROM `metadata` WHERE `metadata`.`object_id` = `song`.`id` AND `metadata`.`field` = {$metadata_field} AND (";
            $metadata_sql .= implode(" $sql_logic_operator ", $metadata_queries);
            $where[] = $metadata_sql . '))';
        }

        $join['catalog_map'] = $catalog_filter;
        $join['catalog']     = $catalog_disable || $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        // now that we know which things we want to JOIN...
        if (array_key_exists('song_data', $join)) {
            $table['song_data'] = "LEFT JOIN `song_data` ON `song`.`id` = `song_data`.`song_id`";
        }
        if (array_key_exists('playlist_data', $join)) {
            $table['playlist_data'] = "LEFT JOIN `playlist_data` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type`='song'";
            if (array_key_exists('playlist', $join)) {
                $table['playlist'] = "LEFT JOIN `playlist` ON `playlist_data`.`playlist` = `playlist`.`id`";
            }
        }
        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `song`.`catalog`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            } else {
                $where_sql = "`catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            }
        }
        if ($join['catalog_map']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_song` ON `catalog_map_song`.`object_id` = `song`.`id` AND `catalog_map_song`.`object_type` = 'song' AND `catalog_map_song`.`catalog_id` = `catalog_se`.`id`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        if (array_key_exists('artist', $join)) {
            $table['3_artist_map'] = "LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song'";
            $table['4_artist']     = "LEFT JOIN `artist` ON `artist_map`.`artist_id` = `artist`.`id`";
        }
        if (array_key_exists('album', $join)) {
            $table['album'] = "LEFT JOIN `album` ON `song`.`album` = `album`.`id`";
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`song`.`id`), `song`.`file` FROM `song`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql,
            'parameters' => $parameters
        );
    }

    /**
     * _get_sql_video
     *
     * Handles the generation of the SQL for video searches.
     * @return array
     */
    private function _get_sql_video()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id ?? 0;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $parameters  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->_get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_filter_input($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'file':
                    $where[]      = "`video`.`file` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog_map'] = $catalog_filter;
        $join['catalog']     = $catalog_disable || $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `video`.`catalog`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1' AND `video`.`enabled` = 1";
            } else {
                $where_sql = "`catalog_se`.`enabled` = '1' AND `video`.`enabled` = 1";
            }
        }
        if ($join['catalog_map']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_video` ON `catalog_map_video`.`object_id` = `video`.`id` AND `catalog_map_video`.`object_type` = 'video' AND `catalog_map_video`.`catalog_id` = `catalog_se`.`id`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`video`.`id`), `video`.`file` FROM `video`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql,
            'parameters' => $parameters
        );
    }

    /**
     * _get_sql_playlist
     *
     * Handles the generation of the SQL for playlist searches.
     * @return array
     */
    private function _get_sql_playlist()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id ?? 0;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $parameters  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->_get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_filter_input($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'] ?? '';

            $where[] = "(`playlist`.`type` = 'public' OR `playlist`.`user`=" . $user_id . ")";

            switch ($rule[0]) {
                case 'title':
                    $where[]      = "`playlist`.`name` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'type':
                    $where[]      = "`playlist`.`type` $sql_match_operator ?";
                    $parameters[] = ($input == 1)
                        ? 'private'
                        : 'public';
                    break;
                case 'owner':
                    $where[]      = "`playlist`.`user` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog']     = $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        // always join the table data
        $table['0_playlist_data'] = "LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id`";
        if ($join['catalog']) {
            $table['0_song']    = "LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id`";
            $where_sql          = "(" . $where_sql . ") AND `playlist_data`.`object_type` = 'song'";
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `song`.`catalog`";
            if ($catalog_disable) {
                if (!empty($where_sql)) {
                    $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
                } else {
                    $where_sql = "`catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
                }
            }
        }
        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`playlist`.`id`), `playlist`.`name` FROM `playlist`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql,
            'parameters' => $parameters
        );
    }

    /**
     * _get_sql_podcast
     *
     * Handles the generation of the SQL for podcast_episode searches.
     * @return array
     */
    private function _get_sql_podcast()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id ?? 0;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $parameters  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->_get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_filter_input($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'title':
                    $where[]      = "`podcast`.`title` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'podcast_episode':
                case 'podcast_episode_title':
                    $where[]                 = "`podcast_episode`.`title` $sql_match_operator ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'time':
                    $input                   = $input * 60;
                    $where[]                 = "`podcast_episode`.`time` $sql_match_operator ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'state':
                    $where[]      = "`podcast_episode`.`state` $sql_match_operator ?";
                    switch ($input) {
                        case 0:
                            $parameters[] = 'skipped';
                            break;
                        case 1:
                            $parameters[] = 'pending';
                            break;
                        case 2:
                            $parameters[] = 'completed';
                    }
                    $join['podcast_episode'] = true;
                    break;
                case 'pubdate':
                    $input                   = strtotime((string) $input);
                    $where[]                 = "`podcast_episode`.`pubdate` $sql_match_operator ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'added':
                    $input                   = strtotime((string) $input);
                    $where[]                 = "`podcast_episode`.`addition_time` $sql_match_operator ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'file':
                    $where[]                 = "`podcast_episode`.`file` $sql_match_operator ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog']     = $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if (array_key_exists('podcast_episode', $join)) {
            $table['0_podcast'] = "LEFT JOIN `podcast_episode` ON `podcast_episode`.`podcast` = `podcast`.`id`";
        }
        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `podcast`.`catalog`";
            if ($catalog_disable) {
                if (!empty($where_sql)) {
                    $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1'";
                } else {
                    $where_sql = "`catalog_se`.`enabled` = '1'";
                }
            }
        }
        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`podcast`.`id`), `podcast`.`title` FROM `podcast`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql,
            'parameters' => $parameters
        );
    }

    /**
     * _get_sql_podcast_episode
     *
     * Handles the generation of the SQL for podcast_episode searches.
     * @return array
     */
    private function _get_sql_podcast_episode()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id ?? 0;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $parameters  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->_get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_filter_input($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'title':
                    $where[]      = "`podcast_episode`.`title` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'podcast':
                case 'podcast_title':
                    $where[]         = "`podcast`.`title` $sql_match_operator ?";
                    $parameters[]    = $input;
                    $join['podcast'] = true;
                    break;
                case 'time':
                    $input        = $input * 60;
                    $where[]      = "`podcast_episode`.`time` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'state':
                    $where[]      = "`podcast_episode`.`state` $sql_match_operator ?";
                    switch ($input) {
                        case 0:
                            $parameters[] = 'skipped';
                            break;
                        case 1:
                            $parameters[] = 'pending';
                            break;
                        case 2:
                            $parameters[] = 'completed';
                    }
                    break;
                case 'pubdate':
                    $input        = strtotime((string) $input);
                    $where[]      = "`podcast_episode`.`pubdate` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'added':
                    $input        = strtotime((string) $input);
                    $where[]      = "`podcast_episode`.`addition_time` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'file':
                    $where[]      = "`podcast_episode`.`file` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog']     = $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if (array_key_exists('podcast', $join)) {
            $table['0_podcast'] = "LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast`";
        }
        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `podcast_episode`.`catalog`";
            if ($catalog_disable) {
                if (!empty($where_sql)) {
                    $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1'";
                } else {
                    $where_sql = "`catalog_se`.`enabled` = '1'";
                }
            }
        }
        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`podcast_episode`.`id`), `podcast_episode`.`pubdate` FROM `podcast_episode`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql,
            'parameters' => $parameters
        );
    }

    /**
     * _get_sql_label
     *
     * Handles the generation of the SQL for label searches.
     * @return array
     */
    private function _get_sql_label()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id ?? 0;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $parameters  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->_get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_filter_input($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'title':
                    $where[]      = "`label`.`name` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'category':
                    $where[]      = "`label`.`category` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog_map'] = $catalog_filter;
        $join['catalog']     = $catalog_disable || $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($catalog_disable || $catalog_filter) {
            $table['0_label_asso']  = "LEFT JOIN `label_asso` ON `label_asso`.`label` = `label`.`id`";
            $table['1_artist']      = "LEFT JOIN `artist` ON `label_asso`.`artist` = `artist`.`id`";
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_artist` ON `catalog_map_artist`.`object_id` = `artist`.`id` AND `catalog_map_artist`.`object_type` = 'artist'";
        }

        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_map_artist`.`object_type` = 'artist' AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_map_artist`.`object_type` = 'artist' AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        if ($join['catalog']) {
            $table['3_catalog'] = "LEFT JOIN `catalog`AS `catalog_se` ON `catalog_map_artist`.`catalog_id` = `catalog_se`.`id`";
            if ($catalog_disable) {
                if (!empty($where_sql)) {
                    $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1'";
                } else {
                    $where_sql = "`catalog_se`.`enabled` = '1'";
                }
            }
        }
        $table_sql = implode(' ', $table);

        return array(
            'base' => 'SELECT DISTINCT(`label`.`id`), `label`.`name` FROM `label`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => '',
            'having_sql' => '',
            'parameters' => $parameters
        );
    }

    /**
     * _get_sql_tag
     *
     * Handles the generation of the SQL for tag (genre) searches.
     * @return array
     */
    private function _get_sql_tag()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id ?? 0;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $parameters  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->_get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_filter_input($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'title':
                    $where[]      = "`tag`.`name` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                case 'category':
                    $where[]      = "`tag`.`category` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog_map'] = $catalog_filter;
        $join['catalog']     = $catalog_disable || $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `song`.`catalog`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            } else {
                $where_sql = "`catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            }
        }
        if ($join['catalog_map']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_album` ON `catalog_map_album`.`object_id` = `album`.`id` AND `catalog_map_album`.`object_type` = 'album' AND `catalog_map_album`.`catalog_id` = `catalog_se`.`id`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = $user_id AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }

        return array(
            'base' => 'SELECT DISTINCT(`tag`.`id`) FROM `tag`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => '',
            'group_sql' => '',
            'having_sql' => '',
            'parameters' => $parameters
        );
    }

    /**
     * _get_sql_user
     *
     * Handles the generation of the SQL for user searches.
     * @return array
     */
    private function _get_sql_user()
    {
        $sql_logic_operator = $this->logic_operator;

        $where       = array();
        $table       = array();
        $join        = array();
        $parameters  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->_get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_filter_input($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'username':
                    $where[]      = "`user`.`username` $sql_match_operator ?";
                    $parameters[] = $input;
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $where_sql = implode(" $sql_logic_operator ", $where);
        ksort($table);

        return array(
            'base' => 'SELECT DISTINCT(`user`.`id`), `user`.`username` FROM `user`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => '',
            'group_sql' => '',
            'having_sql' => '',
            'parameters' => $parameters
        );
    }

    /**
     * year_search
     *
     * Build search rules for year -> year searching.
     * @param $fromYear
     * @param $toYear
     * @param $size
     * @param $offset
     * @return array
     */
    public static function year_search($fromYear, $toYear, $size, $offset)
    {
        $search           = array();
        $search['limit']  = $size;
        $search['offset'] = $offset;
        $search['type']   = "album";
        $count            = 0;
        if ($fromYear) {
            $search['rule_' . $count . '_input']    = $fromYear;
            $search['rule_' . $count . '_operator'] = 0;
            $search['rule_' . $count]               = "original_year";
            ++$count;
        }
        if ($toYear) {
            $search['rule_' . $count . '_input']    = $toYear;
            $search['rule_' . $count . '_operator'] = 1;
            $search['rule_' . $count]               = "original_year";
            ++$count;
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
