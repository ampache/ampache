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
<?php Ajax::start_container('tag_filter'); ?>
<?php foreach ($object_ids as $data) { ?>
    <div class="tag_container">
        <div class="tag_button">
            <span id="click_tag_<?php echo $data['id']; ?>"><?php echo $data['name']; ?></span>
            <?php echo Ajax::observe('click_tag_' . $data['id'], 'click', Ajax::action('?page=tag&action=add_filter&browse_id=' . $browse2->id . '&tag_id=' . $data['id'], '')); ?>
        </div>
        <?php if (Access::check('interface', '50')) { ?>
        <div class="tag_actions">
            <ul>
                <li>
                    <a class="tag_edit" id="<?php echo 'edit_tag_'.$data['id'] ?>" onclick="showEditDialog('tag_row', '<?php echo $data['id'] ?>', '<?php echo 'edit_tag_'.$data['id'] ?>', '<?php echo T_('Tag edit') ?>', 'click_tag_')">
                        <?php echo UI::get_icon('edit', T_('Edit')); ?>
                    </a>
                </li>
                <li>
                    <a class="tag_delete" href="<?php echo AmpConfig::get('ajax_url') ?>?page=tag&action=delete&tag_id=<?php echo $data['id']; ?>" onclick="return confirm('<?php echo T_('Do you really want to delete the tag?'); ?>');"><?php echo UI::get_icon('delete', T_('Delete')); ?></a>
                </li>
            </ul>
        </div>
    <?php } ?>
    </div>
<?php } ?>
<br /><br /><br />
<?php
if (isset($_GET['show_tag'])) {
    $show_tag = intval($_GET['show_tag']);
?>
<script>
$(document).ready(function () {
    <?php echo Ajax::action('?page=tag&action=add_filter&browse_id=' . $browse2->id . '&tag_id=' . $show_tag, ''); ?>
});
</script>
<?php } ?>
<?php if (!count($object_ids)) { ?>
<span class="fatalerror"><?php echo T_('Not Enough Data'); ?></span>
<?php } ?>
<?php Ajax::end_container(); ?>
