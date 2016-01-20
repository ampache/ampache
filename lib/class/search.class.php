<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
 * Search Class
 * Search-related voodoo.  Beware tentacles.
 */

class Search extends playlist_object
{
    public $searchtype;
    public $rules;
    public $logic_operator = 'AND';
    public $type           = 'public';
    public $random         = false;
    public $limit          = 0;

    public $basetypes;
    public $types;

    public $link;
    public $f_link;

    /**
     * constructor
     */
    public function __construct($id = null, $searchtype = 'song')
    {
        $this->searchtype = $searchtype;
        if ($id) {
            $info = $this->get_info($id);
            foreach ($info as $key=>$value) {
                $this->$key = $value;
            }

            $this->rules = unserialize($this->rules);
        }

        // Define our basetypes

        $this->basetypes['numeric'][] = array(
            'name'    => 'gte',
            'description' => T_('is greater than or equal to'),
            'sql'     => '>='
        );

        $this->basetypes['numeric'][] = array(
            'name'    => 'lte',
            'description' => T_('is less than or equal to'),
            'sql'     => '<='
        );

        $this->basetypes['numeric'][] = array(
            'name'    => 'equal',
            'description' => T_('is'),
            'sql'     => '<=>'
        );

        $this->basetypes['numeric'][] = array(
            'name'    => 'ne',
            'description' => T_('is not'),
            'sql'     => '<>'
        );

        $this->basetypes['numeric'][] = array(
            'name'    => 'gt',
            'description' => T_('is greater than'),
            'sql'     => '>'
        );

        $this->basetypes['numeric'][] = array(
            'name'    => 'lt',
            'description' => T_('is less than'),
            'sql'     => '<'
        );


        $this->basetypes['boolean'][] = array(
            'name'    => 'true',
            'description' => T_('is true')
        );

        $this->basetypes['boolean'][] = array(
            'name'    => 'false',
            'description' => T_('is false')
        );


        $this->basetypes['text'][] = array(
            'name'     => 'contain',
            'description'  => T_('contains'),
            'sql'      => 'LIKE',
            'preg_match'   => array('/^/','/$/'),
            'preg_replace' => array('%', '%')
        );

        $this->basetypes['text'][] = array(
            'name'     => 'notcontain',
            'description'  => T_('does not contain'),
            'sql'      => 'NOT LIKE',
            'preg_match'   => array('/^/','/$/'),
            'preg_replace' => array('%', '%')
        );

        $this->basetypes['text'][] = array(
            'name'     => 'start',
            'description'  => T_('starts with'),
            'sql'      => 'LIKE',
            'preg_match'   => '/$/',
            'preg_replace' => '%'
        );

        $this->basetypes['text'][] = array(
            'name'     => 'end',
            'description'  => T_('ends with'),
            'sql'      => 'LIKE',
            'preg_match'   => '/^/',
            'preg_replace' => '%'
        );

        $this->basetypes['text'][] = array(
            'name'    => 'equal',
            'description' => T_('is'),
            'sql'     => '='
        );

        $this->basetypes['text'][] = array(
            'name'    => 'sounds',
            'description' => T_('sounds like'),
            'sql'     => 'SOUNDS LIKE'
        );

        $this->basetypes['text'][] = array(
            'name'    => 'notsounds',
            'description' => T_('does not sound like'),
            'sql'     => 'NOT SOUNDS LIKE'
        );


        $this->basetypes['boolean_numeric'][] = array(
            'name'    => 'equal',
            'description' => T_('is'),
            'sql'     => '<=>'
        );

        $this->basetypes['boolean_numeric'][] = array(
            'name'    => 'ne',
            'description' => T_('is not'),
            'sql'     => '<>'
        );


        $this->basetypes['boolean_subsearch'][] = array(
            'name'    => 'equal',
            'description' => T_('is'),
            'sql'     => ''
        );

        $this->basetypes['boolean_subsearch'][] = array(
            'name'    => 'ne',
            'description' => T_('is not'),
            'sql'     => 'NOT'
        );


        $this->basetypes['date'][] = array(
            'name'    => 'lt',
            'description' => T_('before'),
            'sql'     => '<'
        );

        $this->basetypes['date'][] = array(
            'name'    => 'gt',
            'description' => T_('after'),
            'sql'     => '>'
        );
        $this->basetypes['multiple'] = array_merge($this->basetypes['text'], $this->basetypes['numeric']);

        switch ($searchtype) {
        case 'song':
            $this->types[] = array(
                'name'   => 'anywhere',
                'label'  =>  T_('Any searchable text'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'title',
                'label'  => T_('Title'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'album',
                'label'  => T_('Album'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'artist',
                'label'  => T_('Artist'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'composer',
                'label'  => T_('Composer'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'comment',
                'label'  =>  T_('Comment'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'label',
                'label'  =>  T_('Label'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );


            $this->types[] = array(
                'name'   => 'tag',
                'label'  => T_('Tag'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'album_tag',
                'label'  => T_('Album tag'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'file',
                'label'  => T_('Filename'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'year',
                'label'  => T_('Year'),
                'type'   => 'numeric',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'time',
                'label'  => T_('Length (in minutes)'),
                'type'   => 'numeric',
                'widget' => array('input', 'text')
            );

            if (AmpConfig::get('ratings')) {
                $this->types[] = array(
                    'name'   => 'rating',
                    'label'  => T_('Rating'),
                    'type'   => 'numeric',
                    'widget' => array(
                        'select',
                        array(
                            '1 Star',
                            '2 Stars',
                            '3 Stars',
                            '4 Stars',
                            '5 Stars'
                        )
                    )
                );
            }

            if (AmpConfig::get('show_played_times')) {
                $this->types[] = array(
                    'name'   => 'played_times',
                    'label'  => T_('# Played'),
                    'type'   => 'numeric',
                    'widget' => array('input', 'text')
                );
            }

            $this->types[] = array(
                'name'   => 'bitrate',
                'label'  => T_('Bitrate'),
                'type'   => 'numeric',
                'widget' => array(
                    'select',
                    array(
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
                        '320'
                    )
                )
            );

            $this->types[] = array(
                'name'   => 'played',
                'label'  => T_('Played'),
                'type'   => 'boolean',
                'widget' => array('input', 'hidden')
            );

            $this->types[] = array(
                'name'   => 'added',
                'label'  => T_('Added'),
                'type'   => 'date',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'updated',
                'label'  => T_('Updated'),
                'type'   => 'date',
                'widget' => array('input', 'text')
            );

            $catalogs = array();
            foreach (Catalog::get_catalogs() as $catid) {
                $catalog = Catalog::create_from_id($catid);
                $catalog->format();
                $catalogs[$catid] = $catalog->f_name;
            }
            $this->types[] = array(
                'name'   => 'catalog',
                'label'  => T_('Catalog'),
                'type'   => 'boolean_numeric',
                'widget' => array('select', $catalogs)
            );

            $playlists = array();
            foreach (Playlist::get_playlists() as $playlistid) {
                $playlist = new Playlist($playlistid);
                $playlist->format();
                $playlists[$playlistid] = $playlist->f_name;
            }
            $this->types[] = array(
                'name'   => 'playlist',
                'label'  => T_('Playlist'),
                'type' => 'boolean_numeric',
                'widget' => array('select', $playlists)
            );

            $this->types[] = array(
                'name'   => 'playlist_name',
                'label'  => T_('Playlist Name'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $playlists = array();
            foreach (Search::get_searches() as $playlistid) {
                // Slightly different from the above so we don't instigate
            // a vicious loop.
                $playlists[$playlistid] = Search::get_name_byid($playlistid);
            }
            $this->types[] = array(
                'name'   => 'smartplaylist',
                'label'  => T_('Smart Playlist'),
                'type'   => 'boolean_subsearch',
                'widget' => array('select', $playlists)
            );

            $metadataFields          = array();
            $metadataFieldRepository = new \Lib\Metadata\Repository\MetadataField();
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

            $licenses = array();
            foreach (License::get_licenses() as $license_id) {
                $license               = new License($license_id);
                $licenses[$license_id] = $license->name;
            }
            if (AmpConfig::get('licensing')) {
                $this->types[] = array(
                    'name'   => 'license',
                    'label'  => T_('Music License'),
                    'type'   => 'boolean_numeric',
                    'widget' => array('select', $licenses)
                );
            }

        break;
        case 'album':
            $this->types[] = array(
                'name'   => 'title',
                'label'  => T_('Title'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'artist',
                'label'  => T_('Artist'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'year',
                'label'  => T_('Year'),
                'type'   => 'numeric',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'image width',
                'label'  => T_('Image Width'),
                'type'   => 'numeric',
                'widget' => array('input', 'text')
            );

            $this->types[] = array(
                'name'   => 'image height',
                'label'  => T_('Image Height'),
                'type'   => 'numeric',
                'widget' => array('input', 'text')
            );

            if (AmpConfig::get('ratings')) {
                $this->types[] = array(
                    'name'   => 'rating',
                    'label'  => T_('Rating'),
                    'type'   => 'numeric',
                    'widget' => array(
                        'select',
                        array(
                            '1 Star',
                            '2 Stars',
                            '3 Stars',
                            '4 Stars',
                            '5 Stars'
                        )
                    )
                );
            }

            $catalogs = array();
            foreach (Catalog::get_catalogs() as $catid) {
                $catalog = Catalog::create_from_id($catid);
                $catalog->format();
                $catalogs[$catid] = $catalog->f_name;
            }
            $this->types[] = array(
                'name'   => 'catalog',
                'label'  => T_('Catalog'),
                'type'   => 'boolean_numeric',
                'widget' => array('select', $catalogs)
            );


            $this->types[] = array(
                'name'   => 'tag',
                'label'  => T_('Tag'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );
        break;
        case 'video':
            $this->types[] = array(
                'name'   => 'filename',
                'label'  => T_('Filename'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );
        break;
        case 'artist':
            $this->types[] = array(
                'name'   => 'name',
                'label'  => T_('Name'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );
            $this->types[] = array(
                'name'   => 'yearformed',
                'label'  => T_('Year'),
                'type'   => 'numeric',
                'widget' => array('input', 'text')
            );
            $this->types[] = array(
                'name'   => 'placeformed',
                'label'  => T_('Place'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );
            $this->types[] = array(
                'name'   => 'tag',
                'label'  => T_('Tag'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );
        break;
        case 'playlist':
            $this->types[] = array(
                'name'   => 'name',
                'label'  => T_('Name'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );
        break;
        case 'label':
            $this->types[] = array(
                'name'   => 'name',
                'label'  => T_('Name'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );
            $this->types[] = array(
                'name'   => 'category',
                'label'  => T_('Category'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );
        break;
        case 'user':
            $this->types[] = array(
                'name'   => 'username',
                'label'  => T_('Username'),
                'type'   => 'text',
                'widget' => array('input', 'text')
            );
        break;
        } // end switch on searchtype
    } // end constructor

    /**
     * clean_request
     *
     * Sanitizes raw search data
     */
    public static function clean_request($data)
    {
        $request = array();
        foreach ($data as $key => $value) {
            $prefix = substr($key, 0, 4);
            $value  = trim($value);

            if ($prefix == 'rule' && strlen($value)) {
                $request[$key] = Dba::escape($value);
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
     */
    public static function get_name_byid($id)
    {
        $sql        = "SELECT `name` FROM `search` WHERE `id` = '$id'";
        $db_results = Dba::read($sql);
        $r          = Dba::fetch_assoc($db_results);
        return $r['name'];
    }

    /**
     * get_searches
     *
     * Return the IDs of all saved searches accessible by the current user.
     */
    public static function get_searches()
    {
        $sql = "SELECT `id` from `search` WHERE `type`='public' OR " .
            "`user`='" . $GLOBALS['user']->id . "' ORDER BY `name`";
        $db_results = Dba::read($sql);

        $results = array();

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
     */
    public static function run($data)
    {
        $limit  = intval($data['limit']);
        $offset = intval($data['offset']);
        $data   = Search::clean_request($data);

        $search = new Search(null, $data['type']);
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
        $sql .= ' ' . $limit_sql;
        $sql = trim($sql);

        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * delete
     *
     * Does what it says on the tin.
     */
    public function delete()
    {
        $id  = Dba::escape($this->id);
        $sql = "DELETE FROM `search` WHERE `id` = ?";
        Dba::write($sql, array($id));

        return true;
    }

    /**
     * format
     * Gussy up the data
     */
    public function format($details = true)
    {
        parent::format();

        $this->link   = AmpConfig::get('web_path') . '/smartplaylist.php?action=show_playlist&playlist_id=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '">' . $this->f_name . '</a>';
    }

    /**
     * get_items
     *
     * Return an array of the items output by our search (part of the
     * playlist interface).
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

        if ($this->random) {
            $sql .= " ORDER BY RAND()";
        }
        if ($this->limit > 0) {
            $sql .= " LIMIT " . intval($this->limit);
        }

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
     * get_random_items
     *
     * Returns a randomly sorted array (with an optional limit) of the items
     * output by our search (part of the playlist interface)
     */
    public function get_random_items($limit = null)
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

        $sql .= ' ORDER BY RAND()';
        $sql .= $limit ? ' LIMIT ' . intval($limit) : '';

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
     * name_to_basetype
     *
     * Iterates over our array of types to find out the basetype for
     * the passed string.
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
     * Takes an array of sanitized search data from the form and generates
     * our real array from it.
     */
    public function parse_rules($data)
    {
        $this->rules = array();
        foreach ($data as $rule => $value) {
            if (preg_match('/^rule_(\d+)$/', $rule, $ruleID)) {
                $ruleID = $ruleID[1];
                foreach (explode('|', $data['rule_' . $ruleID . '_input']) as $input) {
                    $this->rules[] = array(
                        $value,
                        $this->basetypes[$this->name_to_basetype($value)][$data['rule_' . $ruleID . '_operator']]['name'],
                        $input,
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
     */
    public function save()
    {
        // Make sure we have a unique name
        if (! $this->name) {
            $this->name = $GLOBALS['user']->username . ' - ' . date('Y-m-d H:i:s', time());
        }
        $sql        = "SELECT `id` FROM `search` WHERE `name` = ?";
        $db_results = Dba::read($sql, array($this->name));
        if (Dba::num_rows($db_results)) {
            $this->name .= uniqid('', true);
        }

        $sql = "INSERT INTO `search` (`name`, `type`, `user`, `rules`, `logic_operator`, `random`, `limit`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($this->name, $this->type, $GLOBALS['user']->id, serialize($this->rules), $this->logic_operator, $this->random ? 1 : 0, $this->limit));
        $insert_id = Dba::insert_id();
        $this->id  = $insert_id;
        return $insert_id;
    }


    /**
     * to_js
     *
     * Outputs the javascript necessary to re-show the current set of rules.
     */
    public function to_js()
    {
        $js = "";
        foreach ($this->rules as $rule) {
            $js .= '<script type="text/javascript">' .
                'SearchRow.add("' . $rule[0] . '","' .
                $rule[1] . '","' . $rule[2] . '", "' . $rule[3] . '"); </script>';
        }
        return $js;
    }

    /**
     * to_sql
     *
     * Call the appropriate real function.
     */
    public function to_sql()
    {
        return call_user_func(array($this, $this->searchtype . "_to_sql"));
    }

    /**
     * update
     *
     * This function updates the saved version with the current settings.
     */
    public function update(array $data = null)
    {
        if ($data && is_array($data)) {
            $this->name   = $data['name'];
            $this->type   = $data['pl_type'];
            $this->random = $data['random'];
            $this->limit  = $data['limit'];
        }

        if (!$this->id) {
            return false;
        }

        $sql = "UPDATE `search` SET `name` = ?, `type` = ?, `rules` = ?, `logic_operator` = ?, `random` = ?, `limit` = ? WHERE `id` = ?";
        Dba::write($sql, array($this->name, $this->type, serialize($this->rules), $this->logic_operator, $this->random, $this->limit, $this->id));

        return $this->id;
    }

    public static function gc()
    {
    }

    /**
     * _mangle_data
     *
     * Private convenience function.  Mangles the input according to a set
     * of predefined rules so that we don't have to include this logic in
     * foo_to_sql.
     */
    private function _mangle_data($data, $type, $operator)
    {
        if ($operator['preg_match']) {
            $data = preg_replace(
                $operator['preg_match'],
                $operator['preg_replace'],
                $data
            );
        }

        if ($type == 'numeric') {
            return intval($data);
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
     */
    private function album_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $join['tag'] = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_mangle_data($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'title':
                    $where[] = "`album`.`name` $sql_match_operator '$input'";
                break;
                case 'year':
                    $where[] = "`album`.`year` $sql_match_operator '$input'";
                break;
                case 'rating':
                    if ($this->type != "public") {
                        $where[] = "COALESCE(`rating`.`rating`,0) $sql_match_operator '$input'";
                    } else {
                        $group[]  = "`album`.`id`";
                        $having[] = "ROUND(AVG(IFNULL(`rating`.`rating`,0))) $sql_match_operator '$input'";
                    }
                    $join['rating'] = true;
                break;
                case 'catalog':
                    $where[]      = "`song`.`catalog` $sql_match_operator '$input'";
                    $join['song'] = true;
                break;
                case 'tag':
                    $key               = md5($input . $sql_match_operator);
                    $where[]           = "`realtag_$key`.`match` > 0";
                    $join['tag'][$key] = "$sql_match_operator '$input'";
                break;
                case 'image height':
                    $where[]       = "`image`.`height` $sql_match_operator '$input'";
                    $join['image'] = true;
                break;
                case 'image width':
                    $where[]       = "`image`.`width` $sql_match_operator '$input'";
                    $join['image'] = true;
                break;
                case 'artist':
                    $where[]        = "`artist`.`name` $sql_match_operator '$input'";
                    $join['artist'] = true;
                break;
                default:
                    // Nae laird!
                break;
            } // switch on ruletype
        } // foreach rule

        $join['song']    = $join['song'] || AmpConfig::get('catalog_disable');
        $join['catalog'] = AmpConfig::get('catalog_disable');

        $where_sql = implode(" $sql_logic_operator ", $where);

        foreach ($join['tag'] as $key => $value) {
            $table['tag_' . $key] =
                "LEFT JOIN (" .
                "SELECT `object_id`, COUNT(`name`) AS `match` " .
                "FROM `tag` LEFT JOIN `tag_map` " .
                "ON `tag`.`id`=`tag_map`.`tag_id` " .
                "WHERE `tag_map`.`object_type`='album' " .
                "AND `tag`.`name` $value GROUP BY `object_id`" .
                ") AS realtag_$key " .
                "ON `album`.`id`=`realtag_$key`.`object_id`";
        }
        if ($join['artist']) {
            $table['artist'] = "LEFT JOIN `artist` ON `artist`.`id`=`album`.`album_artist`";
        }
        if ($join['song']) {
            $table['song'] = "LEFT JOIN `song` ON `song`.`album`=`album`.`id`";

            if ($join['catalog']) {
                $table['catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
                $where_sql .= " AND `catalog_se`.`enabled` = '1'";
            }
        }
        if ($join['rating']) {
            $userid          = intval($GLOBALS['user']->id);
            $table['rating'] = "LEFT JOIN `rating` ON `rating`.`object_type`='album' ";
            if ($this->type != 'public') {
                $table['rating'] .= "AND `rating`.`user`='$userid' ";
            }
            $table['rating'] .= "AND `rating`.`object_id`=`album`.`id`";
        }
        if ($join['image']) {
            $table['song'] = "LEFT JOIN `image` ON `image`.`object_id`=`album`.`id`";
            $where_sql .= " AND `image`.`object_type`='album'";
            $where_sql .= " AND `image`.`size`='original'";
        }

        $table_sql  = implode(' ', $table);
        $group_sql  = implode(', ', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`album`.`id`) FROM `album`',
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
     */
    private function artist_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $where              = array();
        $table              = array();
        $join               = array();
        $group              = array();
        $having             = array();
        $join['tag']        = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_mangle_data($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'name':
                    $where[] = "`artist`.`name` $sql_match_operator '$input'";
                break;
                case 'yearformed':
                    $where[] = "`artist`.`yearformed` $sql_match_operator '$input'";
                break;
                case 'placeformed':
                    $where[] = "`artist`.`placeformed` $sql_match_operator '$input'";
                break;
                case 'tag':
                    $key               = md5($input . $sql_match_operator);
                    $where[]           = "`realtag_$key`.`match` > 0";
                    $join['tag'][$key] = "$sql_match_operator '$input'";
                break;
                default:
                    // Nihil
                break;
            } // switch on ruletype
        } // foreach rule

        $join['song']    = $join['song'] || AmpConfig::get('catalog_disable');
        $join['catalog'] = AmpConfig::get('catalog_disable');

        $where_sql = implode(" $sql_logic_operator ", $where);

        foreach ($join['tag'] as $key => $value) {
            $table['tag_' . $key] =
                "LEFT JOIN (" .
                "SELECT `object_id`, COUNT(`name`) AS `match` " .
                "FROM `tag` LEFT JOIN `tag_map` " .
                "ON `tag`.`id`=`tag_map`.`tag_id` " .
                "WHERE `tag_map`.`object_type`='artist' " .
                "AND `tag`.`name` $value  GROUP BY `object_id`" .
                ") AS realtag_$key " .
                "ON `artist`.`id`=`realtag_$key`.`object_id`";
        }

        if ($join['song']) {
            $table['song'] = "LEFT JOIN `song` ON `song`.`artist`=`artist`.`id`";

            if ($join['catalog']) {
                $table['catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
                $where_sql .= " AND `catalog_se`.`enabled` = '1'";
            }
        }

        $table_sql  = implode(' ', $table);
        $group_sql  = implode(', ', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`artist`.`id`) FROM `artist`',
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
     */
    private function song_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $join['tag'] = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_mangle_data($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'anywhere':
                    $where[]           = "(`artist`.`name` $sql_match_operator '$input' OR `album`.`name` $sql_match_operator '$input' OR `song_data`.`comment` $sql_match_operator '$input' OR `song_data`.`label` $sql_match_operator '$input' OR `song`.`file` $sql_match_operator '$input' OR `song`.`title` $sql_match_operator '$input')";
                    $join['album']     = true;
                    $join['artist']    = true;
                    $join['song_data'] = true;
                break;
                case 'tag':
                    $key               = md5($input . $sql_match_operator);
                    $where[]           = "`realtag_$key`.`match` > 0";
                    $join['tag'][$key] = "$sql_match_operator '$input'";
                break;
                case 'album_tag':
                    $key                     = md5($input . $sql_match_operator);
                    $where[]                 = "`realtag_$key`.`match` > 0";
                    $join['album_tag'][$key] = "$sql_match_operator '$input'";
                    $join['album']           = true;
                break;
                case 'title':
                    $where[] = "`song`.`title` $sql_match_operator '$input'";
                break;
                case 'album':
                    $where[]       = "`album`.`name` $sql_match_operator '$input'";
                    $join['album'] = true;
                break;
                case 'artist':
                    $where[]        = "`artist`.`name` $sql_match_operator '$input'";
                    $join['artist'] = true;
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
                case 'played':
                    $where[] = " `song`.`played` = '$input'";
                break;
                case 'bitrate':
                    $input   = $input * 1000;
                    $where[] = "`song`.`bitrate` $sql_match_operator '$input'";
                break;
                case 'rating':
                    if ($this->type != "public") {
                        $where[] = "COALESCE(`rating`.`rating`,0) $sql_match_operator '$input'";
                    } else {
                        $group[]  = "`song`.`id`";
                        $having[] = "ROUND(AVG(IFNULL(`rating`.`rating`,0))) $sql_match_operator '$input'";
                    }
                    $join['rating'] = true;
                break;
                case 'played_times':
                    $where[] = "`song`.`id` IN (SELECT `object_count`.`object_id` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' " .
                        "GROUP BY `object_count`.`object_id` HAVING COUNT(*) $sql_match_operator '$input')";
                break;
                case 'catalog':
                    $where[] = "`song`.`catalog` $sql_match_operator '$input'";
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
                    $subsearch = new Search($input, 'song');
                    $subsql    = $subsearch->to_sql();
                    $where[]   = "$sql_match_operator (" . $subsql['where_sql'] . ")";
                    // HACK: array_merge would potentially lose tags, since it
                    // overwrites. Save our merged tag joins in a temp variable,
                    // even though that's ugly.
                    $tagjoin     = array_merge($subsql['join']['tag'], $join['tag']);
                    $join        = array_merge($subsql['join'], $join);
                    $join['tag'] = $tagjoin;
                break;
                case 'license':
                    $where[] = "`song`.`license` $sql_match_operator '$input'";
                break;
                case 'added':
                    $input   = strtotime($input);
                    $where[] = "`song`.`addition_time` $sql_match_operator $input";
                break;
                case 'updated':
                    $input   = strtotime($input);
                    $where[] = "`song`.`update_time` $sql_match_operator $input";
                    break;
                case 'metadata':
                    // Need to create a join for every field so we can create and / or queries with only one table
                    $tableAlias         = 'metadata' . uniqid();
                    $field              = (int) $rule[3];
                    $join[$tableAlias]  = true;
                    $parsedInput        = is_numeric($input) ? $input : '"' . $input . '"';
                    $where[]            = "(`$tableAlias`.`field` = {$field} AND `$tableAlias`.`data` $sql_match_operator $parsedInput)";
                    $table[$tableAlias] = 'LEFT JOIN `metadata` AS ' . $tableAlias . ' ON `song`.`id` = `' . $tableAlias . '`.`object_id`';
                    break;
                default:
                    // NOSSINK!
                break;
            } // switch on type
        } // foreach over rules

        $join['catalog'] = AmpConfig::get('catalog_disable');

        $where_sql = implode(" $sql_logic_operator ", $where);

        // now that we know which things we want to JOIN...
        if ($join['artist']) {
            $table['artist'] = "LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`";
        }
        if ($join['album']) {
            $table['album'] = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
        }
        if ($join['song_data']) {
            $table['song_data'] = "LEFT JOIN `song_data` ON `song`.`id`=`song_data`.`song_id`";
        }
        if ($join['tag']) {
            foreach ($join['tag'] as $key => $value) {
                $table['tag_' . $key] =
                    "LEFT JOIN (" .
                    "SELECT `object_id`, COUNT(`name`) AS `match` " .
                    "FROM `tag` LEFT JOIN `tag_map` " .
                    "ON `tag`.`id`=`tag_map`.`tag_id` " .
                    "WHERE `tag_map`.`object_type`='song' " .
                    "AND `tag`.`name` $value GROUP BY `object_id`" .
                    ") AS realtag_$key " .
                    "ON `song`.`id`=`realtag_$key`.`object_id`";
            }
        }
        if ($join['album_tag']) {
            foreach ($join['album_tag'] as $key => $value) {
                $table['tag_' . $key] =
                    "LEFT JOIN (" .
                    "SELECT `object_id`, COUNT(`name`) AS `match` " .
                    "FROM `tag` LEFT JOIN `tag_map` " .
                    "ON `tag`.`id`=`tag_map`.`tag_id` " .
                    "WHERE `tag_map`.`object_type`='album' " .
                    "AND `tag`.`name` $value  GROUP BY `object_id`" .
                    ") AS realtag_$key " .
                    "ON `album`.`id`=`realtag_$key`.`object_id`";
            }
        }
        if ($join['rating']) {
            $userid          = $GLOBALS['user']->id;
            $table['rating'] = "LEFT JOIN `rating` ON `rating`.`object_type`='song' AND ";
            if ($this->type != "public") {
                $table['rating'] .= "`rating`.`user`='$userid' AND ";
            }
            $table['rating'] .= "`rating`.`object_id`=`song`.`id`";
        }
        if ($join['playlist_data']) {
            $table['playlist_data'] = "LEFT JOIN `playlist_data` ON `song`.`id`=`playlist_data`.`object_id` AND `playlist_data`.`object_type`='song'";
            if ($join['playlist']) {
                $table['playlist'] = "LEFT JOIN `playlist` ON `playlist_data`.`playlist`=`playlist`.`id`";
            }
        }

        if ($join['catalog']) {
            $table['catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
            $where_sql .= " AND `catalog_se`.`enabled` = '1'";
        }

        $table_sql  = implode(' ', $table);
        $group_sql  = implode(', ', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`song`.`id`) FROM `song`',
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
     */
    private function video_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;

        $where  = array();
        $table  = array();
        $join   = array();
        $group  = array();
        $having = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_mangle_data($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'filename':
                    $where[] = "`video`.`file` $sql_match_operator '$input'";
                break;
                default:
                    // WE WILLNA BE FOOLED AGAIN!
            } // switch on ruletype
        } // foreach rule

        $join['catalog'] = AmpConfig::get('catalog_disable');

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($join['catalog']) {
            $table['catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`video`.`catalog`";
            $where_sql .= " AND `catalog_se`.`enabled` = '1'";
        }

        $table_sql  = implode(' ', $table);
        $group_sql  = implode(', ', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`video`.`id`) FROM `video`',
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
     */
    private function playlist_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $where              = array();
        $table              = array();
        $join               = array();
        $group              = array();
        $having             = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_mangle_data($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'];

            $where[] = "`playlist`.`type` = 'public'";

            switch ($rule[0]) {
                case 'name':
                    $where[] = "`playlist`.`name` $sql_match_operator '$input'";
                break;
                default:
                    // Nihil
                break;
            } // switch on ruletype
        } // foreach rule

        $join['playlist_data'] = true;
        $join['song']          = $join['song'] || AmpConfig::get('catalog_disable');
        $join['catalog']       = AmpConfig::get('catalog_disable');

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($join['playlist_data']) {
            $table['playlist_data'] = "LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id`";
        }

        if ($join['song']) {
            $table['song'] = "LEFT JOIN `song` ON `song`.`id`=`playlist_data`.`object_id`";
            $where_sql .= " AND `playlist_data`.`object_type` = 'song'";

            if ($join['catalog']) {
                $table['catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
                $where_sql .= " AND `catalog_se`.`enabled` = '1'";
            }
        }

        $table_sql  = implode(' ', $table);
        $group_sql  = implode(', ', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`playlist`.`id`) FROM `playlist`',
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
     */
    private function label_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $where              = array();
        $table              = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_mangle_data($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'name':
                    $where[] = "`label`.`name` $sql_match_operator '$input'";
                break;
                case 'category':
                    $where[] = "`label`.`category` $sql_match_operator '$input'";
                break;
                default:
                    // Nihil
                break;
            } // switch on ruletype
        } // foreach rule

        $where_sql = implode(" $sql_logic_operator ", $where);

        return array(
            'base' => 'SELECT DISTINCT(`label`.`id`) FROM `label`',
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
     */
    private function user_to_sql()
    {
        $sql_logic_operator = $this->logic_operator;
        $where              = array();
        $table              = array();

        foreach ($this->rules as $rule) {
            $type     = $this->name_to_basetype($rule[0]);
            $operator = array();
            foreach ($this->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input              = $this->_mangle_data($rule[2], $type, $operator);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'username':
                    $where[] = "`user`.`username` $sql_match_operator '$input'";
                break;
                default:
                    // Nihil
                break;
            } // switch on ruletype
        } // foreach rule

        $where_sql = implode(" $sql_logic_operator ", $where);

        return array(
            'base' => 'SELECT DISTINCT(`user`.`id`) FROM `user`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => '',
            'group_sql' => '',
            'having_sql' => ''
        );
    }
}
