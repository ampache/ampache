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

	/**
	 * add_tag
	 * This function adds a new tag, for now we're going to limit the tagging a bit
	 */
	public static function add_tag($type, $id, $tagval) {
		
		if (!self::validate_type($type)) { 
			return false; 
		} 
		if (!is_numeric($id)) { 
			return false; 
		} 
		if (!preg_match('/^[A-Za-z_]+$/',$tagval)) { 
			return false; 
		} 
		
		$uid = intval($GLOBALS['user']->id); 
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

		// Now make sure this isn't a duplicate
		$sql = "SELECT * FROM `tag_map " . 
				"WHERE `tag_id`='$insert_id' AND `user`='$uid' AND `object_type`='$type' AND `object_id`='$id'"; 
		$db_results = Dba::query($sql); 

		$row = Dba::fetch_assoc($db_results); 

		// Only insert it if the current tag for this user doesn't exist
		if (!count($row)) { 
			$sql = "INSERT INTO `tag_map` (`tag_id`,`user`,`object_type`,`object_id`) " . 
				"VALUES ('$tid','$uid','$type','$id')";
			$db_results = Dba::query($sql);	
		} 

		return true; 

	} // add_tag

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
		
		if (in_array($type,$valid_array)) { return true; } 

		return false; 

	} // validate_type

} // end of TagCloud class
?>
