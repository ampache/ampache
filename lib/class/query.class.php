<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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
 *
 */

/**
 * Query Class
 *
 * This handles all of the sql/filtering for the ampache database
 * FIXME: flowerysong didn't know about this when he wrote all the fancy stuff
 * for newsearch, and they should be merged if possible.
 *
 */
class Query
{
    /**
     * @var integer|string $id
     */
    public $id;

    /**
     * @var integer $catalog
     */
    public $catalog;

    /**
     * @var array $_state
     */
    protected $_state = array();

    /**
     * @var array $_cache
     */
    protected $_cache;

    /**
     * @var array $allowed_filters
     */
    private static $allowed_filters;

    /**
     * @var array $allowed_sorts
     */
    private static $allowed_sorts;

    /**
     * constructor
     * This should be called
     * @param integer|null $query_id
     * @param boolean $cached
     */
    public function __construct($query_id = null, $cached = true)
    {
        $sid = session_id();

        if (!$cached) {
            $this->id = 'nocache';

            return true;
        }

        if ($query_id === null) {
            $this->reset();
            $data = self::_serialize($this->_state);

            $sql = 'INSERT INTO `tmp_browse` (`sid`, `data`) VALUES(?, ?)';
            Dba::write($sql, array($sid, $data));
            $this->id = Dba::insert_id();

            return true;
        } else {
            $sql = 'SELECT `data` FROM `tmp_browse` WHERE `id` = ? AND `sid` = ?';

            $db_results = Dba::read($sql, array($query_id, $sid));
            if ($results = Dba::fetch_assoc($db_results)) {
                $this->id     = $query_id;
                $this->_state = (array) self::_unserialize($results['data']);

                return true;
            }
        }

        AmpError::add('browse', T_('Browse was not found or expired, try reloading the page'));

        return false;
    }

    /**
     * _auto_init
     * Automatically called when the class is loaded.
     * Populate static arrays if necessary
     * @return boolean
     */
    public static function _auto_init()
    {
        if (!empty(self::$allowed_filters)) {
            return true;
        }

        self::$allowed_filters = array(
            'album' => array(
                'add_lt',
                'add_gt',
                'update_lt',
                'update_gt',
                'show_art',
                'starts_with',
                'exact_match',
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'catalog',
                'catalog_enabled'
            ),
            'artist' => array(
                'add_lt',
                'add_gt',
                'update_lt',
                'update_gt',
                'exact_match',
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with',
                'tag',
                'catalog',
                'catalog_enabled'
            ),
            'song' => array(
                'add_lt',
                'add_gt',
                'update_lt',
                'update_gt',
                'exact_match',
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with',
                'tag',
                'catalog',
                'catalog_enabled',
                'composer',
                'enabled'
            ),
            'live_stream' => array(
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with',
                'catalog_enabled'
            ),
            'playlist' => array(
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with'
            ),
            'smartplaylist' => array(
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with'
            ),
            'tag' => array(
                'tag',
                'object_type',
                'exact_match',
                'alpha_match',
                'regex_match',
                'regex_not_match'
            ),
            'video' => array(
                'starts_with',
                'exact_match',
                'alpha_match',
                'regex_match',
                'regex_not_match'
            ),
            'license' => array(
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with'
            ),
            'tvshow' => array(
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with',
                'year_lt',
                'year_gt',
                'year_eq'
            ),
            'tvshow_season' => array(
                'season_lt',
                'season_lg',
                'season_eq'
            ),
            'user' => array(
                'starts_with'
            ),
            'label' => array(
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with'
            ),
            'pvmsg' => array(
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with',
                'user',
                'to_user'
            ),
            'follower' => array(
                'user',
                'to_user',
            ),
            'podcast' => array(
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with'
            ),
            'podcast_episode' => array(
                'alpha_match',
                'regex_match',
                'regex_not_match',
                'starts_with'
            )
        );

        if (Access::check('interface', 50)) {
            array_push(self::$allowed_filters['playlist'], 'playlist_type');
        }

        self::$allowed_sorts = array(
            'song' => array(
                'title',
                'year',
                'track',
                'time',
                'album',
                'artist'
            ),
            'artist' => array(
                'name',
                'album',
                'placeformed',
                'yearformed'
            ),
            'tag' => array(
                'tag',
                'name'
            ),
            'album' => array(
                'name',
                'year',
                'artist',
                'album_artist',
                'generic_artist'
            ),
            'playlist' => array(
                'name',
                'user',
                'last_update'
            ),
            'smartplaylist' => array(
                'name',
                'user'
            ),
            'shoutbox' => array(
                'date',
                'user',
                'sticky'
            ),
            'live_stream' => array(
                'name',
                'call_sign',
                'frequency'
            ),
            'video' => array(
                'title',
                'resolution',
                'length',
                'codec'
            ),
            'user' => array(
                'fullname',
                'username',
                'last_seen',
                'create_date'
            ),
            'wanted' => array(
                'user',
                'accepted',
                'artist',
                'name',
                'year'
            ),
            'share' => array(
                'object',
                'object_type',
                'user',
                'creation_date',
                'lastvisit_date',
                'counter',
                'max_counter',
                'allow_stream',
                'allow_download',
                'expire'
            ),
            'channel' => array(
                'id',
                'name',
                'interface',
                'port',
                'max_listeners',
                'listeners'
            ),
            'broadcast' => array(
                'name',
                'user',
                'started',
                'listeners'
            ),
            'license' => array(
                'name'
            ),
            'tvshow' => array(
                'name',
                'year'
            ),
            'tvshow_season' => array(
                'season',
                'tvshow'
            ),
            'tvshow_episode' => array(
                'title',
                'resolution',
                'length',
                'codec',
                'episode',
                'season',
                'tvshow'
            ),
            'movie' => array(
                'title',
                'resolution',
                'length',
                'codec',
                'release_date'
            ),
            'clip' => array(
                'title',
                'artist',
                'resolution',
                'length',
                'codec',
                'release_date'
            ),
            'personal_video' => array(
                'title',
                'location',
                'resolution',
                'length',
                'codec',
                'release_date'
            ),
            'label' => array(
                'name',
                'category',
                'user'
            ),
            'pvmsg' => array(
                'subject',
                'to_user',
                'creation_date',
                'is_read'
            ),
            'follower' => array(
                'user',
                'follow_user',
                'follow_date'
            ),
            'podcast' => array(
                'title'
            ),
            'podcast_episode' => array(
                'title',
                'category',
                'author',
                'time',
                'pubDate'
            )
        );

        return true;
    }

    /**
     * garbage_collection
     * This cleans old data out of the table
     */
    public static function garbage_collection()
    {
        $sql = 'DELETE FROM `tmp_browse` USING `tmp_browse` LEFT JOIN ' .
            '`session` ON `session`.`id` = `tmp_browse`.`sid` ' .
            'WHERE `session`.`id` IS NULL';
        Dba::write($sql);
    }

