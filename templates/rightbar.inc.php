<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

?>
<ul id="rb_action">
	<li>
	<?php echo Ajax::button('?page=stream&action=basket','all',_('Play'),'rightbar_play'); ?>
	</li>
	<li id="pl_add">
		<?php echo get_user_icon('playlist_add',_('Add to Playlist')); ?>
		<ul id="pl_action_additems">
		  <li>
		    <?php echo Ajax::text('?page=playlist&action=create',_('Add to New Playlist'),'rb_create_playlist'); ?>
		  </li>
		<?php 
			$playlists = Playlist::get_users($GLOBALS['user']->id); 
			foreach ($playlists as $playlist_id) { 
				$playlist = new Playlist($playlist_id);
				$playlist->format(); 
		?>
		  <li>
		    <?php echo Ajax::text('?page=playlist&action=append',$playlist->f_name,'rb_append_playlist_' . $playlist->id); ?>
		  </li>
		<?php } ?>
		</ul>
	</li>
<?php if (Access::check_function('batch_download')) { ?>
	<li>
	<a href="<?php echo Config::get('web_path'); ?>/batch.php?action=tmp_playlist&amp;id=<?php echo $GLOBALS['user']->playlist->id; ?>">
	        <?php echo get_user_icon('batch_download',_('Batch Download')); ?>
        </a>
	</li>
<?php } ?>
	<li>
	<?php echo Ajax::button('?action=basket&type=clear_all','delete',_('Clear Playlist'),'rb_clear_playlist'); ?>
	</li>
	<li id="rb_add">
	  <?php echo get_user_icon('add',_('Add Dynamic Items')); ?>
	  <ul id="rb_action_additems">
	   <li>
	    <?php echo Ajax::text('?action=basket&type=dynamic&random_type=default',_('Pure Random'),'rb_add_pure_random'); ?>
	   </li>
	   <li>
	    <?php echo Ajax::text('?action=basket&type=dynamic&random_type=artist',_('Related Artist'),'rb_add_related_artist'); ?>
	   </li>
	   <li>
	    <?php echo Ajax::text('?action=basket&type=dynamic&random_type=album',_('Related Album'),'rb_add_related_album'); ?>
	   </li>
	   <li>
	    <?php echo Ajax::text('?action=basket&type=dynamic&random_type=genre',_('Related Genre'),'rb_add_related_genre'); ?>
	   </li>
	  </ul>
	</li>
</ul>
<ul id="rb_current_playlist">
<?php 
	//FIXME :: this feels kludgy
	$objects = $GLOBALS['user']->playlist->get_items(); 
	foreach ($objects as $uid=>$object_data) { 
		if ($object_data['1'] == 'radio' || $object_data['1'] == 'song') { 
			$object = new $object_data['1']($object_data['0']); 
			$object->format(); 
		} 
		else { 
			$object = new Random(); 
			$object->f_link = Random::get_type_name($object_data['1']); 
		} 
?>
<li class="<?php echo flip_class(); ?>" >
  <?php echo $object->f_link; ?>
	<?php echo Ajax::button('?action=current_playlist&type=delete&id=' . $uid,'delete',_('Delete'),'rightbar_delete_' . $uid,'','delitem'); ?>
</li>
<?php } if (!count($objects)) { ?>
	<li class="error"><?php echo _('Not Enough Data'); ?></li>
<?php } ?>
</ul>


<?php 

// We do a little magic here to force a iframe reload depending on preference
// We do this last because we want it to load, and we want to know if there is anything
// to even pass
if ($GLOBALS['user']->prefs['playlist_method'] != 'default' AND AJAX_INCLUDE == '1' AND count($objects)) { 
	// Set the target
	$_SESSION['iframe']['target'] = Config::get('web_path') . '/stream.php?action=basket'; 
	echo "<script type=\"text/javascript\">"; 
	echo "reload_util();"; 
	echo "</script>"; 
} 
?>
