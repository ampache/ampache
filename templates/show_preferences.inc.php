<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This page has a few tabs, as such we need to figure out which tab we are on
 * and display the information accordingly
 */

?>
<?php /* HINT: Username */ UI::show_box_top(sprintf(T_('Editing %s preferences'), $fullname), 'box box_preferences'); ?>
<?php  if ($_REQUEST['tab'] != 'account' && $_REQUEST['tab'] != 'modules') {
    ?>

<form method="post" name="preferences" action="<?php echo AmpConfig::get('web_path'); ?>/preferences.php?action=update_preferences" enctype="multipart/form-data">
<?php show_preference_box($preferences[$_REQUEST['tab']]); ?>
<div class="formValidation">
    <input class="button" type="submit" value="<?php echo T_('Update Preferences'); ?>" />
    <?php echo Core::form_register('update_preference'); ?>
    <input type="hidden" name="tab" value="<?php echo scrub_out($_REQUEST['tab']); ?>" />
    <input type="hidden" name="method" value="<?php echo scrub_out($_REQUEST['action']); ?>" />
    <?php if (Access::check('interface', '100')) {
        ?>
        <input type="hidden" name="user_id" value="<?php echo scrub_out($_REQUEST['user_id']); ?>" />
    <?php
    } ?>
</div>
<?php
}  // end if not account
if ($_REQUEST['tab'] == 'account') {
    $client = $GLOBALS['user'];
    require AmpConfig::get('prefix') . UI::find_template('show_account.inc.php');
}
?>
</form>

<?php UI::show_box_bottom(); ?>
