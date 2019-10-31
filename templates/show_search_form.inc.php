<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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

UI::show_box_top(T_('Search Ampache') . "...", 'box box_advanced_search'); ?>
<form id="search" name="search" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/search.php?type=<?php echo (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) ? scrub_out((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) : 'song'; ?>" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata">
    <tr id="search_location">
        <td><?php if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) !== 'song') {
    ?><a href="<?php echo AmpConfig::get('web_path'); ?>/search.php?type=song"><?php echo T_('Songs'); ?></a><?php
} else {
        echo T_('Songs');
    } ?></td>
        <td><?php if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) !== 'album') {
        ?><a href="<?php echo AmpConfig::get('web_path'); ?>/search.php?type=album"><?php echo T_('Albums'); ?></a><?php
    } else {
        echo T_('Albums');
    } ?></td>
        <td><?php if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) !== 'artist') {
        ?><a href="<?php echo AmpConfig::get('web_path'); ?>/search.php?type=artist"><?php echo T_('Artists'); ?></a><?php
    } else {
        echo T_('Artists');
    } ?></td>
    <?php if (AmpConfig::get('allow_video') && Video::get_item_count('Video')) { ?>
        <td><?php if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) != 'video') { ?>
            <td><?php if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) !== 'video') {
        ?><a href="<?php echo AmpConfig::get('web_path'); ?>/search.php?type=video"><?php echo T_('Videos'); ?></a><?php
    } else {
        echo T_('Videos');
    } ?></td>
        <?php
        }
    }?>
    </tr>
</table>
<table class="tabledata">
    <tr id="search_max_results">
    <td><?php echo T_('Maximum Results'); ?></td>
        <td>
                <select name="limit">
                        <option value="0"><?php echo T_('Unlimited'); ?></option>
                        <option value="25" <?php if ((int) filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT) == 25) {
        echo "selected=\"selected\"";
    }?>>25</option>
                        <option value="50" <?php if ((int) filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT) == 50) {
        echo "selected=\"selected\"";
    }?>>50</option>
                        <option value="100" <?php if ((int) filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT) == 100) {
        echo "selected=\"selected\"";
    }?>>100</option>
                        <option value="500" <?php if ((int) filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT) == 500) {
        echo "selected=\"selected\"";
    }?>>500</option>
                </select>
        </td>
    </tr>
</table>

<?php require AmpConfig::get('prefix') . UI::find_template('show_rules.inc.php'); ?>

<div class="formValidation">
            <input class="button" type="submit" value="<?php echo T_('Search'); ?>" />&nbsp;&nbsp;
<?php if (((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) == 'song' || ! (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) && Access::check('interface', 25)) { ?>
        <input id="savesearchbutton" class="button" type="submit" value="<?php echo T_('Save as Smart Playlist'); ?>" onClick="$('#hiddenaction').val('save_as_smartplaylist');" />&nbsp;&nbsp;
<?php
    } ?>
            <input type="hidden" id="hiddenaction" name="action" value="search" />
</div>
</form>
<script>
    document.getElementById('searchString').value = '';
</script>
<?php UI::show_box_bottom(); ?>
