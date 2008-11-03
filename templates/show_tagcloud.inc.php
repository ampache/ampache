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
<?php Ajax::start_container('tag_filter'); ?>
<?php foreach ($object_ids as $data) { 
	$tag = new Tag($data['id']); 
	$tag->format(); 
?>
<span id="click_<?php echo intval($tag->id); ?>" class="<?php echo $tag->f_class; ?>"><?php echo $tag->name; ?></span> 
<?php echo Ajax::observe('click_' . intval($tag->id),'click',Ajax::action('?page=browse&action=toggle_tag&tag_id=' . intval($tag->id),'')); ?>
<?php } ?>
<?php Ajax::end_container(); ?>
