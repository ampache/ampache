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

use Ampache\Module\Playlist\SearchType\SearchTypeMapperInterface;
use Ampache\Repository\Model\Metadata\Repository\MetadataField;
use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\PlaylistRepositoryInterface;
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
        if ($user) {
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
        $this->type_text('album_tag', T_('Album Tag'));
        $this->type_text('artist_tag', T_('Artist Tag'));

        $users = array();
        foreach ($this->getUserRepository()->getValid() as $userid) {
            $user           = new User($userid);
            $users[$userid] = $user->username;
        }
        $this->type_select('other_user', T_('Another User'), 'user_numeric', $users);
        $this->type_select('other_user_album', T_('Another User (Album)'), 'user_numeric', $users);
        $this->type_select('other_user_artist', T_('Another User (Artist)'), 'user_numeric', $users);

        $this->type_text('label', T_('Label'));
        if (AmpConfig::get('licensing')) {
            $licenses = array();
            foreach ($this->getLicenseRepository()->getAll() as $license) {
                $licenses[$license->getId()] = $license->getName();
            }
            $this->type_select('license', T_('Music License'), 'boolean_numeric', $licenses);
        }

        $playlistIds = $this->getPlaylistRepository()->getPlaylists(
            (int) Core::get_global('user')->id
        );

        $playlists = array();
        foreach ($playlistIds as $playlistid) {
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
        foreach ($this->getCatalogRepository()->getList() as $catid) {
            $catalog = Catalog::create_from_id($catid);
            $catalog->format();
            $catalogs[$catid] = $catalog->f_name;
        }
        $this->type_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs);

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
        foreach ($this->getUserRepository()->getValid() as $userid) {
            $user           = new User($userid);
            $users[$userid] = $user->username;
        }
        $this->type_select('other_user', T_('Another User'), 'user_numeric', $users);

        $this->type_numeric('recent_played', T_('Recently played'), 'recent_played');

        $catalogs = array();
        foreach ($this->getCatalogRepository()->getList('music') as $catid) {
            $catalog = Catalog::create_from_id($catid);
            $catalog->format();
            $catalogs[$catid] = $catalog->f_name;
        }
        $this->type_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs);

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
        foreach ($this->getUserRepository()->getValid() as $userid) {
            $user           = new User($userid);
            $users[$userid] = $user->username;
        }
        $this->type_select('other_user', T_('Another User'), 'user_numeric', $users);

        $this->type_numeric('recent_played', T_('Recently played'), 'recent_played');

        $catalogs = array();
        foreach ($this->getCatalogRepository()->getList() as $catid) {
            $catalog = Catalog::create_from_id($catid);
            $catalog->format();
            $catalogs[$catid] = $catalog->f_name;
        }
        $this->type_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs);

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
        $sql = "SELECT `id` from `search` WHERE `type`='public' OR `user`='" . Core::get_global('user')->id . "' ORDER BY `name`";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * @param array $data
     * @param User|null $user
     *
     * @return int[]
     */
    public function runSearch(
        array $data,
        ?User $user = null
    ): array {
        return static::run($data, $user);
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

        $search_info = $search->buildSql();
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

        $sqltbl = $this->buildSql();
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

        $sqltbl = $this->buildSql();
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
            $sql .= "`" . $this->searchtype . "`.`id` NOT IN" . " (SELECT `object_id` FROM `rating`" . " WHERE `rating`.`object_type` = '" . $this->searchtype . "'" . " AND `rating`.`rating` <=" . $rating_filter . " AND `rating`.`user` = " . $user_id . ")";
        }
        if (!empty($sqltbl['group_sql'])) {
            $sql .= ' GROUP BY ' . $sqltbl['group_sql'];
        }
        if (!empty($sqltbl['having_sql'])) {
            $sql .= ' HAVING ' . $sqltbl['having_sql'];
        }

        $sql .= ' ORDER BY RAND()';
        $sql .= ($limit) ? ' LIMIT ' . (string) ($limit) : ' ';
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
     * @return string[]
     */
    public function buildSql(): array
    {
        $searchType = $this->getSearchTypeMapper()->map($this->searchtype);
        if ($searchType === null) {
            return [];
        }

        return $searchType->getSql($this);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getSearchTypeMapper(): SearchTypeMapperInterface
    {
        global $dic;

        return $dic->get(SearchTypeMapperInterface::class);
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

    /**
     * @deprecated inject dependency
     */
    private function getCatalogRepository(): CatalogRepositoryInterface
    {
        global $dic;

        return $dic->get(CatalogRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private function getPlaylistRepository(): PlaylistRepositoryInterface
    {
        global $dic;

        return $dic->get(PlaylistRepositoryInterface::class);
    }
}
