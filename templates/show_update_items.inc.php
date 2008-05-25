<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

show_box_top(_('Starting Update from Tags')); 
Catalog::update_single_item($type,$object_id); 
?>
<br />
<strong><?php echo _('Update from Tags Complete'); ?></strong>&nbsp;&nbsp;
<a class="button" href="<?php echo $target_url; ?>"><?php echo _('Continue'); ?></a>
<?php show_box_bottom(); ?>
