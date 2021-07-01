<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

    public $searchtype;
    public $rules;
    public $logic_operator = 'AND';
    public $type           = 'public';
    public $random         = 0;
    public $limit          = 0;
    public $last_count     = 0;
    public $last_duration  = 0;
    public $date           = 0;

    public $basetypes;
    public $types;

    public $link;
    public $f_link;

    public $search_user;

    private $stars;
    private $order_by;

    /**
     * constructor
     * @param integer $search_id
     * @param string $searchtype
     * @param User $user
     */
    public function __construct($search_id = 0, $searchtype = 'song', ?User $user = null)
    {
        if ($user->id) {
            $this->search_user = $user;
        } else {
            $this->search_user = Core::get_global('user');
        }
        $this->searchtype = $searchtype;
        if ($search_id > 0) {
            $info = $this->get_info($search_id);
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
            $this->rules = json_decode((string)$this->rules, true);
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
        $this->set_basetypes();

        $this->types = array();
        switch ($searchtype) {
            case 'song':
                $this->song_types();
                $this->order_by = '`song`.`file`';
                break;
            case 'album':
                $this->album_types();
                $this->order_by = (AmpConfig::get('album_group')) ? '`album`.`name`' : '`album`.`name`, `album`.`disk`';
                break;
            case 'video':
                $this->video_types();
                $this->order_by = '`video`.`file`';
                break;
            case 'artist':
                $this->artist_types();
                $this->order_by = '`artist`.`name`';
                break;
            case 'playlist':
                $this->playlist_types();
                $this->order_by = '`playlist`.`name`';
                break;
            case 'label':
                $this->label_types();
                $this->order_by = '`label`.`name`';
                break;
            case 'user':
                $this->user_types();
                $this->order_by = '`user`.`username`';
                break;
        } // end switch on searchtype
    } // end constructor

    /**
     * set_basetypes
     *
     * Function called during construction to set the different types and rules for search
     */
    private function set_basetypes()
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
     * type_numeric
     *
     * Generic integer searches rules
     * @param string $name
     * @param string $label
     * @param string $type
     */
    private function type_numeric($name, $label, $type = 'numeric')
    {
        $this->types[] = array(
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'widget' => array('input', 'number')
        );
    }

    /**
     * type_date
     *
     * Generic integer searches rules
     * @param string $name
     * @param string $label
     */
    private function type_date($name, $label)
    {
        $this->types[] = array(
            'name' => $name,
            'label' => $label,
            'type' => 'date',
            'widget' => array('input', 'datetime-local')
        );
    }

    /**
     * type_text
     *
     * Generic text rules
     * @param string $name
     * @param string $label
     */
    private function type_text($name, $label)
    {
        $this->types[] = array(
            'name' => $name,
            'label' => $label,
            'type' => 'text',
            'widget' => array('input', 'text')
        );
    }

    /**
     * type_select
     *
     * Generic rule to select from a list
     * @param string $name
     * @param string $label
     * @param string $type
     * @param array $array
     */
    private function type_select($name, $label, $type, $array)
    {
        $this->types[] = array(
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'widget' => array('select', $array)
        );
    }

    /**
     * type_boolean
     *
     * True or false generic searches
     * @param string $name
     * @param string $label
     * @param string $type
     */
    private function type_boolean($name, $label, $type = 'boolean')
    {
        $this->types[] = array(
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'widget' => array('input', 'hidden')
        );
    }

    /**
     * songtypes
     *
     * this is where all the searchtypes for songs are defined
     */
    private function song_types()
    {
        $this->type_text('anywhere', T_('Any searchable text'));
        $this->type_text('title', T_('Title'));
        $this->type_text('album', T_('Album'));
        $this->type_text('artist', T_('Song Artist'));
        $this->type_text('album_artist', T_('Album Artist'));
        $this->type_text('composer', T_('Composer'));

        $this->type_numeric('year', T_('Year'));

        if (AmpConfig::get('ratings')) {
            $this->type_select('myrating', T_('My Rating'), 'numeric', $this->stars);
            $this->type_select('rating', T_('Rating (Average)'), 'numeric', $this->stars);
            $this->type_select('albumrating', T_('My Rating (Album)'), 'numeric', $this->stars);
            $this->type_select('artistrating', T_('My Rating (Artist)'), 'numeric', $this->stars);
        }
        if (AmpConfig::get('userflags')) {
            $this->type_text('favorite', T_('Favorites'));
            $this->type_text('favorite_album', T_('Favorites (Album)'));
            $this->type_text('favorite_artist', T_('Favorites (Artist)'));
        }

        /* HINT: Number of times object has been played */
        $this->type_numeric('played_times', T_('# Played'));
        /* HINT: Number of times object has been skipped */
        $this->type_numeric('skipped_times', T_('# Skipped'));
        /* HINT: Number of times object has been played OR skipped */
        $this->type_numeric('played_or_skipped_times', T_('# Played or Skipped'));
        /* HINT: Percentage of (Times Played / Times skipped) * 100 */
        $this->type_numeric('play_skip_ratio', T_('Played/Skipped ratio'));
        $this->type_numeric('last_play', T_('My Last Play'), 'days');
        $this->type_numeric('last_skip', T_('My Last Skip'), 'days');
        $this->type_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days');
        $this->type_boolean('played', T_('Played'));
        $this->type_boolean('myplayed', T_('Played by Me'));
        $this->type_boolean('myplayedalbum', T_('Played by Me (Album)'));
        $this->type_boolean('myplayedartist', T_('Played by Me (Artist)'));
        $this->type_numeric('time', T_('Length (in minutes)'));

        $this->type_text('tag', T_('Genre'));
        $this->type_text('album_tag', T_('Album Genre'));
        $this->type_text('artist_tag', T_('Artist Genre'));

        $users = array();
        foreach ($this->getUserRepository()->getValid() as $user_id) {
            $user            = new User($user_id);
            $users[$user_id] = $user->username;
        }
        $this->type_select('other_user', T_('Another User'), 'user_numeric', $users);
        $this->type_select('other_user_album', T_('Another User (Album)'), 'user_numeric', $users);
        $this->type_select('other_user_artist', T_('Another User (Artist)'), 'user_numeric', $users);

        $this->type_text('label', T_('Label'));
        if (AmpConfig::get('licensing')) {
            $licenses = array();
            foreach ($this->getLicenseRepository()->getAll() as $license_id) {
                $license               = new License($license_id);
                $licenses[$license_id] = $license->name;
            }
            $this->type_select('license', T_('Music License'), 'boolean_numeric', $licenses);
        }

        $playlists = array();
        foreach (Playlist::get_playlists() as $playlistid) {
            $playlist = new Playlist($playlistid);
            $playlist->format(false);
            $playlists[$playlistid] = $playlist->f_name;
        }
        $this->type_select('playlist', T_('Playlist'), 'boolean_numeric', $playlists);

        $playlists = array();
        $searches  = self::get_searches();
        foreach ($searches as $playlistid) {
            // Slightly different from the above so we don't instigate a vicious loop.
            $playlists[$playlistid] = self::get_name_byid($playlistid);
        }
        $this->type_select('smartplaylist', T_('Smart Playlist'), 'boolean_subsearch', $playlists);

        $this->type_text('playlist_name', T_('Playlist Name'));

        $this->type_text('comment', T_('Comment'));
        $this->type_text('lyrics', T_('Lyrics'));
        $this->type_text('file', T_('Filename'));
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
        $this->type_select('bitrate', T_('Bitrate'), 'numeric', $bitrate_array);
        $this->type_date('added', T_('Added'));
        $this->type_date('updated', T_('Updated'));

        $this->type_numeric('recent_played', T_('Recently played'), 'recent_played');
        $this->type_numeric('recent_added', T_('Recently added'), 'recent_added');
        $this->type_numeric('recent_updated', T_('Recently updated'), 'recent_updated');

        $catalogs = array();
        foreach (Catalog::get_catalogs('music', (int)$this->search_user) as $catid) {
            $catalog = Catalog::create_from_id($catid);
            $catalog->format();
            $catalogs[$catid] = $catalog->f_name;
        }
        if (!empty($catalogs)) {
            $this->type_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs);
        }

        $this->type_text('mbid', T_('MusicBrainz ID'));
        $this->type_text('mbid_album', T_('MusicBrainz ID (Album)'));
        $this->type_text('mbid_artist', T_('MusicBrainz ID (Artist)'));
        $this->type_boolean('possible_duplicate', T_('Possible Duplicate'), 'is_true');

        if (AmpConfig::get('enable_custom_metadata')) {
            $metadataFields          = array();
            $metadataFieldRepository = new MetadataField();
            foreach ($metadataFieldRepository->findAll() as $metadata) {
                $metadataFields[$metadata->getId()] = $metadata->getName();
            }
            $this->types[] = array(
                'name' => 'metadata',
                'label' => T_('Metadata'),
                'type' => 'multiple',
                'subtypes' => $metadataFields,
                'widget' => array('subtypes', array('input', 'text'))
            );
        }
    }

    /**
     * artisttypes
     *
     * this is where all the searchtypes for artists are defined
     */
    private function artist_types()
    {
        $this->type_text('title', T_('Name'));

        $this->type_numeric('yearformed', T_('Year'));
        $this->type_text('placeformed', T_('Place'));

        if (AmpConfig::get('ratings')) {
            $this->type_select('myrating', T_('My Rating'), 'numeric', $this->stars);
            $this->type_select('rating', T_('Rating (Average)'), 'numeric', $this->stars);
        }

        if (AmpConfig::get('userflags')) {
            $this->type_text('favorite', T_('Favorites'));
        }

        /* HINT: Number of times object has been played */
        $this->type_numeric('played_times', T_('# Played'));
        $this->type_numeric('last_play', T_('My Last Play'), 'days');
        $this->type_numeric('last_skip', T_('My Last Skip'), 'days');
        $this->type_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days');
        $this->type_boolean('played', T_('Played'));
        $this->type_boolean('myplayed', T_('Played by Me'));
        $this->type_numeric('time', T_('Length (in minutes)'));

        $this->type_text('tag', T_('Genre'));

        $users = array();
        foreach ($this->getUserRepository()->getValid() as $user_id) {
            $user            = new User($user_id);
            $users[$user_id] = $user->username;
        }
        $this->type_select('other_user', T_('Another User'), 'user_numeric', $users);

        $this->type_numeric('recent_played', T_('Recently played'), 'recent_played');

        $catalogs = array();
        foreach (Catalog::get_catalogs('music', (int)$this->search_user) as $catid) {
            $catalog = Catalog::create_from_id($catid);
            $catalog->format();
            $catalogs[$catid] = $catalog->f_name;
        }
        if (!empty($catalogs)) {
            $this->type_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs);
        }

        $this->type_text('mbid', T_('MusicBrainz ID'));

        $this->type_boolean('has_image', T_('Local Image'));
        $this->type_numeric('image_width', T_('Image Width'));
        $this->type_numeric('image_height', T_('Image Height'));
        $this->type_boolean('possible_duplicate', T_('Possible Duplicate'), 'is_true');
        $this->type_boolean('possible_duplicate_album', T_('Possible Duplicate Albums'), 'is_true');
    } // artisttypes

    /**
     * albumtypes
     *
     * this is where all the searchtypes for albums are defined
     */
    private function album_types()
    {
        $this->type_text('title', T_('Title'));
        $this->type_text('artist', T_('Album Artist'));

        $this->type_numeric('year', T_('Year'));
        $this->type_numeric('original_year', T_('Original Year'));
        $this->type_text('release_type', T_('Release Type'));
        $this->type_text('release_status', T_('Release Status'));

        if (AmpConfig::get('ratings')) {
            $this->type_select('myrating', T_('My Rating'), 'numeric', $this->stars);
            $this->type_select('rating', T_('Rating (Average)'), 'numeric', $this->stars);
            $this->type_select('artistrating', T_('My Rating (Artist)'), 'numeric', $this->stars);
        }
        if (AmpConfig::get('userflags')) {
            $this->type_text('favorite', T_('Favorites'));
        }

        /* HINT: Number of times object has been played */
        $this->type_numeric('played_times', T_('# Played'));
        $this->type_numeric('last_play', T_('My Last Play'), 'days');
        $this->type_numeric('last_skip', T_('My Last Skip'), 'days');
        $this->type_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days');
        $this->type_boolean('played', T_('Played'));
        $this->type_boolean('myplayed', T_('Played by Me'));
        $this->type_numeric('time', T_('Length (in minutes)'));

        $this->type_text('tag', T_('Genre'));

        $users = array();
        foreach ($this->getUserRepository()->getValid() as $user_id) {
            $user            = new User($user_id);
            $users[$user_id] = $user->username;
        }
        $this->type_select('other_user', T_('Another User'), 'user_numeric', $users);

        $this->type_numeric('recent_played', T_('Recently played'), 'recent_played');

        $catalogs = array();
        foreach (Catalog::get_catalogs('music', (int)$this->search_user) as $catid) {
            $catalog = Catalog::create_from_id($catid);
            $catalog->format();
            $catalogs[$catid] = $catalog->f_name;
        }
        if (!empty($catalogs)) {
            $this->type_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs);
        }

        $this->type_text('mbid', T_('MusicBrainz ID'));

        $this->type_boolean('has_image', T_('Local Image'));
        $this->type_numeric('image_width', T_('Image Width'));
        $this->type_numeric('image_height', T_('Image Height'));
        $this->type_boolean('possible_duplicate', T_('Possible Duplicate'), 'is_true');
    } // albumtypes

    /**
     * videotypes
     *
     * this is where all the searchtypes for videos are defined
     */
    private function video_types()
    {
        $this->type_text('file', T_('Filename'));
    }

    /**
     * playlisttypes
     *
     * this is where all the searchtypes for playlists are defined
     */
    private function playlist_types()
    {
        $this->type_text('title', T_('Name'));
    }

    /**
     * labeltypes
     *
     * this is where all the searchtypes for labels are defined
     */
    private function label_types()
    {
        $this->type_text('title', T_('Name'));
        $this->type_text('category', T_('Category'));
    }

    /**
     * usertypes
     *
     * this is where all the searchtypes for users are defined
     */
    private function user_types()
    {
        $this->type_text('username', T_('Username'));
    }

    /**
     * clean_request
     *
     * Sanitizes raw search data
     * @param array $data
     * @return array
     */
    public static function clean_request($data)
    {
        $request = array();
        foreach ($data as $key => $value) {
            $prefix = substr($key, 0, 4);
            $value  = trim((string)$value);

            if ($prefix == 'rule' && strlen((string)$value)) {
                $request[$key] = Dba::escape(filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
            }
        }

        // Figure out if they want an AND based search or an OR based search
        switch ($data['operator']) {
            case 'or':
                $request['operator'] = 'OR';
                break;
            default:
                $request['operator'] = 'AND';
                break;
        }

        // Verify the type
        switch ($data['type']) {
            case 'album':
            case 'artist':
            case 'video':
            case 'song':
            case 'tag':  // for Genres
            case 'playlist':
            case 'label':
            case 'user':
                $request['type'] = $data['type'];
                break;
            default:
                $request['type'] = 'song';
                break;
        }

        return $request;
    } // end clean_request

    /**
     * get_name_byid
     *
     * Returns the name of the saved search corresponding to the given ID
     * @param string $search_id
     * @return string
     */
    public static function get_name_byid($search_id)
    {
        $sql        = "SELECT `name` FROM `search` WHERE `id` = '$search_id'";
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_assoc($db_results);

        return $row['name'];
    }

    /**
     * get_searches
     *
     * Return the IDs of all saved searches accessible by the current user.
     * @return array
     */
    public static function get_searches()
    {
        $sql = "SELECT `id` FROM `search` WHERE `type`='public' OR `user`='" . Core::get_global('user')->id . "' ORDER BY `name`";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

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
        $limit  = (int)($data['limit']);
        $offset = (int)($data['offset']);
        $random = ((int)$data['random'] > 0) ? 1 : 0;
        $data   = self::clean_request($data);
        $search = new Search(null, $data['type'], $user);
        $search->parse_rules($data);

        // Generate BASE SQL
        $limit_sql = "";
        if ($limit > 0) {
            $limit_sql = ' LIMIT ';
            if ($offset) {
                $limit_sql .= $offset . ",";
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

        //debug_event(self::class, 'SQL get_items: ' . $sql, 5);
        $db_results = Dba::read($sql);
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

        $this->link   = AmpConfig::get('web_path') . '/smartplaylist.php?action=show_playlist&playlist_id=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '">' . $this->f_name . '</a>';
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
        //debug_event(self::class, 'SQL get_items: ' . $sql, 5);

        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array(
                'object_id' => $row['id'],
                'object_type' => $this->searchtype
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
            $sql       = "UPDATE `search` SET `" . Dba::escape($column) . "` = " . $count . " WHERE `id` = ?";
            Dba::write($sql, array($search_id));
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
        if ($rating_filter > 0 && $rating_filter <= 5 && Core::get_global('user')) {
            $user_id = Core::get_global('user')->id;
            if (empty($sqltbl['where_sql'])) {
                $sql .= " WHERE ";
            } else {
                $sql .= " AND ";
            }
            $sql .= "`" . $this->searchtype . "`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '" . $this->searchtype . "' AND `rating`.`rating` <=$rating_filter AND `rating`.`user` = $user_id)";
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
        //debug_event(self::class, 'SQL get_random_items: ' . $sql, 5);

        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array(
                'object_id' => $row['id'],
                'object_type' => $this->searchtype
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
        $results    = Dba::fetch_row($db_results);

        return (int)$results['0'];
    } // get_total_duration

    /**
     * name_to_basetype
     *
     * Iterates over our array of types to find out the basetype for
     * the passed string.
     * @param string $name
     * @return string|false
     */
    public function name_to_basetype($name)
    {
        foreach ($this->types as $type) {
            if ($type['name'] == $name) {
                return $type['type'];
            }
        }

        return false;
    }

    /**
     * parse_rules
     *
     * Takes an array of sanitized search data from the form and generates our real array from it.
     * @param array $data
     */
    public function parse_rules($data)
    {
        // check that a limit or random flag have been sent
        $this->random = (isset($data['random'])) ? (int) $data['random'] : $this->random;
        $this->limit  = (isset($data['limit'])) ? (int) $data['limit'] : $this->limit;
        // parse the remaining rule* keys
        $this->rules  = array();
        foreach ($data as $rule => $value) {
            if ((($this->searchtype == 'artist' && $value == 'artist') || $value == 'name') && preg_match('/^rule_[0123456789]*$/', $rule)) {
                $value = 'title';
            }
            if (preg_match('/^rule_(\d+)$/', $rule, $ruleID)) {
                $ruleID     = (string)$ruleID[1];
                $input_rule = (string)$data['rule_' . $ruleID . '_input'];
                $operator   = $this->basetypes[$this->name_to_basetype($value)][$data['rule_' . $ruleID . '_operator']]['name'];
                //keep vertical bar in regular expression
                if (in_array($operator, ['regexp', 'notregexp'])) {
                    $input_rule = str_replace("|", "\0", $input_rule);
                }
                foreach (explode('|', $input_rule) as $input) {
                    $this->rules[] = array(
                        $value,
                        $operator,
                        in_array($operator, ['regexp', 'notregexp']) ? str_replace("\0", "|", $input) : $input,
                        $data['rule_' . $ruleID . '_subtype']
                    );
                }
            }
        }
        $this->logic_operator = $data['operator'];
    }

    /**
     * save
     *
     * Save this search to the database for use as a smart playlist
     * @return string
     */
    public function save()
    {
        // Make sure we have a unique name
        if (!$this->name) {
            $this->name = Core::get_global('user')->username . ' - ' . get_datetime(time());
        }
        $sql        = "SELECT `id` FROM `search` WHERE `name` = ?";
        $db_results = Dba::read($sql, array($this->name));
        if (Dba::num_rows($db_results)) {
            $this->name .= uniqid('', true);
        }

        $sql = "INSERT INTO `search` (`name`, `type`, `user`, `rules`, `logic_operator`, `random`, `limit`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array(
            $this->name,
            $this->type,
            Core::get_global('user')->id,
            json_encode($this->rules),
            $this->logic_operator,
            ($this->random > 0) ? 1 : 0,
            $this->limit
        ));
        $insert_id = Dba::insert_id();
        $this->id  = (int)$insert_id;

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
        return call_user_func(array($this, $this->searchtype . "_to_sql"));
    }

    /**
     * update
     *
     * This function updates the saved version with the current settings.
     * @param array $data
     * @return integer
     */
    public function update(array $data = null)
    {
        if ($data && is_array($data)) {
            $this->name   = $data['name'];
            $this->type   = $data['pl_type'];
            $this->random = ((int)$data['random'] > 0 || $this->random) ? 1 : 0;
            $this->limit  = $data['limit'];
        }

        if (!$this->id) {
            return 0;
        }

        $sql = "UPDATE `search` SET `name` = ?, `type` = ?, `rules` = ?, `logic_operator` = ?, `random` = ?, `limit` = ? WHERE `id` = ?";
        Dba::write($sql, array(
            $this->name,
            $this->type,
            json_encode($this->rules),
            $this->logic_operator,
            $this->random,
            $this->limit,
            $this->id
        ));

        return $this->id;
    }

    /**
     * _mangle_data
     *
     * Private convenience function.  Mangles the input according to a set
     * of predefined rules so that we don't have to include this logic in
     * foo_to_sql.
     * @param array|string $data
     * @param string|false $type
     * @param array $operator
     * @return array|boolean|integer|string|string[]|null
     */
    private function _mangle_data($data, $type, $operator)
    {
        if ($operator['preg_match']) {
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
     * album_to_sql
     *
     * Handles the generation of the SQL for album searches.
     * @return array
     */
    private function album_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $join['tag'] = array();
        $groupdisks  = AmpConfig::get('album_group');

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            if (!$type) {
                return array();
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $raw_input          = $this->_mangle_data($rule[2], $type, $operator);
            $input              = filter_var($raw_input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $sql_match_operator = $operator['sql'];
            if ($groupdisks) {
                /* 'album_group' DEFAULT:
                 * `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`
                 */
                $group[] = "`album`.`prefix`";
                $group[] = "`album`.`name`";
                $group[] = "`album`.`album_artist`";
                $group[] = "`album`.`release_type`";
                $group[] = "`album`.`release_status`";
                $group[] = "`album`.`mbid`";
                $group[] = "`album`.`year`";
                $group[] = "`album`.`original_year`";
            } else {
                $group[] = "`album`.`id`";
                $group[] = "`album`.`disk`";
            }

            switch ($rule[0]) {
                case 'title':
                    $where[] = "(`album`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator '$input')";
                    break;
                case 'year':
                    $where[] = "`album`.`" . $rule[0] . "` $sql_match_operator '$input'";
                    break;
                case 'original_year':
                    $where[] = "(`album`.`original_year` $sql_match_operator '$input' OR " .
                        "(`album`.`original_year` IS NULL AND `album`.`year` $sql_match_operator '$input'))";
                    break;
                case 'time':
                    $input   = $input * 60;
                    $where[] = "`album`.`time` $sql_match_operator '$input'";
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "`average_rating`.`avg` $sql_match_operator '$input'";
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='album' GROUP BY `object_id`) AS `average_rating` on `average_rating`.`object_id` = `album`.`id` ";
                    break;
                case 'favorite':
                    $where[] = "(`album`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator '$input') AND `favorite_album_$user_id`.`user` = $user_id AND `favorite_album_$user_id`.`object_type` = 'album'";
                    // flag once per user
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_album_$user_id"))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $user_id) AS `favorite_album_$user_id` ON `album`.`id`=`favorite_album_$user_id`.`object_id` AND `favorite_album_$user_id`.`object_type` = 'album'"
                        : "";
                    break;
                case 'myrating':
                case 'artistrating':
                    // combine these as they all do the same thing just different tables
                    $looking = str_replace('rating', '', $rule[0]);
                    $column  = ($looking == 'my') ? 'id' : 'album_artist';
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
                    } elseif ($sql_match_operator == '<>' || $sql_match_operator == '<' || $sql_match_operator == '<=' || $sql_match_operator == '!=') {
                        $where[] = "(`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator $input OR `rating_" . $my_type . "_" . $user_id . "`.`rating` IS NULL)";
                    } else {
                        $where[] = "`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator $input";
                    }
                    // rating once per user
                    $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = $user_id AND `object_type`='$my_type') AS `rating_" . $my_type . "_" . $user_id . "` ON `rating_" . $my_type . "_" . $user_id . "`.`object_id`=`album`.`$column`"
                        : "";
                    break;
                case 'myplayed':
                    $column       = 'id';
                    $my_type      = 'album';
                    $operator_sql = ((int)$sql_match_operator == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $user_id . "` ON `album`.`$column`=`myplayed_" . $my_type . "_" . $user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`myplayed_" . $my_type . "_" . $user_id . "`.`object_id` $operator_sql";
                    break;
                case 'last_play':
                    $my_type = 'album';
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $user_id . "` ON `album`.`id`=`last_play_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`last_play_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_skip':
                    $my_type = 'album';
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id`=`last_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_skip_" . $my_type . "_" . $user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`last_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    $join['song'] = true;
                    break;
                case 'last_play_or_skip':
                    $my_type = 'album';
                    $table['last_play_or_skip'] .= (!strpos((string) $table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id`=`last_play_or_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_or_skip_" . $my_type . "_" . $user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`last_play_or_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    $join['song'] = true;
                    break;
                case 'played_times':
                    $where[] = "`album`.`id` IN (SELECT `object_count`.`object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` HAVING COUNT(*) $sql_match_operator '$input')";
                    break;
                case 'release_type':
                    $where[] = "`album`.`release_type` $sql_match_operator '$input'";
                    break;
                case 'release_status':
                    $where[] = "`album`.`release_status` $sql_match_operator '$input'";
                    break;
                case 'other_user':
                    $other_userid = $input;
                    if ($sql_match_operator == 'userflag') {
                        $where[] = "`favorite_album_$other_userid`.`user` = $other_userid AND `favorite_album_$other_userid`.`object_type` = 'album'";
                        // flag once per user
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_album_$other_userid"))
                            ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $other_userid) AS `favorite_album_$other_userid` ON `song`.`album`=`favorite_album_$other_userid`.`object_id` AND `favorite_album_$other_userid`.`object_type` = 'album'"
                            : "";
                    } else {
                        $column  = 'id';
                        $my_type = 'album';
                        $where[] = "`rating_album_" . $other_userid . '`.' . $sql_match_operator . " AND `rating_album_$other_userid`.`user` = $other_userid AND `rating_album_$other_userid`.`object_type` = 'album'";
                        // rating once per user
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $user_id))
                            ? "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $user_id . "` ON `rating_" . $my_type . "_" . $user_id . "`.`object_type`='$my_type' AND `rating_" . $my_type . "_" . $user_id . "`.`object_id`=`$my_type`.`$column` AND `rating_" . $my_type . "_" . $user_id . "`.`user` = $user_id "
                            : "";
                    }
                    break;
                case 'recent_played':
                    $key                     = md5($input . $sql_match_operator);
                    $where[]                 = "`played_$key`.`object_id` IS NOT NULL";
                    $table['played_' . $key] = "LEFT JOIN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'album' ORDER BY $sql_match_operator DESC LIMIT $input) as `played_$key` ON `album`.`id` = `played_$key`.`object_id`";
                    break;
                case 'catalog':
                    $where[] = "`album`.`catalog` $sql_match_operator '$input'";
                    break;
                case 'tag':
                    $where[] = "`album`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` WHERE `tag_map`.`object_type`='album' AND `tag_map`.`tag_id` IN (SELECT `tag`.`id` FROM `tag` WHERE `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator '$input'))";
                    break;
                case 'has_image':
                    $where[]            = ($sql_match_operator == '1') ? "`has_image`.`object_id` IS NOT NULL" : "`has_image`.`object_id` IS NULL";
                    $table['has_image'] = "LEFT JOIN (SELECT `object_id` FROM `image` WHERE `object_type` = 'album') as `has_image` ON `album`.`id` = `has_image`.`object_id`";
                    break;
                case 'image_height':
                case 'image_width':
                    $looking       = strpos($rule[0], "image_") ? str_replace('image_', '', $rule[0]) : str_replace('image ', '', $rule[0]);
                    $where[]       = "`image`.`$looking` $sql_match_operator '$input'";
                    $join['image'] = true;
                    break;
                case 'artist':
                    $where[]         = "(`artist`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator '$input')";
                    $table['artist'] = "LEFT JOIN `artist` ON `album`.`album_artist`=`artist`.`id`";
                    break;
                case 'mbid':
                    $where[] = "`album`.`mbid` $sql_match_operator '$input'";
                    break;
                case 'possible_duplicate':
                    $where[]               = "(`dupe_search1`.`dupe_id1` IS NOT NULL OR `dupe_search2`.`dupe_id2` IS NOT NULL)";
                    $table['dupe_search1'] = "LEFT JOIN (SELECT MIN(`id`) AS `dupe_id1`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk`, `year`, `release_type`, `release_status` HAVING `Counting` > 1) AS `dupe_search1` ON `album`.`id` = `dupe_search1`.`dupe_id1`";
                    $table['dupe_search2'] = "LEFT JOIN (SELECT MAX(`id`) AS `dupe_id2`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk`, `year`, `release_type`, `release_status` HAVING `Counting` > 1) AS `dupe_search2` ON `album`.`id` = `dupe_search2`.`dupe_id2`";
                    break;
                default:
                    break;
            } // switch on ruletype album
        } // foreach rule

        $join['song']        = $join['song'] || $catalog_disable || $catalog_filter;
        $join['catalog']     = $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($join['song']) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`album`=`album`.`id`";
        }
        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            } else {
                $where_sql .= " `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            }
        }
        if ($join['catalog_map']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_album` ON `catalog_map_album`.`object_id`=`album`.`id` AND `catalog_map_album`.`object_type` = 'album' AND `catalog_map_album`.`catalog_id` = `catalog_se`.`id`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`filter_user` IN (0, $user_id)";
            } else {
                $where_sql .= " `catalog_se`.`filter_user` IN (0, $user_id)";
            }
        }
        if ($join['count']) {
            $table['object_count'] = "LEFT JOIN (SELECT `object_count`.`object_id`, MAX(`object_count`.`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`user`='" . $user_id . "' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` ON `object_count`.`object_id`=`album`.`id`";
        }
        if ($join['image']) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`album`=`album`.`id` LEFT JOIN `image` ON `image`.`object_id`=`album`.`id`";
            $where_sql .= " AND `image`.`object_type`='album'";
            $where_sql .= " AND `image`.`size`='original'";
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => ($groupdisks) ? 'SELECT MIN(`album`.`id`) AS `id` FROM `album`' : 'SELECT MIN(`album`.`id`) AS `id`, MAX(`album`.`disk`) AS `disk` FROM `album`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql
        );
    }

    /**
     * artist_to_sql
     *
     * Handles the generation of the SQL for artist searches.
     * @return array
     */
    private function artist_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            if (!$type) {
                return array();
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $raw_input          = $this->_mangle_data($rule[2], $type, $operator);
            $input              = filter_var($raw_input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'title':
                case 'name':
                    $where[] = "(`artist`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator '$input')";
                    break;
                case 'yearformed':
                    $where[] = "`artist`.`yearformed` $sql_match_operator '$input'";
                    break;
                case 'placeformed':
                    $where[] = "`artist`.`placeformed` $sql_match_operator '$input'";
                    break;
                case 'time':
                    $input   = $input * 60;
                    $where[] = "`artist`.`time` $sql_match_operator '$input'";
                    break;
                case 'tag':
                    $where[] = "`artist`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` WHERE `tag_map`.`object_type`='artist' AND `tag_map`.`tag_id` IN (SELECT `tag`.`id` FROM `tag` WHERE `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator '$input'))";
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "`average_rating`.`avg` $sql_match_operator '$input'";
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='artist' GROUP BY `object_id`) AS `average_rating` on `average_rating`.`object_id` = `artist`.`id` ";
                    break;
                case 'favorite':
                    $where[] = "(`artist`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator '$input') AND `favorite_artist_$user_id`.`user` = $user_id AND `favorite_artist_$user_id`.`object_type` = 'artist'";
                    // flag once per user
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_artist_$user_id"))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $user_id) AS `favorite_artist_$user_id` ON `artist`.`id`=`favorite_artist_$user_id`.`object_id` AND `favorite_artist_$user_id`.`object_type` = 'artist'"
                        : "";
                    break;
                case 'has_image':
                    $where[]            = ($sql_match_operator == '1') ? "`has_image`.`object_id` IS NOT NULL" : "`has_image`.`object_id` IS NULL";
                    $table['has_image'] = "LEFT JOIN (SELECT `object_id` FROM `image` WHERE `object_type` = 'artist') as `has_image` ON `artist`.`id` = `has_image`.`object_id`";
                    break;
                case 'image_height':
                case 'image_width':
                    $looking       = strpos($rule[0], "image_") ? str_replace('image_', '', $rule[0]) : str_replace('image ', '', $rule[0]);
                    $where[]       = "`image`.`$looking` $sql_match_operator '$input'";
                    $join['image'] = true;
                    break;
                case 'myrating':
                    // combine these as they all do the same thing just different tables
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
                    } elseif ($sql_match_operator == '<>' || $sql_match_operator == '<' || $sql_match_operator == '<=' || $sql_match_operator == '!=') {
                        $where[] = "(`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator $input OR `rating_" . $my_type . "_" . $user_id . "`.`rating` IS NULL)";
                    } else {
                        $where[] = "`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator $input";
                    }
                    // rating once per user
                    $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = $user_id AND `object_type`='$my_type') AS `rating_" . $my_type . "_" . $user_id . "` ON `rating_" . $my_type . "_" . $user_id . "`.`object_id`=`artist`.`$column`"
                        : "";
                    break;
                case 'myplayed':
                    $column       = 'id';
                    $my_type      = 'artist';
                    $operator_sql = ((int)$sql_match_operator == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $user_id . "` ON `artist`.`$column`=`myplayed_" . $my_type . "_" . $user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`myplayed_" . $my_type . "_" . $user_id . "`.`object_id` $operator_sql";
                    break;
                case 'last_play':
                    $my_type = 'artist';
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $user_id . "` ON `artist`.`id`=`last_play_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`last_play_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_skip':
                    $my_type = 'artist';
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id`=`last_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_skip_" . $my_type . "_" . $user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`last_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    $join['song'] = true;
                    break;
                case 'last_play_or_skip':
                    $my_type = 'artist';
                    $table['last_play_or_skip'] .= (!strpos((string) $table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id`=`last_play_or_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_or_skip_" . $my_type . "_" . $user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`last_play_or_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    $join['song'] = true;
                    break;
                case 'played_times':
                    $where[] = "`artist`.`id` IN (SELECT `object_count`.`object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'artist' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` HAVING COUNT(*) $sql_match_operator '$input')";
                    break;
                case 'other_user':
                    $other_userid = $input;
                    if ($sql_match_operator == 'userflag') {
                        $where[] = "`favorite_artist_$other_userid`.`user` = $other_userid AND `favorite_artist_$other_userid`.`object_type` = 'artist'";
                        // flag once per user
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_artist_$other_userid"))
                            ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $other_userid) AS `favorite_artist_$other_userid` ON `song`.`artist`=`favorite_artist_$other_userid`.`object_id` AND `favorite_artist_$other_userid`.`object_type` = 'artist'"
                            : "";
                    } else {
                        $column  = 'id';
                        $my_type = 'artist';
                        $where[] = "`rating_artist_" . $other_userid . '`.' . $sql_match_operator . " AND `rating_artist_$other_userid`.`user` = $other_userid AND `rating_artist_$other_userid`.`object_type` = 'artist'";
                        // rating once per user
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $user_id))
                            ? "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $user_id . "` ON `rating_" . $my_type . "_" . $user_id . "`.`object_type`='$my_type' AND `rating_" . $my_type . "_" . $user_id . "`.`object_id`=`$my_type`.`$column` AND `rating_" . $my_type . "_" . $user_id . "`.`user` = $user_id "
                            : "";
                    }
                    break;
                case 'recent_played':
                    $key                     = md5($input . $sql_match_operator);
                    $where[]                 = "`played_$key`.`object_id` IS NOT NULL";
                    $table['played_' . $key] = "LEFT JOIN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'artist' ORDER BY $sql_match_operator DESC LIMIT $input) as `played_$key` ON `artist`.`id` = `played_$key`.`object_id`";
                    break;
                case 'catalog':
                    $where[]                = "`artist_catalog`.`catalog_id` $sql_match_operator '$input'";
                    $join['artist_catalog'] = true;
                    break;
                case 'mbid':
                    $where[] = "`artist`.`mbid` $sql_match_operator '$input'";
                    break;
                case 'possible_duplicate':
                    $where[]               = "(`dupe_search1`.`dupe_id1` IS NOT NULL OR `dupe_search2`.`dupe_id2` IS NOT NULL)";
                    $table['dupe_search1'] = "LEFT JOIN (SELECT MIN(`id`) AS `dupe_id1`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`))) AS `Counting` FROM `artist` GROUP BY `fullname` HAVING `Counting` > 1) AS `dupe_search1` ON `artist`.`id` = `dupe_search1`.`dupe_id1`";
                    $table['dupe_search2'] = "LEFT JOIN (SELECT MAX(`id`) AS `dupe_id2`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`))) AS `Counting` FROM `artist` GROUP BY `fullname` HAVING `Counting` > 1) AS `dupe_search2` ON `artist`.`id` = `dupe_search2`.`dupe_id2`";
                    break;
                case 'possible_duplicate_album':
                    $where[]                     = "(`dupe_album_search1`.`dupe_album_id1` IS NOT NULL OR `dupe_album_search2`.`dupe_album_id2` IS NOT NULL)";
                    $table['dupe_album_search1'] = "LEFT JOIN (SELECT album_artist, MIN(`id`) AS `dupe_album_id1`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk`, `year`, `release_type`, `release_status` HAVING `Counting` > 1) AS `dupe_album_search1` ON `artist`.`id` = `dupe_album_search1`.`album_artist`";
                    $table['dupe_album_search2'] = "LEFT JOIN (SELECT album_artist, MAX(`id`) AS `dupe_album_id2`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk`, `year`, `release_type`, `release_status` HAVING `Counting` > 1) AS `dupe_album_search2` ON `artist`.`id` = `dupe_album_search2`.`album_artist`";
                    break;
                default:
                    break;
            } // switch on ruletype artist
        } // foreach rule

        $join['song']        = $join['song'] || $catalog_disable || $catalog_filter;
        $join['catalog']     = $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($join['song']) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`artist`=`artist`.`id`";
        }
        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            } else {
                $where_sql .= " `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            }
        }
        if ($join['catalog_map']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_artist` ON `catalog_map_artist`.`object_id`=`artist`.`id` AND `catalog_map_artist`.`object_type` = 'artist' AND `catalog_map_artist`.`catalog_id` = `catalog_se`.`id`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`filter_user` IN (0, $user_id)";
            } else {
                $where_sql .= " `catalog_se`.`filter_user` IN (0, $user_id)";
            }
        }
        if ($join['count']) {
            $table['object_count'] = "LEFT JOIN (SELECT `object_count`.`object_id`, MAX(`object_count`.`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'artist' AND `object_count`.`user`='" . $user_id . "' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` ON `object_count`.`object_id`=`artist`.`id`";
        }
        if ($join['image']) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`artist`=`artist`.`id` LEFT JOIN `image` ON `image`.`object_id`=`artist`.`id`";
            $where_sql .= " AND `image`.`object_type`='artist'";
            $where_sql .= " AND `image`.`size`='original'";
        }
        if ($join['artist_catalog']) {
            $table['catalog_map'] = "LEFT JOIN `catalog_map` AS `artist_catalog` ON `artist_catalog`.`object_type` = 'artist' AND `artist_catalog`.`object_id`=`artist`.`id`";
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
            'having_sql' => $having_sql
        );
    }

    /**
     * song_to_sql
     * Handles the generation of the SQL for song searches.
     * @return array
     */
    private function song_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $metadata    = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            if (!$type) {
                return array();
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $raw_input          = $this->_mangle_data($rule[2], $type, $operator);
            $input              = filter_var($raw_input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'anywhere':
                    $tag_string = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` WHERE `tag_map`.`object_type`='song' AND `tag_map`.`tag_id` IN (SELECT `tag`.`id` FROM `tag` WHERE `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator '$input'))";
                    // we want AND NOT and like for this query to really exclude them
                    if ($sql_match_operator == 'NOT LIKE' || $sql_match_operator == 'NOT' || $sql_match_operator == '!=') {
                        $where[] = "NOT ((`artist`.`name` LIKE '$input' OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) LIKE '$input') OR (`album`.`name` LIKE '$input' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) LIKE '$input') OR `song_data`.`comment` LIKE '$input' OR `song_data`.`label` LIKE '$input' OR `song`.`file` LIKE '$input' OR `song`.`title` LIKE '$input' OR NOT " . $tag_string . ')';
                    } else {
                        $where[] = "((`artist`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator '$input') OR (`album`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator '$input') OR `song_data`.`comment` $sql_match_operator '$input' OR `song_data`.`label` $sql_match_operator '$input' OR `song`.`file` $sql_match_operator '$input' OR `song`.`title` $sql_match_operator '$input' OR " . $tag_string . ')';
                    }
                    // join it all up
                    $table['album']    = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
                    $table['artist']   = "LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`";
                    $join['song_data'] = true;
                    break;
                case 'tag':
                    $where[] = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` WHERE `tag_map`.`object_type`='song' AND `tag_map`.`tag_id` IN (SELECT `tag`.`id` FROM `tag` WHERE `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator '$input'))";
                    break;
                case 'album_tag':
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
                    $where[]        = "`album`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` WHERE `tag_map`.`object_type`='album' AND `tag_map`.`tag_id` IN (SELECT `tag`.`id` FROM `tag` WHERE `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator '$input'))";
                    break;
                case 'artist_tag':
                    $table['artist'] = "LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`";
                    $where[]         = "`artist`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` WHERE `tag_map`.`object_type`='artist' AND `tag_map`.`tag_id` IN (SELECT `tag`.`id` FROM `tag` WHERE `tag`.`is_hidden` = 0 AND `tag`.`name` $sql_match_operator '$input'))";
                    break;
                case 'title':
                    $where[] = "`song`.`title` $sql_match_operator '$input'";
                    break;
                case 'album':
                    $where[]        = "(`album`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator '$input')";
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
                    break;
                case 'artist':
                    $where[]         = "(`artist`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator '$input')";
                    $table['artist'] = "LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`";
                    break;
                case 'album_artist':
                    $where[]         = "(`album_artist`.`name` $sql_match_operator '$input' " .
                        " OR LTRIM(CONCAT(COALESCE(`album_artist`.`prefix`, ''), " .
                        "' ', `album_artist`.`name`)) $sql_match_operator '$input')";
                    $table['album']        = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
                    $table['album_artist'] = "LEFT JOIN `artist` AS `album_artist` ON `album`.`album_artist`=`album_artist`.`id`";
                    break;
                case 'composer':
                    $where[] = "`song`.`composer` $sql_match_operator '$input'";
                    break;
                case 'time':
                    $input   = $input * 60;
                    $where[] = "`song`.`time` $sql_match_operator '$input'";
                    break;
                case 'file':
                    $where[] = "`song`.`file` $sql_match_operator '$input'";
                    break;
                case 'year':
                    $where[] = "`song`.`year` $sql_match_operator '$input'";
                    break;
                case 'comment':
                    $where[]           = "`song_data`.`comment` $sql_match_operator '$input'";
                    $join['song_data'] = true;
                    break;
                case 'label':
                    $where[]           = "`song_data`.`label` $sql_match_operator '$input'";
                    $join['song_data'] = true;
                    break;
                case 'lyrics':
                    $where[]           = "`song_data`.`lyrics` $sql_match_operator '$input'";
                    $join['song_data'] = true;
                    break;
                case 'played':
                    $where[] = "`song`.`played` = '$sql_match_operator'";
                    break;
                case 'last_play':
                    $my_type = 'song';
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $user_id . "` ON `song`.`id`=`last_play_" . $my_type . "_" . $user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`last_play_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_skip':
                    $my_type = 'song';
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $user_id))
                        ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'skip' " .
                        "AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $user_id . "` " .
                        "ON `song`.`id`=`last_skip_" . $my_type . "_" . $user_id . "`.`object_id` " .
                        "AND `last_skip_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type' " : "";
                    $where[] = "`last_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_play_or_skip':
                    $my_type = 'song';
                    $table['last_play_or_skip'] .= (!strpos((string) $table['play_or_skip'], "play_or_skip_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `play_or_skip_" . $my_type . "_" . $user_id . "` ON `song`.`id`=`play_or_skip_" . $my_type . "_" . $user_id . "`.`object_id` AND `play_or_skip_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`play_or_skip_" . $my_type . "_" . $user_id . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'played_times':
                    $where[] = "`song`.`id` IN (SELECT `object_count`.`object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` HAVING COUNT(*) $sql_match_operator '$input')";
                    break;
                case 'skipped_times':
                    $where[] = "`song`.`id` IN (SELECT `object_count`.`object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' GROUP BY `object_count`.`object_id` HAVING COUNT(*) $sql_match_operator '$input')";
                    break;
                case 'played_or_skipped_times':
                    $where[] = "`song`.`id` IN (SELECT `object_count`.`object_id` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') " .
                        "GROUP BY `object_count`.`object_id` HAVING COUNT(*) $sql_match_operator '$input')";
                    break;
                case 'play_skip_ratio':
                    $where[] = "`song`.`id` IN (SELECT `song`.`id` FROM `song` LEFT JOIN (SELECT COUNT(`object_id`) AS `counting`, `object_id`, `count_type` FROM `object_count` WHERE `object_type` = 'song' AND `count_type` = 'stream' GROUP BY `object_id`, `count_type`) AS `stream_count` on `song`.`id` = `stream_count`.`object_id` LEFT JOIN (SELECT COUNT(`object_id`) AS `counting`, `object_id`, `count_type` FROM `object_count` WHERE `object_type` = 'song' AND `count_type` = 'skip' GROUP BY `object_id`, `count_type`) AS `skip_count` on `song`.`id` = `skip_count`.`object_id` WHERE ((IFNULL(`stream_count`.`counting`, 0)/IFNULL(`skip_count`.`counting`, 0)) * 100) $sql_match_operator '$input' GROUP BY `song`.`id`)";
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
                $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $user_id))
                    ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user`=$user_id GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $user_id . "` ON `song`.`$column`=`myplayed_" . $my_type . "_" . $user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $user_id . "`.`object_type` = '$my_type'"
                    : "";
                $where[] = "`myplayed_" . $my_type . "_" . $user_id . "`.`object_id` $operator_sql";
                    break;
                case 'bitrate':
                    $input   = $input * 1000;
                    $where[] = "`song`.`bitrate` $sql_match_operator '$input'";
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "`average_rating`.`avg` $sql_match_operator '$input'";
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='song' GROUP BY `object_id`) AS `average_rating` on `average_rating`.`object_id` = `song`.`id` ";
                    break;
                case 'favorite':
                    $where[] = "`song`.`title` $sql_match_operator '$input' AND `favorite_song_$user_id`.`user` = $user_id AND `favorite_song_$user_id`.`object_type` = 'song'";
                    // flag once per user
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_song_$user_id"))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $user_id) AS `favorite_song_$user_id` ON `song`.`id`=`favorite_song_$user_id`.`object_id` AND `favorite_song_$user_id`.`object_type` = 'song'"
                        : "";
                    break;
                case 'favorite_album':
                    $where[] = "(`album`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator '$input') AND `favorite_album_$user_id`.`user` = $user_id AND `favorite_album_$user_id`.`object_type` = 'album'";
                    // flag once per user
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_album_$user_id"))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $user_id) AS `favorite_album_$user_id` ON `album`.`id`=`favorite_album_$user_id`.`object_id` AND `favorite_album_$user_id`.`object_type` = 'album'"
                        : "";
                    $join['album'] = true;
                    break;
                case 'favorite_artist':
                    $where[] = "(`artist`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator '$input') AND `favorite_artist_$user_id`.`user` = $user_id AND `favorite_artist_$user_id`.`object_type` = 'artist'";
                    // flag once per user
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_artist_$user_id"))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $user_id) AS `favorite_artist_$user_id` ON `artist`.`id`=`favorite_artist_$user_id`.`object_id` AND `favorite_artist_$user_id`.`object_type` = 'artist'"
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
                    } elseif ($sql_match_operator == '<>' || $sql_match_operator == '<' || $sql_match_operator == '<=' || $sql_match_operator == '!=') {
                        $where[] = "(`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator $input OR `rating_" . $my_type . "_" . $user_id . "`.`rating` IS NULL)";
                    } else {
                        $where[] = "`rating_" . $my_type . "_" . $user_id . "`.`rating` $sql_match_operator $input";
                    }
                    // rating once per user
                    $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = $user_id AND `object_type`='$my_type') AS `rating_" . $my_type . "_" . $user_id . "` ON `rating_" . $my_type . "_" . $user_id . "`.`object_id`=`song`.`$column`"
                        : "";
                    break;
                case 'catalog':
                    $where[] = "`song`.`catalog` $sql_match_operator '$input'";
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
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_" . $my_type . "_" . $other_userid . ""))
                            ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $other_userid) AS `favorite_" . $my_type . "_" . $other_userid . "` ON `song`.`$column`=`favorite_" . $my_type . "_" . $other_userid . "`.`object_id` AND `favorite_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type'"
                            : "";
                    } else {
                        $unrated = ($sql_match_operator == 'unrated');
                        $where[] = ($unrated) ? "`song`.`$column` NOT IN (SELECT `object_id` FROM `rating` WHERE `object_type` = '$my_type' AND `user` = $other_userid)" : "`rating_" . $my_type . "_" . $other_userid . "`.$sql_match_operator AND `rating_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid AND `rating_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type'";
                        // rating once per user
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $other_userid))
                            ? "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $other_userid . "` ON `rating_" . $my_type . "_" . $other_userid . "`.`object_type`='$my_type' AND `rating_" . $my_type . "_" . $other_userid . "`.`object_id`=`song`.`$column` AND `rating_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid "
                            : "";
                    }
                    break;
                case 'playlist_name':
                    $join['playlist']      = true;
                    $join['playlist_data'] = true;
                    $where[]               = "`playlist`.`name` $sql_match_operator '$input'";
                    break;
                case 'playlist':
                    $join['playlist_data'] = true;
                    $where[]               = "`playlist_data`.`playlist` $sql_match_operator '$input'";
                    break;
                case 'smartplaylist':
                    $subsearch  = new Search($input, 'song', $this->search_user);
                    $results    = $subsearch->get_items();
                    $itemstring = '';
                    if (count($results) > 0) {
                        foreach ($results as $item) {
                            $itemstring .= ' ' . $item['object_id'] . ',';
                        }
                        $where[]  = "`song`.`id` $sql_match_operator IN (" . substr($itemstring, 0, -1) . ")";
                    }
                    break;
                case 'license':
                    $where[] = "`song`.`license` $sql_match_operator '$input'";
                    break;
                case 'added':
                    $input   = strtotime((string) $input);
                    $where[] = "`song`.`addition_time` $sql_match_operator $input";
                    break;
                case 'updated':
                    $input   = strtotime((string) $input);
                    $where[] = "`song`.`update_time` $sql_match_operator $input";
                    break;
                case 'recent_played':
                    $key                     = md5($input . $sql_match_operator);
                    $where[]                 = "`played_$key`.`object_id` IS NOT NULL";
                    $table['played_' . $key] = "LEFT JOIN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'song' ORDER BY $sql_match_operator DESC LIMIT $input) as `played_$key` ON `song`.`id` = `played_$key`.`object_id`";
                    break;
                case 'recent_added':
                    $key                       = md5($input . $sql_match_operator);
                    $where[]                   = "`addition_time_$key`.`id` IS NOT NULL";
                    $table['addition_' . $key] = "LEFT JOIN (SELECT `id` FROM `song` ORDER BY $sql_match_operator DESC LIMIT $input) as `addition_time_$key` ON `song`.`id` = `addition_time_$key`.`id`";
                    break;
                case 'recent_updated':
                    $key                     = md5($input . $sql_match_operator);
                    $where[]                 = "`update_time_$key`.`id` IS NOT NULL";
                    $table['update_' . $key] = "LEFT JOIN (SELECT `id` FROM `song` ORDER BY $sql_match_operator DESC LIMIT $input) as `update_time_$key` ON `song`.`id` = `update_time_$key`.`id`";
                    break;
                case 'mbid':
                    $where[] = "`song`.`mbid` $sql_match_operator '$input'";
                    break;
                case 'mbid_album':
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
                    $where[]        = "`album`.`mbid` $sql_match_operator '$input'";
                    break;
                case 'mbid_artist':
                    $table['artist'] = "LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`";
                    $where[]         = "`artist`.`mbid` $sql_match_operator '$input'";
                    break;
                case 'possible_duplicate':
                    $where[]               = "(`dupe_search1`.`dupe_id1` IS NOT NULL OR `dupe_search2`.`dupe_id2` IS NOT NULL)";
                    $table['dupe_search1'] = "LEFT JOIN (SELECT MIN(`song`.`id`) AS `dupe_id1`, CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`disk`, `song`.`title`) AS `fullname`, COUNT(CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`disk`, `song`.`title`)) AS `counting` FROM song LEFT JOIN `album` on song.album = album.id LEFT JOIN `artist` on song.artist = artist.id GROUP BY `fullname` HAVING `Counting` > 1) AS `dupe_search1` on `song`.`id` = `dupe_search1`.`dupe_id1`";
                    $table['dupe_search2'] = "LEFT JOIN (SELECT MAX(`song`.`id`) AS `dupe_id2`, CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`disk`, `song`.`title`) AS `fullname`, COUNT(CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`disk`, `song`.`title`)) AS `counting` FROM song LEFT JOIN `album` on song.album = album.id LEFT JOIN `artist` on song.artist = artist.id GROUP BY `fullname` HAVING `Counting` > 1) AS `dupe_search2` on `song`.`id` = `dupe_search2`.`dupe_id2`";
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
                        $metadata[$field][] = "`metadata`.`data` $sql_match_operator $parsedInput";
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
        if ($join['song_data']) {
            $table['song_data'] = "LEFT JOIN `song_data` ON `song`.`id`=`song_data`.`song_id`";
        }
        if ($join['playlist_data']) {
            $table['playlist_data'] = "LEFT JOIN `playlist_data` ON `song`.`id`=`playlist_data`.`object_id` AND `playlist_data`.`object_type`='song'";
            if ($join['playlist']) {
                $table['playlist'] = "LEFT JOIN `playlist` ON `playlist_data`.`playlist`=`playlist`.`id`";
            }
        }
        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            } else {
                $where_sql .= " `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            }
        }
        if ($join['catalog_map']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_song` ON `catalog_map_song`.`object_id`=`song`.`id` AND `catalog_map_song`.`object_type` = 'song' AND `catalog_map_song`.`catalog_id` = `catalog_se`.`id`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`filter_user` IN (0, $user_id)";
            } else {
                $where_sql .= " `catalog_se`.`filter_user` IN (0, $user_id)";
            }
        }
        if ($join['album']) {
            $table['album'] = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
        }
        if ($join['artist']) {
            $table['artist'] = "LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`";
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
            'having_sql' => $having_sql
        );
    }

    /**
     * video_to_sql
     *
     * Handles the generation of the SQL for video searches.
     * @return array
     */
    private function video_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where  = array();
        $table  = array();
        $join   = array();
        $group  = array();
        $having = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            if (!$type) {
                return array();
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $raw_input          = $this->_mangle_data($rule[2], $type, $operator);
            $input              = filter_var($raw_input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'file':
                    $where[] = "`video`.`file` $sql_match_operator '$input'";
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog_map'] = $catalog_filter;
        $join['catalog']     = $catalog_disable || $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`video`.`catalog`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`enabled` = '1' AND `video`.`enabled` = 1";
            } else {
                $where_sql .= " `catalog_se`.`enabled` = '1' AND `video`.`enabled` = 1";
            }
        }
        if ($join['catalog_map']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_video` ON `catalog_map_video`.`object_id`=`video`.`id` AND `catalog_map_video`.`object_type` = 'video' AND `catalog_map_video`.`catalog_id` = `catalog_se`.`id`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`filter_user` IN (0, $user_id)";
            } else {
                $where_sql .= " `catalog_se`.`filter_user` IN (0, $user_id)";
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
            'having_sql' => $having_sql
        );
    }

    /**
     * playlist_to_sql
     *
     * Handles the generation of the SQL for playlist searches.
     * @return array
     */
    private function playlist_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where  = array();
        $table  = array();
        $join   = array();
        $group  = array();
        $having = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            if (!$type) {
                return array();
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $raw_input          = $this->_mangle_data($rule[2], $type, $operator);
            $input              = filter_var($raw_input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $sql_match_operator = $operator['sql'];

            $where[] = "(`playlist`.`type` = 'public' OR `playlist`.`user`=" . $user_id . ")";

            switch ($rule[0]) {
                case 'title':
                case 'name':
                    $where[] = "`playlist`.`name` $sql_match_operator '$input'";
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['playlist_data'] = true;
        $join['song']          = $join['song'] || $catalog_disable || $catalog_filter;
        $join['catalog']       = $catalog_disable || $catalog_filter;
        $join['catalog_map']   = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($join['playlist_data']) {
            $table['0_playlist_data'] = "LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id`";
        }
        if ($join['song']) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`id`=`playlist_data`.`object_id`";
            $where_sql .= " AND `playlist_data`.`object_type` = 'song'";
        }
        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
            if ($catalog_disable) {
                if (!empty($where_sql)) {
                    $where_sql .= " AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
                } else {
                    $where_sql .= " `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
                }
            }
        }
        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`filter_user` IN (0, $user_id)";
            } else {
                $where_sql .= " `catalog_se`.`filter_user` IN (0, $user_id)";
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
            'having_sql' => $having_sql
        );
    }

    /**
     * label_to_sql
     *
     * Handles the generation of the SQL for label searches.
     * @return array
     */
    private function label_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where = array();
        $table = array();
        $join  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            if (!$type) {
                return array();
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $raw_input          = $this->_mangle_data($rule[2], $type, $operator);
            $input              = filter_var($raw_input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'title':
                case 'name':
                    $where[] = "`label`.`name` $sql_match_operator '$input'";
                    break;
                case 'category':
                    $where[] = "`label`.`category` $sql_match_operator '$input'";
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
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_artist` ON `catalog_map_artist`.`object_id`=`artist`.`id` AND `catalog_map_artist`.`object_type` = 'artist'";
        }

        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_map_artist`.`object_type` = 'artist' AND `catalog_se`.`filter_user` IN (0, $user_id)";
            } else {
                $where_sql .= " `catalog_map_artist`.`object_type` = 'artist' AND `catalog_se`.`filter_user` IN (0, $user_id)";
            }
        }
        if ($join['catalog']) {
            $table['3_catalog'] = "LEFT JOIN `catalog`AS `catalog_se` ON `catalog_map_artist`.`catalog_id` = `catalog_se`.`id`";
            if ($catalog_disable) {
                if (!empty($where_sql)) {
                    $where_sql .= " AND `catalog_se`.`enabled` = '1'";
                } else {
                    $where_sql .= " `catalog_se`.`enabled` = '1'";
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
            'having_sql' => ''
        );
    }

    /**
       * tag_to_sql
       *
       * Handles the generation of the SQL for tag (genre) searches.
       * @return array
       */

    private function tag_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $user_id            = $this->search_user->id;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where = array();
        $table = array();
        $join  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            if (!$type) {
                return array();
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $raw_input          = $this->_mangle_data($rule[2], $type, $operator);
            $input              = filter_var($raw_input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'title':
                case 'name':
                    $where[] = "`tag`.`name` $sql_match_operator '$input'";
                    break;
                case 'category':
                    $where[] = "`tag`.`category` $sql_match_operator '$input'";
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog_map'] = $catalog_filter;
        $join['catalog']     = $catalog_disable || $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            } else {
                $where_sql .= " `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            }
        }
        if ($join['catalog_map']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_album` ON `catalog_map_album`.`object_id`=`album`.`id` AND `catalog_map_album`.`object_type` = 'album' AND `catalog_map_album`.`catalog_id` = `catalog_se`.`id`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`filter_user` IN (0, $user_id)";
            } else {
                $where_sql .= " `catalog_se`.`filter_user` IN (0, $user_id)";
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
            'having_sql' => ''
        );
    }

    /**
     * user_to_sql
     *
     * Handles the generation of the SQL for user searches.
     * @return array
     */
    private function user_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;

        $where = array();
        $table = array();
        $join  = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            if (!$type) {
                return array();
            }
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $raw_input          = $this->_mangle_data($rule[2], $type, $operator);
            $input              = filter_var($raw_input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'username':
                    $where[] = "`user`.`username` $sql_match_operator '$input'";
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
            'having_sql' => ''
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
            $search['rule_' . $count . '']          = "original_year";
            ++$count;
        }
        if ($toYear) {
            $search['rule_' . $count . '_input']    = $toYear;
            $search['rule_' . $count . '_operator'] = 1;
            $search['rule_' . $count . '']          = "original_year";
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