    /**
     * _serialize
     *
     * Attempts to produce a more compact representation for large result
     * sets by collapsing ranges.
     * @param array $data
     * @return string
     */
    private static function _serialize($data)
    {
        return json_encode($data);
    }

    /**
     * _unserialize
     *
     * Reverses serialization.
     * @param string $data
     * @return mixed
     */
    private static function _unserialize($data)
    {
        return json_decode((string) $data, true);
    }

    /**
     * set_filter
     * This saves the filter data we pass it.
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function set_filter($key, $value)
    {
        switch ($key) {
            case 'tag':
                if (is_array($value)) {
                    $this->_state['filter'][$key] = $value;
                } elseif (is_numeric($value)) {
                    $this->_state['filter'][$key] = array($value);
                } else {
                    $this->_state['filter'][$key] = array();
                }
                break;
            case 'artist':
            case 'album_artist':
            case 'catalog':
            case 'album':
                $this->_state['filter'][$key] = $value;
                break;
            case 'min_count':
            case 'unplayed':
            case 'rated':
                break;
            case 'add_lt':
            case 'add_gt':
            case 'update_lt':
            case 'update_gt':
            case 'catalog_enabled':
            case 'year_lt':
            case 'year_lg':
            case 'year_eq':
            case 'season_lt':
            case 'season_lg':
            case 'season_eq':
            case 'user':
            case 'to_user':
            case 'enabled':
                $this->_state['filter'][$key] = (int) ($value);
                break;
            case 'exact_match':
            case 'alpha_match':
            case 'regex_match':
            case 'regex_not_match':
            case 'starts_with':
                if ($this->is_static_content()) {
                    return false;
                }
                $this->_state['filter'][$key] = $value;
                if ($key == 'regex_match') {
                    unset($this->_state['filter']['regex_not_match']);
                }
                if ($key == 'regex_not_match') {
                    unset($this->_state['filter']['regex_match']);
                }
                break;
            case 'playlist_type':
                // Must be a content manager to turn this off
                if (Access::check('interface', 100)) {
                    unset($this->_state['filter'][$key]);
                } else {
                    $this->_state['filter'][$key] = '1';
                }
                break;
            default:
                return false;
        } // end switch

        // If we've set a filter we need to reset the totals
        $this->reset_total();
        $this->set_start(0);

        return true;
    } // set_filter

    /**
     * reset
     * Reset everything, this should only be called when we are starting
     * fresh
     */
    public function reset()
    {
        $this->reset_base();
        $this->reset_filters();
        $this->reset_total();
        $this->reset_join();
        $this->reset_select();
        $this->reset_having();
        $this->set_static_content(false);
        $this->set_is_simple(false);
        $this->set_start(0);
        $this->set_offset(AmpConfig::get('offset_limit') ? AmpConfig::get('offset_limit') : '25');
    } // reset

    /**
     * reset_base
     * this resets the base string
     */
    public function reset_base()
    {
        $this->_state['base'] = null;
    } // reset_base

    /**
     * reset_select
     * This resets the select fields that we've added so far
     */
    public function reset_select()
    {
        $this->_state['select'] = array();
    } // reset_select

    /**
     * reset_having
     * Null out the having clause
     */
    public function reset_having()
    {
        unset($this->_state['having']);
    } // reset_having

    /**
     * reset_join
     * clears the joins if there are any
     */
    public function reset_join()
    {
        unset($this->_state['join']);
    } // reset_join

    /**
     * reset_filter
     * This is a wrapper function that resets the filters
     */
    public function reset_filters()
    {
        $this->_state['filter'] = array();
    } // reset_filters

    /**
     * reset_total
     * This resets the total for the browse type
     */
    public function reset_total()
    {
        unset($this->_state['total']);
    } // reset_total

    /**
     * get_filter
     * returns the specified filter value
     * @param string $key
     * @return string|boolean
     */
    public function get_filter($key)
    {
        // Simple enough, but if we ever move this crap
        // If we ever move this crap what?
        return (isset($this->_state['filter'][$key])) ? $this->_state['filter'][$key] : false;
    } // get_filter

    /**
     * get_start
     * This returns the current value of the start
     * @return integer
     */
    public function get_start()
    {
        return $this->_state['start'];
    } // get_start

    /**
     * get_offset
     * This returns the current offset
     * @return integer
     */
    public function get_offset()
    {
        return $this->_state['offset'];
    } // get_offset

    /**
     * set_total
     * This sets the total number of objects
     * @param integer $total
     */
    public function set_total($total)
    {
        $this->_state['total'] = $total;
    }

    /**
     * get_total
     * This returns the total number of objects for this current sort type.
     * If it's already cached used it. if they pass us an array then use
     * that.
     * @param array $objects
     * @return integer
     */
    public function get_total($objects = null)
    {
        // If they pass something then just return that
        if (is_array($objects) && !$this->is_simple()) {
            return count($objects);
        }

        // See if we can find it in the cache
        if (isset($this->_state['total'])) {
            return $this->_state['total'];
        }

        $db_results = Dba::read($this->get_sql(false));
        $num_rows   = Dba::num_rows($db_results);

        $this->_state['total'] = $num_rows;

        return $num_rows;
    } // get_total

    /**
     * get_allowed_filters
     * This returns an array of the allowed filters based on the type of
     * object we are working with, this is used to display the 'filter'
     * sidebar stuff.
     * @param string $type
     * @return array
     */
    public static function get_allowed_filters($type)
    {
        return (isset(self::$allowed_filters[$type])) ? self::$allowed_filters[$type] : array();
    } // get_allowed_filters

    /**
      * set_type
     * This sets the type of object that we want to browse by
     * we do this here so we only have to maintain a single whitelist
     * and if I want to change the location I only have to do it here
     * @param string $type
     * @param string $custom_base
     */
    public function set_type($type, $custom_base = '')
    {
        switch ($type) {
            case 'user':
            case 'video':
            case 'playlist':
            case 'playlist_media':
            case 'smartplaylist':
            case 'song':
            case 'catalog':
            case 'album':
            case 'artist':
            case 'tag':
            case 'playlist_localplay':
            case 'shoutbox':
            case 'live_stream':
            case 'democratic':
            case 'wanted':
            case 'share':
            case 'song_preview':
            case 'channel':
            case 'broadcast':
            case 'license':
            case 'tvshow':
            case 'tvshow_season':
            case 'tvshow_episode':
            case 'movie':
            case 'personal_video':
            case 'clip':
            case 'label':
            case 'pvmsg':
            case 'follower':
            case 'podcast':
            case 'podcast_episode':
                // Set it
                $this->_state['type'] = $type;
                $this->set_base_sql(true, $custom_base);
            break;
            default:
                break;
        } // end type whitelist
    } // set_type

