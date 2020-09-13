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
 */ ?>
<?php UI::show_box_top(T_('Play Random Selection'), 'box box_random'); ?>
<form id="random" method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/random.php?action=get_advanced&type=<?php echo Core::get_request('type') ? scrub_out(Core::get_request('type')) : 'song'; ?>">
<table class="tabledata">
<tr id="search_location">
    <td><?php if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) !== 'song') {
    ?><a href="<?php echo AmpConfig::get('web_path'); ?>/random.php?action=advanced&type=song"><?php echo T_('Songs'); ?></a><?php
} else {
        echo T_('Songs');
    } ?></td>
    <td><?php if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) !== 'album') {
        ?><a href="<?php echo AmpConfig::get('web_path'); ?>/random.php?action=advanced&type=album"><?php echo T_('Albums'); ?></a><?php
    } else {
        echo T_('Albums');
    } ?></td>
    <td><?php if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) !== 'artist') {
        ?><a href="<?php echo AmpConfig::get('web_path'); ?>/random.php?action=advanced&type=artist"><?php echo T_('Artists'); ?></a><?php
    } else {
        echo T_('Artists');
    } ?></td>
    <?php if (AmpConfig::get('allow_video') && Video::get_item_count('Video')) { ?>
        <td><?php if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) !== 'video') { ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/search.php?type=video"><?php echo T_('Videos'); ?></a>
            <?php } else {
        echo T_('Videos');
    } ?></td>
    <?php } ?>
</tr>
</table>
<table class="tabledata">
<tr id="search_item_count">
        <td><?php echo T_('Item Count'); ?></td>
        <td>
        <select name="random">
<?php
        foreach (array(1, 5, 10, 20, 30, 50, 100, 500, 1000) as $i) {
            echo "\t\t\t" . '<option value="' . $i . '" ' .
                (($_POST['random'] == $i) ? 'selected="selected"' : '') . '>' .
                $i . "</option>\n";
        }
            echo "\t\t\t" . '<option value="-1" ' .
                (($_POST['random'] == '-1') ? 'selected="selected"' : '') . '>' .
                T_('All') . "</option>\n"; ?>
        </select>
        </td>
</tr>
<tr id="search_length">
        <td><?php echo T_('Length'); ?></td>
        <td>
                <?php $name = 'length_' . (int) (Core::get_post('length')); ${$name} = ' selected="selected"'; ?>
                <select name="length">
<?php
            echo "\t\t\t" . '<option value="0" ' .
                (($_POST['length'] == 0) ? 'selected="selected"' : '') . '>' .
                T_('Unlimited') . "</option>\n";
        foreach (array(15, 30, 60, 120, 240, 480, 960) as $i) {
            echo "\t\t\t" . '<option value="' . $i . '" ' .
                (($_POST['length'] == $i) ? 'selected="selected"' : '') . '>';
            if ($i < 60) {
                printf(nT_('%d minute', '%d minutes', $i), $i);
            } else {
                printf(nT_('%d hour', '%d hours', $i / 60), $i / 60);
            }
            echo "</option>\n";
        } ?>
                </select>
        </td>
</tr>
<tr id="search_size_limit">
        <td><?php echo T_('Size Limit'); ?></td>
        <td>
                <select name="size_limit">
<?php
            echo "\t\t\t" . '<option value="0" ' .
                (($_POST['size_limit'] == 0) ? 'selected="selected"' : '') . '>' .
                T_('Unlimited') . "</option>\n";
        foreach (array(64, 128, 256, 512, 1024) as $i) {
            echo "\t\t\t" . '<option value="' . $i . '"' .
                (($_POST['size_limit'] == $i) ? 'selected="selected"' : '') . '>' .
                UI::format_bytes($i * 1048576) . "</option>\n";
        } ?>
                </select>
        </td>
</tr>
</table>

<?php require AmpConfig::get('prefix') . UI::find_template('show_rules.inc.php'); ?>

<div class="formValidation">
        <input type="submit" value="<?php echo T_('Enqueue'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
<div id="browse">
<?php
    if (is_array($object_ids)) {
        $browse = new Browse();
        $browse->set_type('song');
        $browse->save_objects($object_ids);
        $browse->show_objects();
        $browse->store();
        echo Ajax::observe('window', 'load', Ajax::action('?action=refresh_rightbar', 'playlist_refresh_load'));
    } ?>
</div>

