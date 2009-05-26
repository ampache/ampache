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

	// constructed
	public $weight=0;  
	public $count=0; 
	public $owner=0;  

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

		$tag_id = self::tag_exists($name); 

		$tag = new Tag($tag_id); 

		return $tag; 
		
	} // construct_from_name

	/**
 	 * format
	 * This makes the tag presentable to the great humans that use this program, other life forms
	 * will just have to fend for themselves
	 */
	public function format($type=0,$object_id=0) { 

		if ($type AND !self::validate_type($type)) { return false; } 

		if ($type) { 
			$this->set_object($type,$object_id); 
		} 

		$size = 3 + ($this->weight-1) - ($this->count-1); 
		if (abs($size) > 4) { $size = 4; } 
		if (abs($size) < 1) { $size = 1; } 

		if ($this->owner == $GLOBALS['user']->id) { 
			$action = '?page=tag&action=remove_tag&type=' . scrub_out($type) . '&tag_id=' . intval($this->id) . '&object_id=' . intval($object_id); 
			$class = "hover-remove "; 
		} 
		else { 
			$action = '?page=tag&action=add_tag&type=' . scrub_out($type) . '&tag_id=' . intval($this->id) . '&object_id=' . intval($object_id); 
			$class = "hover-add "; 
		} 

		$class .= 'tag_size' . $size; 
		$this->f_class = $class; 

		$this->f_name = Ajax::text($action,$this->name,'modify_tag_' . $this->id . '_' . $object_id,'',$class); 

	} // format

	/**
 	 * set_object
	 * This assoicates the tag with a specified object, we try to get the data
	 * from the map cache, otherwise I guess we'll just have to look it up
	 */
	public function set_object($type,$object_id) { 

		if (parent::is_cached('tag_top_' . $type,$object_id)) { 
			$data = parent::get_from_cache('tag_top_' . $type,$object_id); 
		} 
		else { 
			$data = self::get_top_tags($type,$object_id); 
		} 

		// If nothing is found, then go ahead and return false
		if (!is_array($data) OR !count($data)) { return false; } 

		$this->weight = $data[$this->id]['count']; 

		if (in_array($GLOBALS['user']->id,$data[$this->id]['users'])) { 
			$this->owner = $GLOBALS['user']->id; 
		} 
		
		$this->count = count($data); 	
	
	} // set_object

	/**
	 * build_cache
	 * This takes an array of object ids and caches all of their information
	 * in a single query, cuts down on the connections
	 */
	public static function build_cache($ids) { 
		
		if (!is_array($ids) OR !count($ids)) { return false; }

		$idlist = '(' . implode(',',$ids) . ')'; 
	
		$sql = "SELECT * FROM `tag` WHERE `id` IN $idlist"; 
		$db_results = Dba::read($sql); 

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

		if (!is_array($ids) OR !count($ids)) { return false; }

                $type = self::validate_type($type);
                $idlist = '(' . implode(',',$ids) . ')'; 

                $sql = "SELECT `tag_map`.`id`,`tag_map`.`tag_id`,`tag_map`.`object_id`,`tag_map`.`user` FROM `tag_map` " .
                        "WHERE `tag_map`.`object_type`='$type' AND `tag_map`.`object_id` IN $idlist ";
		$db_results = Dba::query($sql); 

		$tags = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$tags[$row['object_id']][$row['tag_id']]['users'][] = $row['user']; 
			$tags[$row['object_id']][$row['tag_id']]['count']++; 
			$tag_map[$row['object_id']] = array('id'=>$row['id'],'tag_id'=>$row['tag_id'],'user'=>$row['user'],'object_type'=>$type,'object_id'=>$row['object_id']);
		}

		// Run through our origional ids as we want to cache NULL results
		foreach ($ids as $id) { 	
			parent::add_to_cache('tag_top_' . $type,$id,$tags[$id]); 
			parent::add_to_cache('tag_map_' . $type,$id,$tag_map[$id]); 
		} 

		return true; 

	} // build_map_cache

	/**
	 * add
	 * This is a wrapper function, it figures out what we need to add, be it a tag
	 * and map, or just the mapping 
	 */
	public static function add($type,$id,$value,$user=false) { 

		// Validate the tag type
		if (!self::validate_type($type)) { return false; } 

		if (!is_numeric($id)) { return false; } 

		$cleaned_value = self::clean_tag($value); 

		if (!strlen($cleaned_value)) { return false; } 

		$uid = ($user === false) ? intval($user) : intval($GLOBALS['user']->id);

		// Check and see if the tag exists, if not create it, we need the tag id from this
		if (!$tag_id = self::tag_exists($cleaned_value)) { 
			$tag_id = self::add_tag($cleaned_value); 
		} 

		if (!$tag_id) { 
			debug_event('Error','Error unable to create tag value:' . $cleaned_value . ' unknown error','1'); 
			return false; 
		} 

		// We've got the tag id, let's see if it's already got a map, if not then create the map and return the value
		if (!$map_id = self::tag_map_exists($type,$id,$tag_id,$user)) { 
			$map_id = self::add_tag_map($type,$id,$tag_id,$user); 
		}

		return $map_id; 

	} // add

	/**
	 * add_tag
	 * This function adds a new tag, for now we're going to limit the tagging a bit
	 */
	public static function add_tag($value) {
		
		// Clean it up and make it tagish
		$value = self::clean_tag($value); 

		if (!strlen($value)) { return false; } 
		
		$value = Dba::escape($value); 

		$sql = "REPLACE INTO `tag` SET `name`='$value'";
		$db_results = Dba::write($sql);
		$insert_id = Dba::insert_id(); 

		parent::add_to_cache('tag_name',$value,$insert_id); 

		return $insert_id; 

	} // add_tag

	/**
	 * add_tag_map
	 * This adds a specific tag to the map for specified object
	 */
	public static function add_tag_map($type,$object_id,$tag_id,$user='') { 
		
		$uid = ($user == '') ? intval($GLOBALS['user']->id) : intval($user); 
		$tag_id = intval($tag_id); 
		if (!self::validate_type($type)) { return false; } 
		$id = intval($object_id); 
		
		if (!$tag_id || !$id) { return false; } 
	
		$sql = "INSERT INTO `tag_map` (`tag_id`,`user`,`object_type`,`object_id`) " .
			"VALUES ('$tag_id','$uid','$type','$id')";
		$db_results = Dba::write($sql);
		$insert_id = Dba::insert_id(); 

		parent::add_to_cache('tag_map_' . $type,$insert_id,array('tag_id'=>$tag_id,'user'=>$uid,'object_type'=>$type,'object_id'=>$id)); 

		return $insert_id;  

	} // add_tag_map

	/**
	 * tag_exists
	 * This checks to see if a tag exists, this has nothing to do with objects or maps 
	 */
	public static function tag_exists($value) { 

		if (parent::is_cached('tag_name',$value)) { 
			return parent::get_from_cache('tag_name',$value); 
		} 

		$value = Dba::escape($value); 
		$sql = "SELECT * FROM `tag` WHERE `name`='$value'"; 
		$db_results = Dba::read($sql); 

		$results = Dba::fetch_assoc($db_results); 

		parent::add_to_cache('tag_name',$results['name'],$results['id']); 

		return $results['id']; 

	} // tag_exists

	/**
	 * tag_map_exists
	 * This looks to see if the current mapping of the current object of the current tag of the current
	 * user exists, lots of currents... taste good in scones. 
	 */
	public static function tag_map_exists($type,$object_id,$tag_id,$user) { 

		if (!self::validate_type($type)) { return false; } 

		if (parent::is_cached('tag_map_' . $type,$object_id)) { 
			$data = parent::get_from_cache('tag_map_' . $type,$object_id);
			return $data['id']; 
		} 

		$object_id = Dba::escape($object_id); 
		$tag_id = Dba::escape($tag_id); 
		$user = Dba::escape($user); 
		$type = Dba::escape($type); 

		$sql = "SELECT * FROM `tag_map` WHERE `tag_id`='$tag_id' AND `user`='$user' AND `object_id`='$object_id' AND `object_type`='$type'"; 
		$db_results = Dba::read($sql); 
	
		$results = Dba::fetch_assoc($db_results); 

		parent::add_to_cache('tag_map_' . $type,$results['id'],$results); 

		return $results['id']; 

	} // tag_map_exists

	/**
	 * get_top_tags
	 * This gets the top tags for the specified object using limit
	 */
	public static function get_top_tags($type,$object_id,$limit='10') { 

		if (!self::validate_type($type)) { return false; } 

		if (parent::is_cached('tag_top_' . $type,$object_id)) { 
			return parent::get_from_cache('tag_top_' . $type,$object_id); 
		} 

		$object_id = intval($object_id); 
		$limit = intval($limit); 

		$sql = "SELECT `tag_map`.`tag_id`,`tag_map`.`user` FROM `tag_map` " . 
			"WHERE `tag_map`.`object_type`='$type' AND `tag_map`.`object_id`='$object_id' " . 
			"LIMIT $limit"; 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[$row['tag_id']]['users'][] = $row['user']; 
			$results[$row['tag_id']]['count']++;
		} 

		parent::add_to_cache('tag_top_' . $type,$object_id,$results); 

		return $results; 	

	} // get_top_tags

	/**
	 * get_object_tags
	 * Display all tags that apply to maching target type of the specified id
	 * UNUSED
	 */
	public static function get_object_tags($type, $id) {

		if (!self::validate_type($type)) { return array(); } 		
		
		$id = Dba::escape($id); 

		$sql = "SELECT `tag_map`.`id`, `tag`.`name`, `tag_map`.`user` FROM `tag` " . 
			"LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` " . 
			"WHERE `tag_map`.`object_type`='$type' AND `tag_map`.`object_id`='$id'"; 
	   
		$results = array();
		$db_results = Dba::read($sql);
		
		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row;
		}
		
		return $results;

	} // get_object_tags

	/**
	 * get_tag_objects
	 * This gets the objects from a specified tag and returns an array of object ids, nothing more
	 */
	public static function get_tag_objects($type,$tag_id) { 

		if (!self::validate_type($type)) { return array(); } 

		$tag_id = Dba::escape($tag_id); 

		$sql = "SELECT DISTINCT `tag_map`.`object_id` FROM `tag_map` " . 
			"WHERE `tag_map`.`tag_id`='$tag_id' AND `tag_map`.`object_type`='$type'"; 
		$db_results = Dba::read($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['object_id']; 
		} 

		return $results; 


	} // get_tag_objects

	/**
 	 * get_tags
	 * This is a non-object non type depedent function that just returns tags
	 * we've got, it can take filters (this is used by the tag cloud)
	 */
	public static function get_tags($limit,$filters=array()) { 

		$sql = "SELECT `tag_map`.`tag_id`,COUNT(`tag_map`.`object_id`) AS `count` " . 
			"FROM `tag_map` " .
			"LEFT JOIN `tag` ON `tag`.`id`=`tag_map`.`tag_id` " . 
			"GROUP BY `tag`.`name` ORDER BY `count` DESC " . 
			"LIMIT $limit";
		$db_results = Dba::read($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			if ($row['count'] > $top) { $top = $row['count']; } 
			$results[$row['tag_id']] = array('id'=>$row['tag_id'],'count'=>$row['count']); 
			$count+= $row['count']; 
		} 

		// Do something with this
		$min = $row['count']; 

		return $results; 

	} // get_tags

	/**
	 * get_display
	 * This returns a human formated version of the tags that we are given
	 * it also takes a type so that it knows how to return it, this is used
	 * by the formating functions of the different objects
	 */
	public static function get_display($tags,$element_id,$type='song') { 

		if (!is_array($tags)) { return ''; } 

		$results = ''; 

		// Itterate through the tags, format them according to type and element id
		foreach ($tags as $tag_id=>$value) { 
			$tag = new Tag($tag_id); 
			$tag->format($type,$element_id); 
			$results .= $tag->f_name . ', '; 
		} 

		$results = rtrim($results,', '); 

		return $results; 

	} // get_display

	/**
	 * count
	 * This returns the count for the all objects assoicated with this tag
	 * If a type is specific only counts for said type are returned
	 */
	public function count($type='') { 

		if ($type) { 
			$filter_sql = " AND `object_type`='" . Dba::escape($type) . "'"; 
		} 

		$results = array(); 

		$sql = "SELECT COUNT(`id`) AS `count`,`object_type` FROM `tag_map` WHERE `tag_id`='" . Dba::escape($this->id) . "'" .  $filter_sql . " GROUP BY `object_type`"; 
		$db_results = Dba::read($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[$row['object_type']] = $row['count'];
		} 

		return $results;

	} // count

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
	 * remove_map
	 * This will only remove tag maps for the current user
	 */
	public function remove_map($type,$object_id) { 

		if (!self::validate_type($type)) { return false; } 

		$type = Dba::escape($type); 
		$tag_id = Dba::escape($this->id); 
		$object_id = Dba::escape($object_id); 	
		$user_id = Dba::escape($GLOBALS['user']->id); 

		$sql = "DELETE FROM `tag_map` WHERE `tag_id`='$tag_id' AND `object_type`='$type' AND `object_id`='$object_id' AND `user`='$user_id'"; 
		$db_results = Dba::write($sql); 

		return true; 

	} // remove_map

	/**
	 * validate_type
	 * This validates the type of the object the user wants to tag, we limit this to types
	 * we currently support
	 */
	public static function validate_type($type) { 

		$valid_array = array('song','artist','album','video','playlist','live_stream'); 
		
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