    /**
     * get_type
     * This returns the type of the browse we currently are using
     * @return string
     */
    public function get_type()
    {
        return (string) $this->_state['type'];
    } // get_type

    /**
     * set_sort
     * This sets the current sort(s)
     * @param string $sort
     * @param string $order
     * @return boolean
     */
    public function set_sort($sort, $order = '')
    {
        // If it's not in our list, smeg off!
        if (!in_array($sort, self::$allowed_sorts[$this->get_type()])) {
            return false;
        }

        $this->reset_join();

        if ($order) {
            $order                       = ($order == 'DESC') ? 'DESC' : 'ASC';
            $this->_state['sort']        = array();
            $this->_state['sort'][$sort] = $order;
        } elseif ($this->_state['sort'][$sort] == 'DESC') {
            // Reset it till I can figure out how to interface the hotness
            $this->_state['sort']        = array();
            $this->_state['sort'][$sort] = 'ASC';
        } else {
            // Reset it till I can figure out how to interface the hotness
            $this->_state['sort']        = array();
            $this->_state['sort'][$sort] = 'DESC';
        }

        $this->resort_objects();

        return true;
    } // set_sort

    /**
     * set_offset
     * This sets the current offset of this query
     * @param integer $offset
     */
    public function set_offset($offset)
    {
        $this->_state['offset'] = abs($offset);
    } // set_offset

    /**
     * set_catalog
     * @param integer $catalog_number
     */
    public function set_catalog($catalog_number)
    {
        $this->catalog = $catalog_number;
    }

    /**
     * set_select
     * This appends more information to the select part of the SQL
     * statement, we're going to move to the %%SELECT%% style queries, as I
     * think it's the only way to do this...
     * @param string $field
     */
    public function set_select($field)
    {
        $this->_state['select'][] = $field;
    } // set_select

    /**
     * set_join
     * This sets the joins for the current browse object
     * @param string $type
     * @param string $table
     * @param string $source
     * @param string $dest
     * @param integer $priority
     */
    public function set_join($type, $table, $source, $dest, $priority)
    {
        $this->_state['join'][$priority][$table] = strtoupper((string) $type) . ' JOIN ' . $table . ' ON ' . $source . '=' . $dest;
    } // set_join

    /**
     * set_having
     * This sets the "HAVING" part of the query, we can only have one..
     * god this is ugly
     * @param string $condition
     */
    public function set_having($condition)
    {
        $this->_state['having'] = $condition;
    } // set_having

    /**
     * set_start
     * This sets the start point for our show functions
     * We need to store this in the session so that it can be pulled
     * back, if they hit the back button
     * @param integer $start
     */
    public function set_start($start)
    {
        $start                 = (int) ($start);
        $this->_state['start'] = $start;
    } // set_start

    /**
     * set_is_simple
     * This sets the current browse object to a 'simple' browse method
     * which means use the base query provided and expand from there
     * @param boolean $value
     */
    public function set_is_simple($value)
    {
        $value                  = make_bool($value);
        $this->_state['simple'] = $value;
    } // set_is_simple

    /**
     * set_static_content
     * This sets true/false if the content of this browse
     * should be static, if they are then content filtering/altering
     * methods will be skipped
     * @param boolean $value
     */
    public function set_static_content($value)
    {
        $value = make_bool($value);

        $this->_state['static'] = $value;
    } // set_static_content

    /**
     *
     * @return boolean
     */
    public function is_static_content()
    {
        return make_bool($this->_state['static']);
    }

    /**
     * is_simple
     * This returns whether or not the current browse type is set to static.
     * @return boolean
     */
    public function is_simple()
    {
        return $this->_state['simple'];
    } // is_simple

    /**
     * get_saved
     * This looks in the session for the saved stuff and returns what it
     * finds.
     * @return array
     */
    public function get_saved()
    {
        // See if we have it in the local cache first
        if (!empty($this->_cache)) {
            return $this->_cache;
        }

        if (!$this->is_simple()) {
            $sql = 'SELECT `object_data` FROM `tmp_browse` ' .
                'WHERE `sid` = ? AND `id` = ?';
            $db_results = Dba::read($sql, array(session_id(), $this->id));

            $row = Dba::fetch_assoc($db_results);

            $this->_cache = (array) self::_unserialize($row['object_data']);

            return $this->_cache;
        } else {
            $objects = $this->get_objects();
        }

        return $objects;
    } // get_saved

    /**
     * get_objects
     * This gets an array of the ids of the objects that we are
     * currently browsing by it applies the sql and logic based
     * filters
     * @return array
     */
    public function get_objects()
    {
        // First we need to get the SQL statement we are going to run
        // This has to run against any possible filters (dependent on type)
        $sql        = $this->get_sql();
        $db_results = Dba::read($sql);

        $results = array();
        while ($data = Dba::fetch_assoc($db_results)) {
            $results[] = $data;
        }

        $results  = $this->post_process($results);
        $filtered = array();
        foreach ($results as $data) {
            // Make sure that this object passes the logic filter
            if ($this->logic_filter($data['id'])) {
                $filtered[] = $data['id'];
            }
        } // end while

        // Save what we've found and then return it
        $this->save_objects($filtered);

        return $filtered;
    } // get_objects

