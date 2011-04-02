<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 * Search Class
 * Search-related voodoo.  Beware tentacles.
 */

class Search extends playlist_object {

	public $searchtype;
	public $rules;
	public $logic_operator = 'AND';
	public $type = 'public';

	public $basetypes;
	public $types;

	/**
	 * constructor
	 */
	public function __construct($searchtype = 'song', $id = '') {
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
			'name'	=> 'gte',
			'description' => _('is greater than or equal to'),
			'sql'	 => '>='
		);

		$this->basetypes['numeric'][] = array(
			'name'	=> 'lte',
			'description' => _('is less than or equal to'),
			'sql'	 => '<='
		);

		$this->basetypes['numeric'][] = array(
			'name'	=> 'equal',
			'description' => _('is'),
			'sql'	 => '<=>'
		);

		$this->basetypes['numeric'][] = array(
			'name'	=> 'ne',
			'description' => _('is not'),
			'sql'	 => '<>'
		);

		$this->basetypes['numeric'][] = array(
			'name'	=> 'gt',
			'description' => _('is greater than'),
			'sql'	 => '>'
		);

		$this->basetypes['numeric'][] = array(
			'name'	=> 'lt',
			'description' => _('is less than'),
			'sql'	 => '<'
		);


		$this->basetypes['boolean'][] = array(
			'name'	=> 'true',
			'description' => _('is true')
		);

		$this->basetypes['boolean'][] = array(
			'name'	=> 'false',
			'description' => _('is false')
		);


		$this->basetypes['text'][] = array(
			'name'	 => 'contain',
			'description'  => _('contains'),
			'sql'	  => 'LIKE',
			'preg_match'   => array('/^/','/$/'),
			'preg_replace' => array('%', '%')
		);

		$this->basetypes['text'][] = array(
			'name'	 => 'notcontain',
			'description'  => _('does not contain'),
			'sql'	  => 'NOT LIKE',
			'preg_match'   => array('/^/','/$/'),
			'preg_replace' => array('%', '%')
		);

		$this->basetypes['text'][] = array(
			'name'	 => 'start',
			'description'  => _('starts with'),
			'sql'	  => 'LIKE',
			'preg_match'   => '/$/',
			'preg_replace' => '%'
		);

		$this->basetypes['text'][] = array(
			'name'	 => 'end',
			'description'  => _('ends with'),
			'sql'	  => 'LIKE',
			'preg_match'   => '/^/',
			'preg_replace' => '%'
		);

		$this->basetypes['text'][] = array(
			'name'	=> 'equal',
			'description' => _('is'),
			'sql'	 => '='
		);

		$this->basetypes['text'][] = array(
			'name'	=> 'sounds',
			'description' => _('sounds like'),
			'sql'	 => 'SOUNDS LIKE'
		);

		$this->basetypes['text'][] = array(
			'name'	=> 'notsounds',
			'description' => _('does not sound like'),
			'sql'	 => 'NOT SOUNDS LIKE'
		);
		

		$this->basetypes['boolean_numeric'][] = array(
			'name'	=> 'equal',
			'description' => _('is'),
			'sql'	 => '<=>'
		);
		
		 $this->basetypes['boolean_numeric'][] = array(
			'name'	=> 'ne',
			'description' => _('is not'),
			'sql'	 => '<>'
		);


		$this->basetypes['boolean_subsearch'][] = array(
			'name'	=> 'equal',
			'description' => _('is'),
			'sql'	 => ''
		);
		
		$this->basetypes['boolean_subsearch'][] = array(
			'name'	=> 'ne',
			'description' => _('is not'),
			'sql'	 => 'NOT'
		);


		$this->basetypes['date'][] = array(
			'name'	=> 'lt',
			'description' => _('before'),
			'sql'	 => '>'
		);
		
		$this->basetypes['date'][] = array(
			'name'	=> 'gt',
			'description' => _('after'),
			'sql'	 => '>'
		);

