<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
 */

/**
 * This page has a few tabs, as such we need to figure out which tab we are on
 * and display the information accordingly
 */

?>
<?php /* HINT: Username */ UI::show_box_top(sprintf(T_('Editing %s preferences'), $fullname),'box box_preferences'); ?>
<?php  if ($_REQUEST['tab'] != 'account' && $_REQUEST['tab'] != 'modules') { ?>

<form method="post" name="preferences" action="<?php echo AmpConfig::get('web_path'); ?>/preferences.php?action=update_preferences" enctype="multipart/form-data">
<?php show_preference_box($preferences[$_REQUEST['tab']]);  ?>
<div class="formValidation">
    <input class="button" type="submit" value="<?php echo T_('Update Preferences'); ?>" />
    <?php echo Core::form_register('update_preference'); ?>
    <input type="hidden" name="tab" value="<?php echo scrub_out($_REQUEST['tab']); ?>" />
    <input type="hidden" name="method" value="<?php echo scrub_out($_REQUEST['action']); ?>" />
    <?php if (Access::check('interface','100')) { ?>
        <input type="hidden" name="user_id" value="<?php echo scrub_out($_REQUEST['user_id']); ?>" />
    <?php } ?>
</div>
<?php
}  // end if not account
if ($_REQUEST['tab'] == 'account') {
        $client = $GLOBALS['user'];
        require AmpConfig::get('prefix') . '/templates/show_account.inc.php';
    }
?>
</form>

<?php UI::show_box_bottom(); ?>
