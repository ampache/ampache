<?php
/*

 Copyright (c) Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.
        
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
        
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/ 
$web_path = Config::get('web_path'); 
?>
<?php show_box_top('', 'info-box'); 
/* make a map id->name
$tagbyid = array();
foreach ($tagcloudList as $f)
  $tagbyid[$f['id']] = $f;
$ar = $_GET;
unset($ar['tag']);
$base = rebuild_query($ar);
$currentTags = array_fill_keys($_SESSION['browse']['filter']['tag'], '1');
$filter=0;
foreach ($_SESSION['browse']['filter']['tag'] as $t) {
    if (!$filter) {
      $filter = 1;
      echo _('Filters(remove): ');
    }
    $ctags = $currentTags;
    unset($ctags[$t]);
    $stags = implode(',', array_keys($ctags));
    $col = 'black';
    $alt = '';
    if (isset($tagbyid[$t]['color'])) {
      $col = $tagbyid[$t]['color'];
      $alt = ' title="owner: '. $tagbyid[$t]['username'].'" ';
    }
    echo '<a style="color:'. $col.'"'.$alt .' href="' .$base 
       . 'tag='.$stags.'">'.$tagbyid[$t]['name'].'</a> ';
}
echo '<br/>';
$filter = 0;
foreach ($tagcloudList as $f) {
   $n = $f['name'];
   $id = $f['id'];
   if (!$currentTags[$id]) {
     if (!$filter) {
       $filter = 1;
       echo _('Matching tags: ');
     }
     $ctags = $currentTags;
     $ctags[$id] = 1;
     $stags = implode(',', array_keys($ctags));
     $col = 'black';
     $alt = '';
     if (isset($f['color'])) {
       $col = $f['color'];
       $alt = ' title="owner: '. $f['username'].'" ';
     }
     echo '<a style="color:'.$col.'"'.$alt.' href="' .$base
     . 'tag='.$stags.'">'.$n.'</a> ';
   }
}
*/
?>
<?php show_box_bottom(); ?>
