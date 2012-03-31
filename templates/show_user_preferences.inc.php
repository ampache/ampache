<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show User Preferences
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

/**
 * This page has a few tabs, as such we need to figure out which tab we are on
 * and display the information accordingly
 */

?>
<?php /* HINT: Editing Username preferences */ show_box_top(sprintf(T_('Editing %s preferences'), $client->fullname),'box box_preferences'); ?>
<form method="post" name="preferences" action="<?php echo Config::get('web_path'); ?>/preferences.php?action=admin_update_preferences" enctype="multipart/form-data">
<table class="tabledata" cellspacing="0">
<colgroup>
	<col id="col_preference" />
	<col id="col_value" />
</colgroup>
<tr class="th-top">
	<th class="col_preference"><?php echo T_('Preference'); ?></th>
	<th class="col_value"><?php echo T_('Value'); ?></th>
</tr>
<?php foreach ($preferences as $pref) { ?>
        <tr class="<?php echo flip_class(); ?>">
                <td class="cel_preference"><?php echo T_($pref['description']); ?></td>
                <td class="cel_value">
                        <?php create_preference_input($pref['name'], $pref['value']); ?>
                </td>
        </tr>
<?php } // End foreach ($preferences['prefs'] as $pref) ?>
<tr>
	<td>
	<div class="formValidation">
	<input class="button" type="submit" value="<?php echo T_('Update Preferences'); ?>" />
	<?php echo Core::form_register('update_preference'); ?>
	<input type="hidden" name="user_id" value="<?php echo scrub_out($_REQUEST['user_id']); ?>" />
	</div>
	</td>
	<td>&nbsp;</td>
</tr>
</table>
</form>

<?php show_box_bottom(); ?>
