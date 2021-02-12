<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

$tag_types = array(
    'artist' => T_('Artist'),
    'album' => T_('Album'),
    'song' => T_('Song'),
    'video' => T_('Video'),
); ?>
<?php Ajax::start_container('tag_filter'); ?>

<form action="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=tag" method="POST">
    <?php echo T_('View'); ?>:
    <select name="type">
        <?php
        foreach ($tag_types as $tag_type => $tag_name) {
            echo "<option value='" . $tag_type . "'";
            if ($tag_type == (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS)) {
                echo " selected";
            }
            echo ">" . $tag_name . "</option>";
        } ?>
    </select>
<input type="submit" value="Ok" />
</form>

<?php foreach ($object_ids as $data) { ?>
    <div class="tag_container">
        <div class="tag_button">
            <span id="click_tag_<?php echo $data['id']; ?>"><?php echo $data['name']; ?></span>
            <?php echo Ajax::observe('click_tag_' . $data['id'], 'click', Ajax::action('?page=tag&action=add_filter&browse_id=' . $browse2->id . '&tag_id=' . $data['id'], '')); ?>
        </div>
        <?php if (Access::check('interface', 50)) { ?>
        <div class="tag_actions">
            <ul>
                <li>
                    <a class="tag_edit" id="<?php echo 'edit_tag_' . $data['id'] ?>" onclick="showEditDialog('tag_row', '<?php echo $data['id'] ?>', '<?php echo 'edit_tag_' . $data['id'] ?>', '<?php echo T_('Edit') ?>', 'click_tag_')">
                        <?php echo UI::get_icon('edit', T_('Edit')); ?>
                    </a>
                </li>
                <li>
                    <a class="tag_delete" href="<?php echo AmpConfig::get('ajax_url') ?>?page=tag&action=delete&tag_id=<?php echo $data['id']; ?>" onclick="return confirm('<?php echo T_('Do you really want to delete this Tag?'); ?>');"><?php echo UI::get_icon('delete', T_('Delete')); ?></a>
                </li>
            </ul>
        </div>
    <?php
            } ?>
    </div>
<?php
        } ?>

<br /><br /><br />
<?php
if (filter_has_var(INPUT_GET, 'show_tag')) {
            $show_tag = (int) (Core::get_get('show_tag')); ?>
<script>
$(document).ready(function () {
    <?php echo Ajax::action('?page=tag&action=add_filter&browse_id=' . $browse2->id . '&tag_id=' . $show_tag, ''); ?>
});
</script>
<?php
        } ?>
<?php if (!count($object_ids)) { ?>
<span class="fatalerror"><?php echo T_('Not Enough Data'); ?></span>
<?php
        } ?>
<?php Ajax::end_container(); ?>
