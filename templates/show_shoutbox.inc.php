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
?>
<?php show_box_top(_('Shoutbox')); ?>
<div id="shoutbox">
<?php 
  foreach ($shouts as $shout_id) { 
	$shout = new shoutBox($shout_id); 
	$object = shoutBox::get_object($shout->object_type,$shout->object_id); 
	$object->format(); 
	$client = new User($shout->user); 
	$client->format(); 
?>
<div class="shout <?php echo flip_class(); ?>">
	<?php echo $shout->get_image(); ?>
	<?php echo Ajax::button('?action=basket&type=' . $shout->object_type .'&id=' . $shout->object_id,'add',_('Add'),'add_' . $shout->object_type . '_' . $shout->object_id); ?>
	<?php echo $object->f_link; ?>
	<span class="information"><?php echo $client->f_link; ?> <?php echo date("d/m H:i",$shout->date); ?></span>
	<span class="shouttext"><?php echo scrub_out($shout->text); ?></span>
</div>
<?php } ?>
</div>
<?php show_box_bottom(); ?>
