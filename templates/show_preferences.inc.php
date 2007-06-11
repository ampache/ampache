<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved

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
 * This page has a few tabs, as such we need to figure out which tab we are on 
 * and display the information accordingly 
 */

?>
<?php show_box_top(_('Editing') . ' ' . $fullname . ' ' . _('preferences')); ?>
<form method="post" name="preferences" action="<?php echo Config::get('web_path'); ?>/preferences.php?action=update_preferences" enctype="multipart/form-data">
<?php 
if ($current_tab != 'account' && $current_tab != 'modules') { 
	show_preference_box($preferences[$_REQUEST['tab']]); 

?>
	<input class="button" type="submit" value="<?php echo _('Update Preferences'); ?>" />
	<input type="hidden" name="action" value="update_preferences" />
	<input type="hidden" name="tab" value="<?php echo scrub_out($current_tab); ?>" />
	<input type="hidden" name="method" value="<?php echo scrub_out($_REQUEST['action']); ?>" />
	<input class="button" type="submit" name="action" value="<?php echo _("Cancel"); ?>" />
<?php
	} 
if ($current_tab == 'modules') {
	        require (conf('prefix') .  '/templates/show_modules.inc.php');
}
if ($current_tab == 'account') { 
		$this_user = new User($user_id);
		require (conf('prefix') . '/templates/show_user.inc.php');
	}
?>
</form>
</div>
<?php show_box_bottom(); ?>
