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
?>
<?php
ob_start();
require AmpConfig::get('prefix') . '/templates/show_search_title.inc.php';
$title = ob_get_contents();
ob_end_clean();
UI::show_box_top('<div id="smartplaylist_row_' . $playlist->id . '">' . $title . '</div>' , 'box box_smartplaylist');
?>
<div id="information_actions">
    <ul>
        <?php if (Access::check_function('batch_download')) { ?>
        <li>
            <a rel="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/batch.php?action=search&amp;id=<?php echo $playlist->id; ?>"><?php echo UI::get_icon('batch_download', T_('Batch Download')); ?></a>
            <?php echo T_('Batch Download'); ?>
        </li>
            <?php } ?>
        <li>
            <?php echo Ajax::button('?action=basket&type=search&id=' . $playlist->id,'add', T_('Add All'),'play_playlist'); ?>
            <?php echo T_('Add All'); ?>
        </li>
        <?php if ($playlist->has_access()) { ?>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/smartplaylist.php?action=delete_playlist&playlist_id=<?php echo $playlist->id; ?>">
                <?php echo UI::get_icon('delete'); ?>
            </a>
            <?php echo T_('Delete'); ?>
        </li>
        <?php } ?>
    </ul>
</div>

<form id="editplaylist" name="editplaylist" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/smartplaylist.php?action=update_playlist&playlist_id=<?php echo $playlist->id; ?>" enctype="multipart/form-data" style="Display:inline">
    <?php require AmpConfig::get('prefix') . '/templates/show_rules.inc.php'; ?>
    <div class="formValidation">
        <input class="button" type="submit" value="<?php echo T_('Save Changes'); ?>" />
    </div>
</form>

<?php UI::show_box_bottom(); ?>

<div>
<?php
    $browse = new Browse();
    $browse->set_type('playlist_song');
    $browse->add_supplemental_object('search', $playlist->id);
    $browse->set_static_content(false);
    $browse->show_objects($object_ids);
    $browse->store();
?>
</div>