		switch ($searchtype) {
		case 'song':
			$this->types[] = array(
				'name'   => 'anywhere',
				'label'  =>  _('Any searchable text'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);

			$this->types[] = array(
				'name'   => 'title',
				'label'  => _('Title'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);

			$this->types[] = array(
				'name'   => 'album',
				'label'  => _('Album'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);

			$this->types[] = array(
				'name'   => 'artist',
				'label'  => _('Artist'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);

			$this->types[] = array(
				'name'   => 'comment',
				'label'  =>  _('Comment'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);

			
			$this->types[] = array(
				'name'   => 'tag',
				'label'  => _('Tag'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);

			$this->types[] = array(
				'name'   => 'file',
				'label'  => _('Filename'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);

			$this->types[] = array(
				'name'   => 'year',
				'label'  => _('Year'),
				'type'   => 'numeric',
				'widget' => array('input', 'text')
			);

			$this->types[] = array(
				'name'   => 'time', 
				'label'  => _('Length (in minutes)'),
				'type'   => 'numeric',
				'widget' => array('input', 'text')
			);

			if (Config::get('ratings')) {
				$this->types[] = array(
					'name'   => 'rating',
					'label'  => _('Rating'),
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

			$this->types[] = array(
				'name'   => 'bitrate',
				'label'  => _('Bitrate'),
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
				'label'  => _('Played'),
				'type'   => 'boolean',
				'widget' => array('input', 'hidden')
			);

			$this->types[] = array(
				'name'   => 'added',
				'label'  => _('Added'),
				'type'   => 'date',
				'widget' => array('input', 'text')
			);

			$this->types[] = array(
				'name'   => 'updated',
				'label'  => _('Updated'),
				'type'   => 'date',
				'widget' => array('input', 'text')
			);

			$catalogs = array();
			foreach (Catalog::get_catalogs() as $catid) {
				$catalog = new Catalog($catid);
				$catalog->format();
				$catalogs[$catid] = $catalog->f_name;
			}
			$this->types[] = array(
				'name'   => 'catalog',
				'label'  => _('Catalog'),
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
				'label'  => _('Playlist'),
				'type'   => 'boolean_numeric',
				'widget' => array('select', $playlists)
			);

			$playlists = array();
			foreach (Search::get_searches() as $playlistid) {
			// Slightly different from the above so we don't 
			// instigate a vicious loop.
				$playlists[$playlistid] = Search::get_name_byid($playlistid);
			}
			$this->types[] = array(
				'name'   => 'smartplaylist',
				'label'  => _('Smart Playlist'),
				'type'   => 'boolean_subsearch',
				'widget' => array('select', $playlists)
			);
		break;
		case 'album':
			$this->types[] = array(
				'name'   => 'title',
				'label'  => _('Title'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);

			$this->types[] = array(
				'name'   => 'year',
				'label'  => _('Year'),
				'type'   => 'numeric',
				'widget' => array('input', 'text')
			);

			if (Config::get('ratings')) {
				$this->types[] = array(
					'name'   => 'rating',
					'label'  => _('Rating'),
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
				$catalog = new Catalog($catid);
				$catalog->format();
				$catalogs[$catid] = $catalog->f_name;
			}
			$this->types[] = array(
				'name'   => 'catalog',
				'label'  => _('Catalog'),
				'type'   => 'boolean_numeric',
				'widget' => array('select', $catalogs)
			);
				

			$this->types[] = array(
				'name'   => 'tag',
				'label'  => _('Tag'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);
		break;
		case 'video':
			$this->types[] = array(
				'name'   => 'filename',
				'label'  => _('Filename'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);
		break;
		case 'artist':
			$this->types[] = array(
				'name'   => 'name',
				'label'  => _('Name'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);
			$this->types[] = array(
				'name'   => 'tag',
				'label'  => _('Tag'),
				'type'   => 'text',
				'widget' => array('input', 'text')
			);
		break;
		} // end switch on searchtype

	} // end constructor

	/**
	 * clean_request
	 * Sanitizes raw search data
	 */
	public static function clean_request($data) {
		foreach ($data as $key => $value) {
			$prefix = substr($key, 0, 4);
			$value = trim($value);

			if ($prefix == 'rule' && strlen($value)) {
				$request[$key] = Dba::escape($value);
			}
		} // end foreach $data

		// Figure out if they want an AND based search or an OR based 
		// search
		switch($data['operator']) {
			case 'or':
				$request['operator'] = 'OR';
			break;
			default:
				$request['operator'] = 'AND';
			break;
		} // end switcn on operator

		return $request;
	} // end clean_request

	/** 
	 * get_name_byid
	 * Returns the name of the saved search corresponding to the given ID
	 */
	public static function get_name_byid($id) {
		$sql = "SELECT `name` FROM `search` WHERE `id`='$id'";
		$db_results = Dba::read($sql);
		$r = Dba::fetch_assoc($db_results);
		return $r['name'];
	 } // end get_name_byid

	/**
	 * get_searches
	 * Return the IDs of all saved searches accessible by the current user.
	 */
	public static function get_searches() {
		$sql = "SELECT `id` from `search` WHERE `type`='public' OR " .
			"`user`='" . $GLOBALS['user']->id . "' ORDER BY `name`";
		$db_results = Dba::read($sql);

		$results = array();

		while ($row = Dba::fetch_assoc($db_results)) {
			$results[] = $row['id'];
		}

		return $results;
	} // end get_searches

	/**
	 * run
	 * This function actually runs the search, and returns an array of the
	 * results.
	 */
	public static function run($data) {
		$limit = intval($data['limit']);
		/* Create an array of the object we need to search on */
		$data = Search::clean_request($data);

		$search = new Search($_REQUEST['type']);
		$search->parse_rules($data);

		/* Generate BASE SQL */

		if ($limit > 0) {
			$limit_sql = " LIMIT " . $limit;
		}

		$search_info = $search->to_sql();
		$sql = $search_info['base'] . ' ' . $search_info['table_sql'] .
			' WHERE ' . $search_info['where_sql'] . " $limit_sql";

		$db_results = Dba::read($sql);

		$results = array();

		while ($row = Dba::fetch_assoc($db_results)) {
			$results[] = $row['id'];
		}

		return $results;
	} // run

	/**
	 * delete
	 * Does what it says on the tin.
	 */
	public function delete() {
		$id = Dba::escape($this->id);
		$sql = "DELETE FROM `search` WHERE `id`='$id'";
		$db_results = Dba::write($sql);

		return true;
	} // end delete

	/**
	 * format
	 * Gussy up the data
	 */
	public function format() {
		parent::format();
		$this->f_link = '<a href="' . Config::get('web_path') . '/smartplaylist.php?action=show_playlist&amp;playlist_id=' . $this->id . '">' . $this->f_name . '</a>';
	} // end format

	/**
	 * get_items
	 * return an array of the items output by our search (part of the
	 * playlist interface).
	 */
	public function get_items() {
		$results = array();

		$sql = $this->to_sql();
		$sql = $sql['base'] . ' ' . $sql['table_sql'] . ' WHERE ' .
			$sql['where_sql'];

		$db_results = Dba::read($sql);

		while ($row = Dba::fetch_assoc($db_results)) {
			$results[] = array(
				'object_id' => $row['id'],
				'type' => $this->searchtype
			);
		}

		return $results;
	} // end get_items

	/** 
	 * name_to_basetype
	 * Iterates over our array of types to find out the basetype for
	 * the passed string.
	 */
	public function name_to_basetype($name) {
		foreach ($this->types as $type) {
			if ($type['name'] == $name) {
				return $type['type'];
			}
		}
		return false;
	} // end name_to_basetype

	/** 
	 * parse_rules
	 * Takes an array of sanitized search data from the form and generates 
	 * our real array from it.
	 */
	public function parse_rules($data) {
		$this->rules = array();
		foreach ($data as $rule => $value) {
			if (preg_match('/^rule_(\d)$/', $rule, $ruleID)) {
				$ruleID = $ruleID[1];
				foreach (explode('|', $data['rule_' . $ruleID . '_input']) as $input) {
					$this->rules[] = array(
						$value,
						$this->basetypes[$this->name_to_basetype($value)][$data['rule_' . $ruleID . '_operator']]['name'],
						$input
					);
				}
			}
		}
		$this->logic_operator = $data['operator'];
	} // end parse_rules

	/**
	 * save
	 * Save this search to the database for use as a smart playlist
	 */
	public function save() {
		// Make sure we have a unique name
		if (! $this->name) {
			$this->name = $GLOBALS['user']->username . ' - ' . date("Y-m-d H:i:s",time());
		}
		$sql = "SELECT `id` FROM `search` WHERE `name`='$this->name'";
		$db_results = Dba::read($sql);
		if (Dba::num_rows($db_results)) {
			$this->name .= uniqid('', true);
		}

		// clean up variables for insert
		$name = Dba::escape($this->name);
		$user = Dba::escape($GLOBALS['user']->id);
		$type = Dba::escape($this->type);
		$rules = serialize($this->rules);
		$logic_operator = $this->logic_operator;

		$sql = "INSERT INTO `search` (`name`, `type`, `user`, `rules`, `logic_operator`) VALUES ('$name', '$type', '$user', '$rules', '$logic_operator')";
		$db_results = Dba::write($sql);
		$insert_id = Dba::insert_id();
		$this->id = $insert_id;
		return $insert_id;
	} // end save


	/**
	 * to_js
	 * Outputs the javascript necessary to re-show the current set of 
	 * rules.
	 */
	public function to_js() {
		foreach ($this->rules as $rule) {
			$js .= '<script type="text/javascript">' .
				'SearchRow.add("' . $rule[0] . '","' .
				$rule[1] . '","' . $rule[2] . '"); </script>';
		}
		return $js;
	} // end to_js

	/**
	 * to_sql
	 * Call the appropriate real function
	 */
	public function to_sql() {
		return call_user_func(
			array($this, $this->searchtype . "_to_sql"));
	} // end to_sql

	/**
	 * update
	 * This function updates the saved version with the current settings 
	 */
	public function update() {
		if (!$this->id) {
			return false;
		}

		$name = Dba::escape($this->name);
		$user = Dba::escape($GLOBALS['user']->id);
		$type = Dba::escape($this->type);
		$rules = serialize($this->rules);
		$logic_operator = $this->logic_operator;

		$sql = "UPDATE `search` SET `name`='$name', `type`='$type', `rules`='$rules', `logic_operator`='$logic_operator' WHERE `id`='" . Dba::escape($this->id) . "'";
		$db_results = Dba::write($sql);
		return $db_results;
	} // end update

	/**
	 * mangle_data
	 * Private convenience function.  Mangles the input according to a set 
	 * of predefined rules so that we don't have to include this logic in 
	 * foo_to_sql.
	 */
	private function mangle_data($data, $type, $operator) {
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
			return make_bool($input);
		}

		return $data;
	} // end mangle_data

	/**
	 * album_to_sql
	 * Handles the generation of the SQL for album searches.
	 */
	private function album_to_sql() {
		$sql_logic_operator = $this->logic_operator;

		$where = array();
		$table = array();
		$join = array();

		foreach ($this->rules as $rule) {
			$type = $this->name_to_basetype($rule[0]);
			foreach ($this->basetypes[$type] as $operator) {
				if ($operator['name'] == $rule[1]) {
					break;
				}
			}
			$input = $this->mangle_data($rule[2], $type, $operator);
			$sql_match_operator = $operator['sql'];

			switch ($rule[0]) {
				case 'title':
					$where[] = "`album`.`name` $sql_match_operator '$input'";
				break;
				case 'year':
					$where[] = "`album`.`year` $sql_match_operator '$input'";
				break;
				case 'rating':
					$where[] = " `realrating`.`rating` $sql_match_operator '$input'";
					$join['rating'] = true;
				break;
				case 'catalog':
					$where[] = "`song`.`catalog` $sql_match_operator '$input'";
					$join['song'] = true;
				break;
				case 'tag':
					$where[] = "`realtag`.`name` $sql_match_operator '$input'";
					$join['tag'] = true;
				break;
				default:
					// Nae laird!
				break;
			} // switch on ruletype
		} // foreach rule

		$where_sql = implode(" $sql_logic_operator ", $where);

		if ($join['tag']) {
			$table['tag'] = "LEFT JOIN (SELECT `object_id`, `name` FROM `tag` " .
				"LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` " .
				"WHERE `tag_map`.`object_type`='album') AS realtag " .
				"ON `album`.`id`=`realtag`.`object_id`";
		}
		if ($join['song']) {
			$table['song'] = "LEFT JOIN `song` ON `song`.`album`=`album`.`id`";
		}
		if ($join['rating']) {
			$userid = $GLOBALS['user']->id;
			$table['rating'] = "LEFT JOIN " .
				"(SELECT `object_id`, `rating` FROM `rating` " .
					"WHERE `object_type`='album' AND `user`='$userid' " .
				"UNION " .
				"SELECT `object_id`, FLOOR(AVG(`rating`)) AS 'rating' FROM `rating` " .
					"WHERE `object_type`='album' AND " .
					"`object_id` NOT IN (SELECT `object_id` FROM `rating` " .
						"WHERE `object_type`='album' AND `user`='$userid') " .
					"GROUP BY `object_id` " .
				") AS realrating ON `album`.`id` = `realrating`.`object_id`";
		}

		$table_sql = implode(' ', $table);

		return array(
			'base' => 'SELECT DISTINCT(`album`.`id`) FROM `album`',
			'join' => $join,
			'where' => $where,
			'where_sql' => $where_sql,
			'table' => $table,
			'table_sql' => $table_sql
		);
	} // album_to_sql

	/**
	 * artist_to_sql
	 * Handles the generation of the SQL for artist searches.
	 */
	private function artist_to_sql() {
		$sql_logic_operator = $this->logic_operator;
		$where = array();
		$table = array();
		$join = array();

		foreach ($this->rules as $rule) {
			$type = $this->name_to_basetype($rule[0]);
			foreach ($this->basetypes[$type] as $operator) {
				if ($operator['name'] == $rule[1]) {
					break;
				}
			}
			$input = $this->mangle_data($rule[2], $type, $operator);
			$sql_match_operator = $operator['sql'];

			switch ($rule[0]) {
				case 'name':
					$where[] = "`artist`.`name` $sql_match_operator '$input'";
				break;
				case 'tag':
					$where[] = " realtag`.`name` $sql_match_operator '$input'";
					$join['tag'] = true;
				break;
				default:
					// Nihil
				break;
			} // switch on ruletype
		} // foreach rule

		$where_sql = implode(" $sql_logic_operator ", $where);

		if ($join['tag']) {
			$table['tag'] = "LEFT JOIN (SELECT `object_id`, `name` FROM `tag` " .
				"LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` " .
				"WHERE `tag_map`.`object_type`='artist') AS realtag " .
				"ON `artist`.`id`=`realtag`.`object_id`";
		}

		$table_sql = implode(' ', $table);

		return array(
			'base' => 'SELECT DISTINCT(`artist`.`id`) FROM `artist`',
			'join' => $join,
			'where' => $where,
			'where_sql' => $where_sql,
			'table' => $table,
			'table_sql' => $table_sql
		);
	} // artist_to_sql

	/**
	 * song_to_sql
	 * Handles the generation of the SQL for song searches.
	 */
	private function song_to_sql() {
		$sql_logic_operator = $this->logic_operator;

		$where = array();
		$table = array();
		$join = array();

		foreach ($this->rules as $rule) {
			$type = $this->name_to_basetype($rule[0]);
			foreach ($this->basetypes[$type] as $operator) {
				if ($operator['name'] == $rule[1]) {
					break;
				}
			}
			$input = $this->mangle_data($rule[2], $type, $operator);
			$sql_match_operator = $operator['sql'];

			switch ($rule[0]) {
				case 'anywhere':
					$where[] = "(`artist`.`name` $sql_match_operator '$input' OR `album`.`name` $sql_match_operator '$input' OR `song_data`.`comment` $sql_match_operator '$input' OR `song`.`file` $sql_match_operator '$input' OR `song`.`title` $sql_match_operator '$input')";
					$join['album'] = true;
					$join['artist'] = true;
					$join['song_data'] = true;
				break;
				case 'tag':
					$where[] = "`realtag`.`name` $sql_match_operator '$input'";
					$join['tag'] = true;
				break;
				case 'title':
					$where[] = "`song`.`title` $sql_match_operator '$input'";
				break;
				case 'album':
					$where[] = "`album`.`name` $sql_match_operator '$input'";
					$join['album'] = true;
				break;
				case 'artist':
					$where[] = "`artist`.`name` $sql_match_operator '$input'";
					$join['artist'] = true;
				break;
				case 'time':
					$input = $input * 60;
					$where[] = "`song`.`time` $sql_match_operator '$input'";
				break;
				case 'file':
					$where[] = "`song`.`file` $sql_match_operator '$input'";
				break;
				case 'year':
					$where[] = "`song`.`year` $sql_match_operator '$input'";
				break;
				case 'comment':
					$where[] = "`song_data`.`comment` $sql_match_operator '$input'";
					$join['song_data'] = true;
				break;
				case 'played':
					$where[] = " `song`.`played` = '$input'";
				break;
				case 'bitrate':
					$input = $input * 1000;
					$where[] = "`song`.`bitrate` $sql_match_operator '$input'";
				break;
				case 'rating':
					$where[] = "`realrating`.`rating` $sql_match_operator '$input'";
					$join['rating'] = true;
				break;
				case 'catalog':
					$where[] = "`song`.`catalog` $sql_match_operator '$input'";
				break;
				case 'playlist':
					$join['playlist_data'] = true;
					$where[] = "`playlist_data`.`playlist` $sql_match_operator '$input'";
				break;
				case 'smartplaylist':
					$subsearch = new Search('song', $input);
					$subsql = $subsearch->to_sql();
					$where[] = "$sql_match_operator (" . $subsql['where_sql'] . ")";
					$join = array_merge($subsql['join'], $join);
				break;
				case 'added':
					$input = strtotime($input);
					$where[] = "`song`.`addition_time` $sql_match_operator $input";
				break;
				case 'updated':
					$input = strtotime($input);
					$where[] = "`song`.`update_time` $sql_match_operator $input";
				default:
					// NOSSINK!
				break;
			} // end switch on type
		} // end foreach over rules
		
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
			$table['tag'] = "LEFT JOIN (SELECT `object_id`, `name` FROM `tag` " . 
				"LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` " .
				"WHERE `tag_map`.`object_type`='song') AS realtag " .
				"ON `song`.`id`=`realtag`.`object_id`";
		}
		if ($join['rating']) {
			// We do a join on ratings from the table with a 
			// preference for our own and fall back to the FLOORed 
			// average of everyone's rating if it's a song we 
			// haven't rated.
			$userid = $GLOBALS['user']->id;
			$table['rating'] = "LEFT JOIN " .
				"(SELECT `object_id`, `rating` FROM `rating` " .
					"WHERE `object_type`='song' AND `user`='$userid' " .
				"UNION " .
				"SELECT `object_id`, FLOOR(AVG(`rating`)) AS 'rating' FROM `rating` " .
					"WHERE `object_type`='song' AND " .
					"`object_id` NOT IN (SELECT `object_id` FROM `rating` " .
						"WHERE `object_type`='song' AND `user`='$userid') " .
					"GROUP BY `object_id` " .
				") AS realrating ON `song`.`id`=`realrating`.`object_id`";
		}
		if ($join['playlist_data']) {
			$table['playlist_data'] = "LEFT JOIN `playlist_data` ON `song`.`id`=`playlist_data`.`object_id` AND `playlist_data`.`object_type`='song'";
		}

		$table_sql = implode(' ', $table);

		return array(
			'base' => 'SELECT DISTINCT(`song`.`id`) FROM `song`',
			'join' => $join,
			'where' => $where,
			'where_sql' => $where_sql,
			'table' => $table,
			'table_sql' => $table_sql
		);
	} // end song_to_sql

	/**
	 * video_to_sql
	 * Handles the generation of the SQL for video searches.
	 */
	private function video_to_sql() {
		$sql_logic_operator = $this->logic_operator;

		$where = array();
		

		foreach ($this->rules as $rule) {
			$type = $this->name_to_basetype($rule[0]);
			foreach ($this->basetypes[$type] as $operator) {
				if ($operator['name'] == $rule[1]) {
					break;
				}
			}
			$input = $this->mangle_data($rule[2], $type, $operator);
			$sql_match_operator = $operator['sql'];

			switch ($rule[0]) {
				case 'filename':
					$where[] = "`video`.`file` $sql_match_operator '$input'";
				break;
				default:
					// WE WILLNA BE FOOLED AGAIN!
			} // switch on ruletype
		} // foreach rule

		$where_sql = explode(" $sql_logic_operator ", $where);

		return array(
			'base' => 'SELECT DISTINCT(`video`.`id`) FROM `video`',
			'where' => $where,
			'where_sql' => $where_sql
		);
	} // end video_to_sql

} // end of Search class
?>