    /**
     * set_base_sql
     * This saves the base sql statement we are going to use.
     * @param boolean $force
     * @param string $custom_base
     * @return boolean
     */
    private function set_base_sql($force = false, $custom_base = '')
    {
        // Only allow it to be set once
        if (strlen((string) $this->_state['base']) && !$force) {
            return true;
        }

        // Custom sql base
        if ($force && !empty($custom_base)) {
            $this->_state['custom'] = true;
            $sql                    = $custom_base;
        } else {
            switch ($this->get_type()) {
                case 'album':
                    $this->set_select("`album`.`id`");
                    $sql = "SELECT MIN(%%SELECT%%) AS `id` FROM `album` ";
                    break;
                case 'artist':
                    $this->set_select("`artist`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `artist` ";
                    break;
                case 'catalog':
                    $this->set_select("`artist`.`name`");
                    $sql = "SELECT %%SELECT%% FROM `artist` ";
                    break;
                case 'user':
                    $this->set_select("`user`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `user` ";
                    break;
                case 'live_stream':
                    $this->set_select("`live_stream`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `live_stream` ";
                    break;
                case 'playlist':
                    $this->set_select("`playlist`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `playlist` ";
                    break;
                case 'smartplaylist':
                    $this->set_select('`search`.`id`');
                    $sql = "SELECT %%SELECT%% FROM `search` ";
                    break;
                case 'shoutbox':
                    $this->set_select("`user_shout`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `user_shout` ";
                    break;
                case 'video':
                    $this->set_select("`video`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `video` ";
                    break;
                case 'tag':
                    $this->set_select("`tag`.`id`");
                    $this->set_join('left', 'tag_map', '`tag_map`.`tag_id`', '`tag`.`id`', 1);
                    $sql = "SELECT %%SELECT%% FROM `tag` ";
                    break;
                case 'wanted':
                    $this->set_select("`wanted`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `wanted` ";
                    break;
                case 'share':
                    $this->set_select("`share`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `share` ";
                    break;
                case 'channel':
                    $this->set_select("`channel`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `channel` ";
                    break;
                case 'broadcast':
                    $this->set_select("`broadcast`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `broadcast` ";
                    break;
                case 'license':
                    $this->set_select("`license`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `license` ";
                    break;
                case 'tvshow':
                    $this->set_select("`tvshow`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `tvshow` ";
                    break;
                case 'tvshow_season':
                    $this->set_select("`tvshow_season`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `tvshow_season` ";
                    break;
                case 'tvshow_episode':
                    $this->set_select("`tvshow_episode`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `tvshow_episode` ";
                    break;
                case 'movie':
                    $this->set_select("`movie`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `movie` ";
                    break;
                case 'clip':
                    $this->set_select("`clip`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `clip` ";
                    break;
                case 'personal_video':
                    $this->set_select("`personal_video`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `personal_video` ";
                    break;
                case 'label':
                    $this->set_select("`label`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `label` ";
                    break;
                case 'pvmsg':
                    $this->set_select("`user_pvmsg`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `user_pvmsg` ";
                    break;
                case 'follower':
                    $this->set_select("`user_follower`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `user_follower` ";
                    break;
                case 'podcast':
                    $this->set_select("`podcast`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `podcast` ";
                    break;
                case 'podcast_episode':
                    $this->set_select("`podcast_episode`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `podcast_episode` ";
                    break;
                case 'playlist_media':
                    $sql = '';
                    break;
                case 'song':
                default:
                    $this->set_select("`song`.`id`");
                    $sql = "SELECT %%SELECT%% FROM `song` ";
                    break;
            } // end base sql
        }

        $this->_state['base'] = $sql;

        return true;
    } // set_base_sql

    /**
     * get_select
     * This returns the selects in a format that is friendly for a sql
     * statement.
     * @return string
     */
    private function get_select()
    {
        return implode(", ", $this->_state['select']);
    } // get_select

    /**
     * get_base_sql
     * This returns the base sql statement all parsed up, this should be
     * called after all set operations.
     * @return string
     */
    private function get_base_sql()
    {
        return str_replace("%%SELECT%%", $this->get_select(), $this->_state['base']);
    } // get_base_sql

    /**
     * get_filter_sql
     * This returns the filter part of the sql statement
     * @return string
     */
    private function get_filter_sql()
    {
        if (!is_array($this->_state['filter'])) {
            return '';
        }

        $sql = "WHERE 1=1 AND ";

        foreach ($this->_state['filter'] as $key => $value) {
            $sql .= $this->sql_filter($key, $value);
        }

        if (AmpConfig::get('catalog_disable')) {
            // Add catalog enabled filter
            switch ($this->get_type()) {
                case "video":
                case "song":
                    $dis = Catalog::get_enable_filter('song', '`' . $this->get_type() . '`.`id`');
                    break;

                case "tag":
                    $dis = Catalog::get_enable_filter('tag', '`' . $this->get_type() . '`.`object_id`');
                    break;
            }
        }
        if (!empty($dis)) {
            $sql .= $dis . " AND ";
        }

        $sql = rtrim((string) $sql, 'AND ') . " ";

        return $sql;
    } // get_filter_sql

    /**
     * get_sort_sql
     * Returns the sort sql part
     * @return string
     */
    private function get_sort_sql()
    {
        if (!is_array($this->_state['sort'])) {
            return '';
        }

        $sql = 'ORDER BY ';

        foreach ($this->_state['sort'] as $key => $value) {
            $sql .= $this->sql_sort($key, $value);
        }

        $sql = rtrim((string) $sql, 'ORDER BY ');
        $sql = rtrim((string) $sql, ', ');

        return $sql;
    } // get_sort_sql

    /**
     * get_limit_sql
     * This returns the limit part of the sql statement
     * @return string
     */
    private function get_limit_sql()
    {
        if (!$this->is_simple() || $this->get_start() < 0) {
            return '';
        }

        return ' LIMIT ' . (string) ($this->get_start()) . ', ' . (string) ($this->get_offset());
    } // get_limit_sql

    /**
     * get_join_sql
     * This returns the joins that this browse may need to work correctly
     * @return string
     */
    private function get_join_sql()
    {
        if (!isset($this->_state['join']) || !is_array($this->_state['join'])) {
            return '';
        }

        $sql = '';

        foreach ($this->_state['join'] as $joins) {
            foreach ($joins as $join) {
                $sql .= $join . ' ';
            } // end foreach joins at this level
        } // end foreach of this level of joins

        return $sql;
    } // get_join_sql

    /**
     * get_having_sql
     * this returns the having sql stuff, if we've got anything
     * @return string
     */
    public function get_having_sql()
    {
        return isset($this->_state['having']) ? $this->_state['having'] : '';
    } // get_having_sql

    /**
     * get_sql
     * This returns the sql statement we are going to use this has to be run
     * every time we get the objects because it depends on the filters and
     * the type of object we are currently browsing.
     * @param boolean $limit
     * @return string
     */
    public function get_sql($limit = true)
    {
        $sql = $this->get_base_sql();

        $filter_sql = "";
        $join_sql   = "";
        $having_sql = "";
        $order_sql  = "";
        if (!isset($this->_state['custom']) || !$this->_state['custom']) {
            $filter_sql = $this->get_filter_sql();
            $order_sql  = $this->get_sort_sql();
            $join_sql   = $this->get_join_sql();
            $having_sql = $this->get_having_sql();
        }
        $limit_sql = $limit ? $this->get_limit_sql() : '';
        $final_sql = $sql . $join_sql . $filter_sql . $having_sql;

        // filter albums when you have grouped disks!
        if ($this->get_type() == 'album' && !$this->_state['custom'] && AmpConfig::get('album_group')) {
            $album_artist = (array_key_exists('album_artist', $this->_state['sort'])) ? " `artist`.`name`," : '';
            $final_sql .= " GROUP BY" . $album_artist . " `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`mbid`, `album`.`year` ";
        } elseif (($this->get_type() == 'artist' || $this->get_type() == 'album') && !$this->_state['custom']) {
            $final_sql .= " GROUP BY `" . $this->get_type() . "`.`name`, `" . $this->get_type() . "`.`id` ";
        }
        $final_sql .= $order_sql . $limit_sql;
        //debug_event(self::class, "get_sql: " . $final_sql, 5);

        return $final_sql;
    } // get_sql

    /**
       * post_process
     * This does some additional work on the results that we've received
     * before returning them.
     * @param array $data
     * @return array
     */
    private function post_process($data)
    {
        $tags = isset($this->_state['filter']['tag']) ? $this->_state['filter']['tag'] : '';

        if (!is_array($tags) || sizeof($tags) < 2) {
            return $data;
        }

        $tag_count = sizeof($tags);
        $count     = array();

        foreach ($data as $row) {
            $count[$row['id']]++;
        }

        $results = array();

        foreach ($count as $key => $value) {
            if ($value >= $tag_count) {
                $results[] = array('id' => $key);
            }
        } // end foreach

        return $results;
    } // post_process

    /**
     * sql_filter
     * This takes a filter name and value and if it is possible
     * to filter by this name on this type returns the appropriate sql
     * if not returns nothing
     * @param string $filter
     * @param mixed $value
     * @return string
     */
    private function sql_filter($filter, $value)
    {
        $filter_sql = '';
        switch ($this->get_type()) {
        case 'song':
            switch ($filter) {
                case 'tag':
                    $this->set_join('left', '`tag_map`', '`tag_map`.`object_id`', '`song`.`id`', 100);
                    $filter_sql = " `tag_map`.`object_type`='" . $this->get_type() . "' AND (";

                    foreach ($value as $tag_id) {
                        $filter_sql .= "  `tag_map`.`tag_id`='" . Dba::escape($tag_id) . "' AND";
                    }
                    $filter_sql = rtrim((string) $filter_sql, 'AND') . ") AND ";
                    break;
                case 'exact_match':
                    $filter_sql = " `song`.`title` = '" . Dba::escape($value) . "' AND ";
                    break;
                case 'alpha_match':
                    $filter_sql = " `song`.`title` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `song`.`title` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `song`.`title` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $filter_sql = " `song`.`title` LIKE '" . Dba::escape($value) . "%' AND ";
                    if ($this->catalog != 0) {
                        $filter_sql .= " `song`.`catalog` = '" . $this->catalog . "' AND ";
                    }
                    break;
                case 'unplayed':
                    $filter_sql = " `song`.`played`='0' AND ";
                    break;
                case 'album':
                    $filter_sql = " `song`.`album` = '" . Dba::escape($value) . "' AND ";
                    break;
                case 'artist':
                    $filter_sql = " `song`.`artist` = '" . Dba::escape($value) . "' AND ";
                    break;
                case 'add_gt':
                    $filter_sql = " `song`.`addition_time` >= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'add_lt':
                    $filter_sql = " `song`.`addition_time` <= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'update_gt':
                    $filter_sql = " `song`.`update_time` >= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'update_lt':
                    $filter_sql = " `song`.`update_time` <= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'catalog':
                    if ($value != 0) {
                        $filter_sql = " `song`.`catalog` = '$value' AND ";
                    }
                    break;
                case 'catalog_enabled':
                    $this->set_join('left', '`catalog`', '`catalog`.`id`', '`song`.`catalog`', 100);
                    $filter_sql = " `catalog`.`enabled` = '1' AND ";
                    break;
                case 'enabled':
                    $filter_sql = " `song`.`enabled`= '$value' AND ";
                    break;
                default:
                    break;
            } // end list of sqlable filters
            break;
        case 'album':
            switch ($filter) {
                case 'tag':
                    $this->set_join('left', '`tag_map`', '`tag_map`.`object_id`', '`album`.`id`', 100);
                    $filter_sql = " `tag_map`.`object_type`='" . $this->get_type() . "' AND (";

                    foreach ($value as $tag_id) {
                        $filter_sql .= "  `tag_map`.`tag_id`='" . Dba::escape($tag_id) . "' AND";
                    }
                    $filter_sql = rtrim((string) $filter_sql, 'AND') . ") AND ";
                    break;
                case 'exact_match':
                    $filter_sql = " `album`.`name` = '" . Dba::escape($value) . "' AND ";
                    break;
                case 'alpha_match':
                    $filter_sql = " `album`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `album`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `album`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $this->set_join('left', '`song`', '`album`.`id`', '`song`.`album`', 100);
                    $filter_sql = " `album`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                    if ($this->catalog != 0) {
                        $filter_sql .= "`song`.`catalog` = '" . $this->catalog . "' AND ";
                    }
                    break;
                case 'artist':
                    $filter_sql = " `artist`.`id` = '" . Dba::escape($value) . "' AND ";
                    break;
                case 'add_lt':
                    $this->set_join('left', '`song`', '`song`.`album`', '`album`.`id`', 100);
                    $filter_sql = " `song`.`addition_time` <= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'add_gt':
                    $this->set_join('left', '`song`', '`song`.`album`', '`album`.`id`', 100);
                    $filter_sql = " `song`.`addition_time` >= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'catalog':
                    if ($value != 0) {
                        $this->set_join('left', '`song`', '`album`.`id`', '`song`.`album`', 100);
                        $this->set_join('left', '`catalog`', '`song`.`catalog`', '`catalog`.`id`', 100);
                        $filter_sql = " (`song`.`catalog` = '$value') AND ";
                    }
                    break;
                case 'update_lt':
                    $this->set_join('left', '`song`', '`song`.`album`', '`album`.`id`', 100);
                    $filter_sql = " `song`.`update_time` <= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'update_gt':
                    $this->set_join('left', '`song`', '`song`.`album`', '`album`.`id`', 100);
                    $filter_sql = " `song`.`update_time` >= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'catalog_enabled':
                    $this->set_join('left', '`song`', '`song`.`album`', '`album`.`id`', 100);
                    $this->set_join('left', '`catalog`', '`catalog`.`id`', '`song`.`catalog`', 100);
                    $filter_sql = " `catalog`.`enabled` = '1' AND ";
                    break;
                default:
                    break;
            }
            break;
        case 'artist':
            switch ($filter) {
                case 'tag':
                    $this->set_join('left', '`tag_map`', '`tag_map`.`object_id`', '`artist`.`id`', 100);
                    $filter_sql = " `tag_map`.`object_type`='" . $this->get_type() . "' AND (";

                    foreach ($value as $tag_id) {
                        $filter_sql .= "  `tag_map`.`tag_id`='" . Dba::escape($tag_id) . "' AND";
                    }
                    $filter_sql = rtrim((string) $filter_sql, 'AND') . ') AND ';
                    break;
                case 'catalog':
                if ($value != 0) {
                    $this->set_join('left', '`song`', '`artist`.`id`', '`song`.`artist`', 100);
                    $this->set_join('left', '`catalog`', '`song`.`catalog`', '`catalog`.`id`', 100);
                    $filter_sql = "  (`catalog`.`id` = '$value') AND ";
                }
                    break;
                case 'exact_match':
                    $filter_sql = " `artist`.`name` = '" . Dba::escape($value) . "' AND ";
                    break;
                case 'alpha_match':
                    $filter_sql = " `artist`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `artist`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `artist`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $this->set_join('left', '`song`', '`artist`.`id`', '`song`.`artist`', 100);
                    $filter_sql = " `artist`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                    if ($this->catalog != 0) {
                        $filter_sql .= "`song`.`catalog` = '" . $this->catalog . "' AND ";
                    }
                    break;
                case 'add_lt':
                    $this->set_join('left', '`song`', '`song`.`artist`', '`artist`.`id`', 100);
                    $filter_sql = " `song`.`addition_time` <= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'add_gt':
                    $this->set_join('left', '`song`', '`song`.`artist`', '`artist`.`id`', 100);
                    $filter_sql = " `song`.`addition_time` >= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'update_lt':
                    $this->set_join('left', '`song`', '`song`.`artist`', '`artist`.`id`', 100);
                    $filter_sql = " `song`.`update_time` <= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'update_gt':
                    $this->set_join('left', '`song`', '`song`.`artist`', '`artist`.`id`', 100);
                    $filter_sql = " `song`.`update_time` >= '" . Dba::escape($value) . "' AND ";
                    break;
                case 'catalog_enabled':
                    $this->set_join('left', '`song`', '`song`.`artist`', '`artist`.`id`', 100);
                    $this->set_join('left', '`catalog`', '`catalog`.`id`', '`song`.`catalog`', 100);
                    $filter_sql = " `catalog`.`enabled` = '1' AND ";
                    break;
                case 'album_artist':
                    $this->set_join('left', '`album`', '`album`.`album_artist`', '`artist`.`id`', 100);
                    $filter_sql = " `album`.`album_artist` IS NOT NULL AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'live_stream':
            switch ($filter) {
                case 'alpha_match':
                    $filter_sql = " `live_stream`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `live_stream`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `live_stream`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $filter_sql = " `live_stream`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                    break;
                case 'catalog_enabled':
                    $this->set_join('left', '`catalog`', '`catalog`.`id`', '`live_stream`.`catalog`', 100);
                    $filter_sql = " `catalog`.`enabled` = '1' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'playlist':
            switch ($filter) {
                case 'alpha_match':
                    $filter_sql = " `playlist`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `playlist`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `playlist`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $filter_sql = " `playlist`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                    break;
                case 'playlist_type':
                    $user_id    = ((int) Core::get_global('user')->id > 0) ? Core::get_global('user')->id : $value;
                    $filter_sql = " (`playlist`.`type` = 'public' OR `playlist`.`user`='$user_id') AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'smartplaylist':
            switch ($filter) {
                case 'alpha_match':
                    $filter_sql = " `search`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `search`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `search`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $filter_sql = " `search`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                    break;
                case 'playlist_type':
                    $user_id    = ((int) Core::get_global('user')->id > 0) ? Core::get_global('user')->id : $value;
                    $filter_sql = " (`search`.`type` = 'public' OR `search`.`user`='$user_id') AND ";
                    break;
            } // end switch on $filter
            break;
        case 'tag':
            switch ($filter) {
                case 'alpha_match':
                    $filter_sql = " `tag`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `tag`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `tag`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'exact_match':
                    $filter_sql = " `tag`.`name` = '" . Dba::escape($value) . "' AND ";
                    break;
                case 'tag':
                    $filter_sql = " `tag`.`id` = '" . Dba::escape($value) . "' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'video':
            switch ($filter) {
                case 'tag':
                    $this->set_join('left', '`tag_map`', '`tag_map`.`object_id`', '`video`.`id`', 100);
                    $filter_sql = " `tag_map`.`object_type`='" . $this->get_type() . "' AND (";

                    foreach ($value as $tag_id) {
                        $filter_sql .= "  `tag_map`.`tag_id`='" . Dba::escape($tag_id) . "' AND";
                    }
                    $filter_sql = rtrim((string) $filter_sql, 'AND') . ') AND ';
                    break;
                case 'alpha_match':
                    $filter_sql = " `video`.`title` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `video`.`title` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `video`.`title` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $filter_sql = " `video`.`title` LIKE '" . Dba::escape($value) . "%' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'license':
            switch ($filter) {
                case 'alpha_match':
                    $filter_sql = " `license`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `license`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `license`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'exact_match':
                    $filter_sql = " `license`.`name` = '" . Dba::escape($value) . "' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'tvshow':
            switch ($filter) {
                case 'alpha_match':
                    $filter_sql = " `tvshow`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `tvshow`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `tvshow`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'exact_match':
                    $filter_sql = " `tvshow`.`name` = '" . Dba::escape($value) . "' AND ";
                    break;
                case 'year_lt':
                    $filter_sql = " `tvshow`.`year` < '" . Dba::escape($value) . "' AND ";
                    break;
                case 'year_gt':
                    $filter_sql = " `tvshow`.`year` > '" . Dba::escape($value) . "' AND ";
                    break;
                case 'year_eq':
                    $filter_sql = " `tvshow`.`year` = '" . Dba::escape($value) . "' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'tvshow_season':
            switch ($filter) {
                case 'season_lt':
                    $filter_sql = " `tvshow_season`.`season_number` < '" . Dba::escape($value) . "' AND ";
                    break;
                case 'season_gt':
                    $filter_sql = " `tvshow_season`.`season_number` > '" . Dba::escape($value) . "' AND ";
                    break;
                case 'season_eq':
                    $filter_sql = " `tvshow_season`.`season_number` = '" . Dba::escape($value) . "' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'user':
            switch ($filter) {
                case 'starts_with':
                    $filter_sql = " (`user`.`fullname` LIKE '" . Dba::escape($value) . "%' OR `user`.`username` LIKE '" . Dba::escape($value) . "%' OR `user`.`email` LIKE '" . Dba::escape($value) . "%' ) AND ";
                    break;
            } // end filter
            break;
        case 'label':
            switch ($filter) {
                case 'alpha_match':
                    $filter_sql = " `label`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `label`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `label`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $filter_sql = " `label`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'pvmsg':
            switch ($filter) {
                case 'alpha_match':
                    $filter_sql = " `user_pvmsg`.`subject` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `user_pvmsg`.`subject` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `user_pvmsg`.`subject` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $filter_sql = " `user_pvmsg`.`subject` LIKE '" . Dba::escape($value) . "%' AND ";
                    break;
                case 'user':
                    $filter_sql = " `user_pvmsg`.`from_user` = '" . Dba::escape($value) . "' AND ";
                    break;
                case 'to_user':
                    $filter_sql = " `user_pvmsg`.`to_user` = '" . Dba::escape($value) . "' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'follower':
            switch ($filter) {
                case 'user':
                    $filter_sql = " `user_follower`.`user` = '" . Dba::escape($value) . "' AND ";
                    break;
                case 'to_user':
                    $filter_sql = " `user_follower`.`follow_user` = '" . Dba::escape($value) . "' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'podcast':
            switch ($filter) {
                case 'alpha_match':
                    $filter_sql = " `podcast`.`title` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `podcast`.`title` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `podcast`.`title` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $filter_sql = " `podcast`.`title` LIKE '" . Dba::escape($value) . "%' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        case 'podcast_episode':
            switch ($filter) {
                case 'alpha_match':
                    $filter_sql = " `podcast_episode`.`title` LIKE '%" . Dba::escape($value) . "%' AND ";
                    break;
                case 'regex_match':
                    if (!empty($value)) {
                        $filter_sql = " `podcast_episode`.`title` REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'regex_not_match':
                    if (!empty($value)) {
                        $filter_sql = " `podcast_episode`.`title` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                    }
                    break;
                case 'starts_with':
                    $filter_sql = " `podcast_episode`.`title` LIKE '" . Dba::escape($value) . "%' AND ";
                    break;
                default:
                    break;
            } // end filter
            break;
        } // end switch on type

        return $filter_sql;
    } // sql_filter

    /**
     * logic_filter
     * This runs the filters that we can't easily apply
     * to the sql so they have to be done after the fact
     * these should be limited as they are often intensive and
     * require additional queries per object... :(
     *
     * @param integer $object_id
     * @return boolean
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function logic_filter($object_id)
    {
        return true;
    } // logic_filter

    /**
     * sql_sort
     * This builds any order bys we need to do
     * to sort the results as best we can, there is also
     * a logic based sort that will come later as that's
     * a lot more complicated
     * @param string $field
     * @param string $order
     * @return string
     */
    private function sql_sort($field, $order)
    {
        if ($order != 'DESC') {
            $order = 'ASC';
        }

        // Depending on the type of browsing we are doing we can apply
        // different filters that apply to different fields
        switch ($this->get_type()) {
            case 'song':
                switch ($field) {
                    case 'title':
                    case 'year':
                    case 'time':
                    case 'track':
                    case 'composer':
                        $sql = "`song`.`$field`";
                        break;
                    case 'album':
                    case 'artist':
                        $sql = "`$field`.`name`";
                        $this->set_join('left', "`$field`", "`$field`.`id`", "`song`.`$field`", 100);
                        break;
                    default:
                        break;
                } // end switch
                break;
            case 'album':
                switch ($field) {
                    case 'name':
                        $sql = "`album`.`name` $order, `album`.`disk`";
                        if (AmpConfig::get('album_group')) {
                            $sql = "`album`.`name`";
                        }
                        break;
                    case 'generic_artist':
                        $sql = "`artist`.`name`";
                        $this->set_join('left', '`song`', '`song`.`album`', '`album`.`id`', 100);
                        $this->set_join('left', '`artist`', 'COALESCE(`album`.`album_artist`, `song`.`artist`)', '`artist`.`id`', 100);
                        break;
                    case 'album_artist':
                        $sql = "`artist`.`name`";
                        $this->set_join('left', '`artist`', '`album`.`album_artist`', '`artist`.`id`', 100);
                        break;
                    case 'artist':
                        $sql = "`artist`.`name`";
                        $this->set_join('left', '`song`', '`song`.`album`', '`album`.`id`', 100);
                        $this->set_join('left', '`artist`', '`song`.`artist`', '`artist`.`id`', 100);
                        break;
                    case 'year':
                        $sql = "`album`.`year`";
                        break;
                } // end switch
                break;
            case 'artist':
                switch ($field) {
                    case 'name':
                    case 'placeformed':
                    case 'yearformed':
                        $sql = "`artist`.`$field`";
                        break;
                } // end switch
                break;
            case 'playlist':
                switch ($field) {
                    case 'type':
                    case 'name':
                    case 'user':
                    case 'last_update':
                        $sql = "`playlist`.`$field`";
                        break;
                } // end switch
                break;
            case 'smartplaylist':
                switch ($field) {
                    case 'type':
                    case 'name':
                    case 'user':
                        $sql = "`search`.`$field`";
                        break;
                } // end switch on $field
                break;
            case 'live_stream':
                switch ($field) {
                    case 'name':
                    case 'codec':
                        $sql = "`live_stream`.`$field`";
                        break;
                } // end switch
                break;
            case 'tag':
                switch ($field) {
                    case 'tag':
                        $sql = "`tag`.`id`";
                        break;
                    case 'name':
                        $sql = "`tag`.`name`";
                        break;
                } // end switch
                break;
            case 'user':
                switch ($field) {
                    case 'username':
                    case 'fullname':
                    case 'last_seen':
                    case 'create_date':
                        $sql = "`user`.`$field`";
                        break;
                } // end switch
                break;
            case 'video':
                $sql = $this->sql_sort_video($field);
                break;
            case 'wanted':
                switch ($field) {
                    case 'name':
                    case 'artist':
                    case 'year':
                    case 'user':
                    case 'accepted':
                        $sql = "`wanted`.`$field`";
                        break;
                } // end switch on field
                break;
            case 'share':
                switch ($field) {
                    case 'object':
                        $sql = "`share`.`object_type`, `share`.`object.id`";
                        break;
                    case 'user':
                    case 'object_type':
                    case 'creation_date':
                    case 'lastvisit_date':
                    case 'counter':
                    case 'max_counter':
                    case 'allow_stream':
                    case 'allow_download':
                    case 'expire':
                        $sql = "`share`.`$field`";
                        break;
                } // end switch on field
                break;
            case 'channel':
                switch ($field) {
                    case 'name':
                    case 'interface':
                    case 'port':
                    case 'max_listeners':
                    case 'listeners':
                        $sql = "`channel`.`$field`";
                        break;
                } // end switch on field
                break;
            case 'broadcast':
                switch ($field) {
                    case 'name':
                    case 'user':
                    case 'started':
                    case 'listeners':
                        $sql = "`broadcast`.`$field`";
                        break;
                } // end switch on field
                break;
            case 'license':
                switch ($field) {
                    case 'name':
                        $sql = "`license`.`name`";
                        break;
                }
                break;
            case 'tvshow':
                switch ($field) {
                    case 'name':
                    case 'year':
                        $sql = "`tvshow`.`$field`";
                        break;
                }
                break;
            case 'tvshow_season':
                switch ($field) {
                    case 'season':
                        $sql = "`tvshow_season`.`season_number`";
                        break;
                    case 'tvshow':
                        $sql = "`tvshow`.`name`";
                        $this->set_join('left', '`tvshow`', '`tvshow_season`.`tvshow`', '`tvshow`.`id`', 100);
                        break;
                }
                break;
            case 'tvshow_episode':
                switch ($field) {
                    case 'episode':
                        $sql = "`tvshow_episode`.`episode_number`";
                        break;
                    case 'season':
                        $sql = "`tvshow_season`.`season_number`";
                        $this->set_join('left', '`tvshow_season`', '`tvshow_episode`.`season`', '`tvshow_season`.`id`', 100);
                        break;
                    case 'tvshow':
                        $sql = "`tvshow`.`name`";
                        $this->set_join('left', '`tvshow_season`', '`tvshow_episode`.`season`', '`tvshow_season`.`id`', 100);
                        $this->set_join('left', '`tvshow`', '`tvshow_season`.`tvshow`', '`tvshow`.`id`', 100);
                        break;
                    default:
                        $sql = $this->sql_sort_video($field, 'tvshow_episode');
                        break;
                }
                break;
            case 'movie':
                $sql = $this->sql_sort_video($field, 'movie');
                break;
            case 'clip':
                switch ($field) {
                    case 'location':
                        $sql = "`clip`.`artist`";
                        break;
                    default:
                        $sql = $this->sql_sort_video($field, 'clip');
                        break;
                }
                break;
            case 'personal_video':
                switch ($field) {
                    case 'location':
                        $sql = "`personal_video`.`location`";
                        break;
                    default:
                        $sql = $this->sql_sort_video($field, 'personal_video');
                        break;
                }
                break;
            case 'label':
                switch ($field) {
                    case 'name':
                    case 'category':
                    case 'user':
                        $sql = "`label`.`$field`";
                        break;
                }
                break;
            case 'pvmsg':
                switch ($field) {
                    case 'subject':
                    case 'to_user':
                    case 'creation_date':
                    case 'is_read':
                        $sql = "`user_pvmsg`.`$field`";
                        break;
                }
                break;
            case 'follower':
                switch ($field) {
                    case 'user':
                    case 'follow_user':
                    case 'follow_date':
                        $sql = "`user_follower`.`$field`";
                        break;
                }
                break;
            case 'podcast':
                switch ($field) {
                    case 'title':
                        $sql = "`podcast`.`title`";
                        break;
                }
                break;
            case 'podcast_episode':
                switch ($field) {
                    case 'title':
                    case 'category':
                    case 'author':
                    case 'time':
                    case 'pubDate':
                        $sql = "`podcast_episode`.`$field`";
                        break;
                }
                break;
            default:
                break;
        } // end switch

        if (isset($sql) && !empty($sql)) {
            return "$sql $order,";
        }

        return "";
    } // sql_sort

    /**
     *
     * @param string $field
     * @param string $table
     * @return string
     */
    private function sql_sort_video($field, $table = 'video')
    {
        $sql = "";
        switch ($field) {
            case 'title':
                $sql = "`video`.`title`";
                break;
            case 'resolution':
                $sql = "`video`.`resolution_x`";
                break;
            case 'length':
                $sql = "`video`.`time`";
                break;
            case 'codec':
                $sql = "`video`.`video_codec`";
                break;
            case 'release_date':
                $sql = "`video`.`release_date`";
                break;
        }

        if (!empty($sql)) {
            if ($table != 'video') {
                $this->set_join('left', '`video`', '`' . $table . '`.`id`', '`video`.`id`', 100);
            }
        }

        return $sql;
    }

    /**
     * resort_objects
     * This takes the existing objects, looks at the current
     * sort method and then re-sorts them This is internally
     * called by the set_sort() function
     * @return boolean
     */
    private function resort_objects()
    {
        // There are two ways to do this.. the easy way...
        // and the vollmer way, hopefully we don't have to
        // do it the vollmer way
        if ($this->is_simple()) {
            $sql = $this->get_sql();
        } else {
            // FIXME: this is fragile for large browses
            // First pull the objects
            $objects = $this->get_saved();

            // If there's nothing there don't do anything
            if (!count($objects) || !is_array($objects)) {
                return false;
            }
            $type      = $this->get_type();
            $where_sql = "WHERE `$type`.`id` IN (";

            foreach ($objects as $object_id) {
                $object_id = Dba::escape($object_id);
                $where_sql .= "'$object_id',";
            }
            $where_sql = rtrim((string) $where_sql, ', ');

            $where_sql .= ")";

            $sql = $this->get_base_sql();

            $group_sql = " GROUP BY `" . $this->get_type() . '`.`id`';
            $order_sql = " ORDER BY ";

            foreach ($this->_state['sort'] as $key => $value) {
                $sql_sort = $this->sql_sort($key, $value);
                $order_sql .= $sql_sort;
                $group_sql .= ", " . substr($sql_sort, 0, strpos($sql_sort, " "));
            }
            // Clean her up
            $order_sql = rtrim((string) $order_sql, "ORDER BY ");
            $order_sql = rtrim((string) $order_sql, ",");

            $sql = $sql . $this->get_join_sql() . $where_sql . $group_sql . $order_sql;
        } // if not simple

        $db_results = Dba::read($sql);
        //debug_event(self::class, "resort_objects: " . $sql, 5);

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        $this->save_objects($results);

        return true;
    } // resort_objects

    /**
     * store
     * This saves the current state to the database
     */
    public function store()
    {
        $browse_id = $this->id;
        if ($browse_id != 'nocache') {
            $data = self::_serialize($this->_state);

            $sql = 'UPDATE `tmp_browse` SET `data` = ? WHERE `sid` = ? AND `id` = ?';
            Dba::write($sql, array($data, session_id(), $browse_id));
        }
    }

    /**
     * save_objects
     * This takes the full array of object ids, often passed into show and
     * if necessary it saves them
     * @param array $object_ids
     * @return boolean
     */
    public function save_objects($object_ids)
    {
        // Saving these objects has two operations, one holds it in
        // a local variable and then second holds it in a row in the
        // tmp_browse table

        // Only do this if it's not a simple browse
        if (!$this->is_simple()) {
            $this->_cache = $object_ids;
            $this->set_total(count($object_ids));
            $browse_id = $this->id;
            if ($browse_id != 'nocache') {
                $data = self::_serialize($this->_cache);

                $sql = 'UPDATE `tmp_browse` SET `object_data` = ? ' .
                    'WHERE `sid` = ? AND `id` = ?';
                Dba::write($sql, array($data, session_id(), $browse_id));
            }
        }

        return true;
    } // save_objects

    /**
     * get_state
     * This is a debug only function
     * @return array
     */
    public function get_state()
    {
        return $this->_state;
    } // get_state

    /**
     * Get content div name
     * @return string
     */
    public function get_content_div()
    {
        $key = 'browse_content_' . $this->get_type();
        if ($this->_state['ak']) {
            $key .= '_' . $this->_state['ak'];
        }

        return $key;
    }

    /**
     * Set an additional content div key.
     * @param string $key
     */
    public function set_content_div_ak($key)
    {
        $this->_state['ak'] = $key;
    }
} // end query.class
