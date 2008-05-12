<?php
/*

 Copyright (c) 2001 - 2008 Ampache.org
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
 * TagCloud Class
 */
 class TagCloud {
   public static function add_tag($objType, $id, $tagval)
   {
     if (!in_array($objType, array('artist','album','song')))
       return;
     if (!is_numeric($id))
       return;
     if (!preg_match('/^[A-Za-z_]+$/',$tagval))
       return;
     $uid = $GLOBALS['user']->id;
     // Check if tag object exists
     $sql = "SELECT tags.id from tags where name='$tagval'";
     $db_results = Dba::query($sql) ;
     $ar = Dba::fetch_assoc($db_results);
     if (!sizeof($ar)) {
       $sql = "INSERT into tags set name='$tagval'";
       $db_results = Dba::query($sql) ;
       $sql = "SELECT tags.id from tags where name='$tagval'";
       $db_results = Dba::query($sql);
       $ar = Dba::fetch_assoc($db_results);
     }
     $tid = $ar['id']; 
     $sql = "INSERT into tag_map set tag_id=$tid, user=$uid, 
     object_type = '$objType', object_id=$id;";
     $db_results = Dba::query($sql) ;//or die(Dba::error());
     $results['error'] = '<error>'.Dba::error().'</error>';
   }
   /**
    * show_tags
    * Return all tags maching any object of type $objtype in list $id
    */
   public static function get_tags($objType, $id) {
     if (!sizeof($id))
       return array();
     global $tag_cache;
     $tag_cache = array();
    $lid = '(' . implode(',',$id) . ')';
    $orsql = '';
    if ($objType == 'artist' || $objType == 'album')
      $orsql=" or (tag_map.object_id = song.id AND 
               tag_map.object_type='song' and song.$objType in $lid )";
    if ($objType == 'artist')
      $orsql .= "or (tag_map.object_id = album.id AND 
               tag_map.object_type='album' and $objType.id in $lid )";
    $sql = "SELECT DISTINCT tags.id, tags.name, tag_map.user, $objType.id as oid
	   FROM tags, tag_map, song, artist, album WHERE
	   tag_map.tag_id = tags.id AND
	   ( (tag_map.object_type='$objType' AND
	   $objType.id in $lid AND
	   tag_map.object_id = $objType.id) $orsql) AND
	   song.album = album.id AND
	   song.artist = artist.id;";

    $results = array();
    //var_dump($sql);
    $db_results = Dba::query($sql) or die(Dba::error());
    while ($r = Dba::fetch_assoc($db_results)) { 
      $uid = intval($r['oid']);
      $results[] = $r;
      if (!isset($tag_cache[$uid]))
	$tag_cache[$uid] = array();
      $tag_cache[$uid][] = $r;
    }
    return self::filter_with_prefs($results);
   }
         /**
	  * show_tagso
	  * Display all tags that apply to $objType objects maching 
	  $restrictObjType == $id
	  */
         public static function get_tagso($objType, $restrictObjType, $id) {
	   $sql = "SELECT DISTINCT tag_map.id, tags.name, tag_map.user
	   FROM tags, tag_map, song, artist, album WHERE
	   tag_map.id = tags.map_id AND 
	   tag_map.object_type='$objType' AND
	   $restrictObjType.id=$id AND
	   song.album = album.id AND
	   song.artist = artist.id;";
	   //echo $sql . '<br/>';
	   $results = array();
	   $db_results = Dba::query($sql) or die(Dba::error());
	   while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r;
		}
	   return $results;
	 }
	 //Use perfs to filter and add display properties
	 public static function filter_with_prefs($l)
	 {
	   $colors = array('#0000FF',
	     '#00FF00', '#FFFF00', '#00FFFF','#FF00FF','#FF0000');
	   $prefs = Config::get('tags_userlist');
	   $ulist = explode(' ', $prefs);
	   $req = '';
	   foreach($ulist as $i) {
	     $req .= "'" . Dba::escape($i) . "',";
	   }
	   rtrim($req, ',');
	   $sql = 'select id,username from user where ';
	   if ($prefs=='all')
	     $sql .= '1';
	   else
	     $sql .= 'username in ('.$req.')';
	   var_dump($sql);
	   $db_results = Dba::query($sql) or die(Dba::error());
	   $uids=array();
	   $usernames = array();
	   $p = 0;
	   while ($r = Dba::fetch_assoc($db_results)) { 
	     $usernames[$r['id']] = $r['username'];
	     $uids[$r['id']] = $colors[$p];
	     $p++;
	     if ($p == sizeof($colors))
	       $p = 0;
	   }
	   var_dump($uids);
	   $res = array();
	   foreach ($l as $i) {
	     if ($GLOBALS['user']->id == $i['user'])
	       $res[] = $i;
	     else if (isset($uids[$i['user']])) {
	       $i['color'] = $uids[$i['user']];
	       $i['username'] = $usernames[$i['user']];
	       $res[] = $i;
	     }	    
	   }
	   return $res;
	 }
   } // end of TagCloud class
?>
