<?php
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
 * Tag Class
 * This class hnadles all of the tag relation operations
 */
class Tag extends database_object {

	public $id; 
	public $name; 

	/**
	 * constructor
	 * This takes a tag id and returns all of the relevent information
	 */
	public function __construct($id) { 

		if (!$id) { return false; } 

		$info = $this->get_info($id); 

		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} // end foreach

	} // constructor

	/**
	 * construct_from_name
	 * This attempts to construct the tag from a name, rather then the ID
	 */
	public static function construct_from_name($name) { 

		$name = Dba::escape($name); 

		$sql = "SELECT * FROM `tag` WHERE `name`='$name'"; 
		$db_results = Dba::query($sql); 

		$row = Dba::fetch_assoc($db_results); 

		if (!$row['id']) { return false; } 

		parent::add_to_cache('tag',$row['id'],$row); 

		$tag = new Tag(0); 
		foreach ($row as $key=>$value) { 
			$tag->$key = $value; 
		} 

		return $tag; 
		
	} // construct_from_name

	/**
	 * get_info	
 	 * This takes the id and returns an array of information, checks the cache
	 * to see what's up
	 */
	private function get_info($id) { 

		$id = intval($id); 

		if (parent::is_cached('tag',$id)) { 
			return parent::get_from_cache('tag',$id);
		} 

		$sql = "SELECT * FROM `tag` WHERE `id`='$id'"; 
		$db_results = Dba::query($sql); 

		$results = Dba::fetch_assoc($db_results); 

		parent::add_to_cache('tag',$id,$results); 
		
		return $results; 

	} // get_info

	/**
	 * build_cache
	 * This takes an array of object ids and caches all of their information
	 * in a single query, cuts down on the connections
	 */
	public static function build_cache($ids) { 

		$idlist = '(' . implode(',',$ids) . ')'; 
		
		$sql = "SELECT * FROM `tag` WHERE `id` IN $idlist"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			parent::add_to_cache('tag',$row['id'],$row); 
		} 

		return true; 

	} // build_cache

	/**
	 * build_map_cache
	 * This builds a cache of the mappings for the specified object, no limit is given
	 */
	public static function build_map_cache($type,$ids) { 

                $type = self::validate_type($type);
                $idlist = '(' . implode(',',$ids) . ')'; 

                $sql = "SELECT COUNT(`tag_map`.`id`) AS `count`,`tag`.`id`,`tag_map`.`object_id` FROM `tag_map` " .
                        "INNER JOIN `tag` ON `tag`.`id`=`tag_map`.`tag_id` " .
                        "WHERE `tag_map`.`object_type`='$type' AND `tag_map`.`object_id` IN $idlist " .
                        "GROUP BY `tag_map`.`object_id` ORDER BY `count` DESC";
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$tags[$row['object_id']][] = $row; 
		} 

	
		foreach ($tags as $id=>$entry) { 	
			parent::add_to_cache('tag_map_' . $type,$id,$entry); 
		} 

		return true; 

	} // build_map_cache

	/**
	 * has_object
	 * This checks to see if the current tag element has the specified object
	 * of the specified type
	 */
	public function has_object($object_type,$object_id) { 

		$object_type = self::validate_type($object_type); 
		$object_id = intval($object_id); 
		$tag_id = intval($this->id); 
		
		$sql = "SELECT * FROM `tag_map` WHERE `object_type`='$object_type' AND `object_id`='$object_id' " . 
			" AND `tag_id`='$tag_id'"; 
		$db_results = Dba::query($sql); 

		return Dba::num_rows($db_results); 

	} // has_object

	/**
	 * add_tag
	 * This function adds a new tag, for now we're going to limit the tagging a bit
	 */
	public static function add_tag($type, $id, $tagval,$user='') {
		
		if (!self::validate_type($type)) { 
			return false; 
		} 
		if (!is_numeric($id)) { 
			return false; 
		} 

		// Clean it up and make it tagish
		$tagval = self::clean_tag($tagval); 

		if (!strlen($tagval)) { return false; } 
		
		$uid = ($user == '') ? intval($user) : intval($GLOBALS['user']->id);
		$tagval = Dba::escape($tagval); 
		$type = Dba::escape($type); 
		$id = intval($id);

		// Check if tag object exists
		$sql = "SELECT `tag`.`id` FROM `tag` WHERE `name`='$tagval'";
		$db_results = Dba::query($sql) ;
		$row = Dba::fetch_assoc($db_results);
		$insert_id = $row['id']; 

		// If the tag doesn't exist create it. 
		if (!count($row)) {
			$sql = "INSERT INTO `tag` SET `name`='$tagval'";
			$db_results = Dba::query($sql) ;
			$insert_id = Dba::insert_id(); 
		}

		self::add_tag_map($insert_id,$type,$id); 

		return $insert_id; 

	} // add_tag

	/**
	 * add_tag_map
	 * This adds a specific tag to the map for specified object
	 */
	public static function add_tag_map($tag_id,$object_type,$object_id,$user='') { 
		
		$uid = ($user == '') ? intval($GLOBALS['user']->id) : intval($user); 
		$tag_id = intval($tag_id); 
		$type = self::validate_type($object_type);  
		$id = intval($object_id); 

                // Now make sure this isn't a duplicate
                $sql = "SELECT * FROM `tag_map " .
                                "WHERE `tag_id`='$insert_id' AND `user`='$uid' AND `object_type`='$type' AND `object_id`='$id'";
                $db_results = Dba::query($sql);

                $row = Dba::fetch_assoc($db_results);

                // Only insert it if the current tag for this user doesn't exist
                if (!count($row)) {
                        $sql = "INSERT INTO `tag_map` (`tag_id`,`user`,`object_type`,`object_id`) " .
                                "VALUES ('$tag_id','$uid','$type','$id')";
                        $db_results = Dba::query($sql);
			$insert_id = Dba::insert_id(); 
                }
		else { 
			$insert_id = $row['id']; 
		} 

		return $insert_id;  

	} // add_tag_map

	/**
	 * get_many_tags
	 * This builds a cache of all of the tags contained by the specified object ids
	 * of the specified type
	 */
	public static function get_many_tags($type, $object_ids) {

		// If they pass us nothing, they get nothing
		if (!count($object_ids)) { return array(); } 
		if (!self::validate_type($type)) { return array(); } 

    		$lid = '(' . implode(',',$id) . ')';
		$orsql = '';
		
		if ($objType == 'artist' || $objType == 'album')
			$orsql=" or (tag_map.object_id = song.id AND tag_map.object_type='song' and song.$objType in $lid )";
		if ($objType == 'artist')
			$orsql .= "or (tag_map.object_id = album.id AND tag_map.object_type='album' and $objType.id in $lid )";
		$sql = "SELECT DISTINCT tag.id, tag.name, tag_map.user, $objType.id as oid FROM tag, tag_map, song, artist, album WHERE " . 
			"tag_map.tag_id = tag.id AND ( (tag_map.object_type='$objType' AND $objType.id in $lid AND tag_map.object_id = $objType.id) $orsql) " . 
			"AND song.album = album.id AND song.artist = artist.id;";
return array();
		$results = array();
    
		$db_results = Dba::query($sql) or die(Dba::error());
	
		while ($r = Dba::fetch_assoc($db_results)) { 
			$uid = intval($r['oid']);
			$results[] = $r;
		} 

		//return self::filter_with_prefs($results);
		return $results; 

	} // get_man_tags

	/**
	 * get_top_tags
	 * This gets the top tags for the specified object using limit
	 */
	public static function get_top_tags($type,$object_id,$limit='2') { 

		$type = self::validate_type($type); 

		if (parent::is_cached('tag_map_' . $type,$object_id)) { 
			return parent::get_from_cache('tag_map_' . $type,$object_id); 
		} 

		$object_id = intval($object_id); 
		$limit = intval($limit); 

		$sql = "SELECT COUNT(`tag_map`.`id`) AS `count`,`tag`.`id` FROM `tag_map` " . 
			"INNER JOIN `tag` ON `tag`.`id`=`tag_map`.`tag_id` " . 
			"WHERE `tag_map`.`object_type`='$type' AND `tag_map`.`object_id`='$object_id' " . 
			"GROUP BY `tag_map`.`object_id` ORDER BY `count` DESC LIMIT $limit"; 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 

		return $results; 	

	} // get_top_tags

	/**
	 * get_object_tags
	 * Display all tags that apply to maching target type of the specified id
	 */
	public static function get_object_tags($type, $id) {

		if (!self::validate_type($type)) { return array(); } 		

		$sql = "SELECT DISTINCT `tag_map`.`id`, `tag`.`name`, `tag_map`.`user` FROM `tag` " . 
			"LEFT JOIN `tag_map` ON `tag_map`.`id`=`tag`.`map_id` " . 
			"LEFT JOIN `$type` ON `$type`.`id`=`tag_map`.`object_id` " . 
			"WHERE `tag_map`.`object_type`='$type'"; 
	   
		$results = array();
		$db_results = Dba::query($sql);
		
		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row;
		}
		
		return $results;

	 } // get_object_tags

	/**
 	 * filter_with_prefs
	 * This filters the tags based on the users preference
	 */
	public static function filter_with_prefs($l) {

	   $colors = array('#0000FF',
	     '#00FF00', '#FFFF00', '#00FFFF','#FF00FF','#FF0000');
		$prefs = 'tag company'; 
//		$prefs = Config::get('tags_userlist');

		$ulist = explode(' ', $prefs);
		$req = '';

		foreach($ulist as $i) {
			$req .= "'" . Dba::escape($i) . "',";
		}
		$req = rtrim($req, ',');

		$sql = 'SELECT `id`,`username` FROM `user` WHERE ';
		
		if ($prefs=='all') { 
			$sql .= '1';
		} 
		else { 
			$sql .= 'username in ('.$req.')';
		}

		$db_results = Dba::query($sql); 

		$uids=array();
		$usernames = array();
		$p = 0;
		while ($r = Dba::fetch_assoc($db_results)) { 
			$usernames[$r['id']] = $r['username'];
			$uids[$r['id']] = $colors[$p];
			$p++;
			if ($p == sizeof($colors)) { 
				$p = 0;
			} 
		}
	   
		$res = array();
		
		foreach ($l as $i) {
			if ($GLOBALS['user']->id == $i['user']) { 
				$res[] = $i;
			} 
			elseif (isset($uids[$i['user']])) {
				$i['color'] = $uids[$i['user']];
				$i['username'] = $usernames[$i['user']];
				$res[] = $i;
			}	    
		}
		
		return $res;

	} // filter_with_prefs

	/**
	 * validate_type
	 * This validates the type of the object the user wants to tag, we limit this to types
	 * we currently support
	 */
	public static function validate_type($type) { 

		$valid_array = array('song','artist','album'); 
		
		if (in_array($type,$valid_array)) { return $type; } 

		return false; 

	} // validate_type

	/**
	 * clean_tag
	 * This takes a string and makes it Tagish
	 */
	public static function clean_tag($value) { 

		$tag = preg_replace("/[^\w\_\-\s\&]/","",$value); 

		return $tag; 

	} // clean_tag

} // end of Tag class
?>
