<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

$web_path     = Config::get('web_path');
$catalogs     = Catalog::get_catalogs();

?>
<?php UI::show_box_top(T_('Catalogs')); ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_name" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
    <th class="cel_name"><?php echo T_('Name'); ?></th>
    <th class="cel_action"><?php echo T_('Action'); ?></th>
</tr>
<?php foreach ($catalogs as $catalog) { ?>
<tr class="<?php echo UI::flip_class(); ?>">
    <td class="cel_name">
        <a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_customize_catalog&amp;catalog_id=<?php echo $catalog->id; ?>">
        <?php echo scrub_out($catalog->name); ?></a>
        &nbsp;&nbsp;(<?php echo scrub_out($catalog->path); ?>)
    </td>
    <td class="cel_action">
        <a href="<?php echo $web_path; ?>/admin/catalog.php?action=add_to_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>">
        <?php echo T_('Add'); ?></a>&nbsp;|&nbsp;
        <a href="<?php echo $web_path; ?>/admin/catalog.php?action=update_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>">
        <?php echo T_('Verify'); ?></a>&nbsp;|&nbsp;
        <a href="<?php echo $web_path; ?>/admin/catalog.php?action=clean_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>">
        <?php echo T_('Clean'); ?></a>&nbsp;|&nbsp;
        <a href="<?php echo $web_path; ?>/admin/catalog.php?action=full_service&amp;catalogs[]=<?php echo $catalog->id; ?>">
        <?php echo T_('All'); ?></a>&nbsp;|&nbsp;
        <a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_delete_catalog&amp;catalog_id=<?php echo $catalog->id; ?>">
        <?php echo T_('Delete'); ?></a>
    </td>
</tr>
<!--
<tr class="<?php echo UI::flip_class(); ?>">
    <td colspan="2">
        <?php echo T_('Fast'); ?><input type="checkbox" name="fast" value="1" />
        <?php echo T_('Gather Art'); ?><input type="checkbox" name="gather_art" value="1" />
    </td>
</tr>
-->
<?php } // end foreach ?>
<?php if (!count($catalogs)) { ?>
<tr>
    <td colspan="2">
    <?php echo T_('No Catalogs Found'); ?>
    </td>
</tr>
<?php } // end if no catalogs ?>
<tr class="th-bottom">
    <th class="cel_name"><?php echo T_('Name'); ?></th>
    <th class="cel_action"><?php echo T_('Action'); ?></th>
</tr>
</table>
<div>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=clean_all_catalogs"><?php echo T_('Clean All'); ?></a>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=update_all_catalogs"><?php echo T_('Verify All'); ?></a>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=add_to_all_catalogs"><?php echo T_('Add to All'); ?></a>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=full_service"><?php echo T_('Update All'); ?></a><hr noshade="noshade" size="3" />
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=show_add_catalog"><?php echo T_('Add a Catalog'); ?></a>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=show_clear_stats"><?php echo T_('Clear Catalog Stats'); ?></a>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=gather_album_art"><?php echo T_('Gather Album Art'); ?></a>
</div>
<?php UI::show_box_bottom(); ?>

<?php UI::show_box_top(T_('Other Tools')); ?>
<div>
    <a class="button" href="<?php echo $web_path; ?>/admin/duplicates.php"><?php echo T_('Show Duplicate Songs'); ?></a>
    <a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo T_('Clear Now Playing'); ?></a>
    <a class="button" href="<?php echo $web_path; ?>/admin/system.php?action=generate_config"><?php echo T_('Generate New Config'); ?></a>
    <a class="button" href="<?php echo $web_path; ?>/admin/preferences.php?action=show_set_preferences"><?php echo T_('Preferences Permissions'); ?></a>
    <a class="button" href="<?php echo $web_path; ?>/admin/system.php?action=export&amp;export=itunes"><?php echo T_('Export To Itunes DB'); ?></a>
    <a class="button" href="<?php echo $web_path; ?>/admin/users.php?action=show_inactive&amp;days=30"><?php echo T_('Show Inactive Users'); ?></a>
<!--    <a class="button" href="<?php echo $web_path; ?>/admin/system.php?action=check_version"><?php echo T_('Check for New Version'); ?></a>-->
</div>
<?php UI::show_box_bottom(); ?>
