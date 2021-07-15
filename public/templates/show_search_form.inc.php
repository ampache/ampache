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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Video;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Util\Ui;
use Ampache\Repository\VideoRepositoryInterface;

global $dic;
$videoRepository = $dic->get(VideoRepositoryInterface::class);
$web_path        = AmpConfig::get('web_path');
$currentType     = (isset($searchType))
    ? $searchType
    : (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
$currentType     = (in_array($currentType, array('song', 'album', 'artist', 'label', 'playlist', 'video')))
    ? $currentType
    : null;
if (!$currentType) {
    header("Location: " . $web_path . '/search.php?type=song');
}
Ui::show_box_top(T_('Search Ampache') . "...", 'box box_advanced_search'); ?>
<form id="search" name="search" method="post" action="<?php echo $web_path; ?>/search.php?type=<?php echo $currentType; ?>" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata">
    <tr id="search_location">
    <td><?php if ($currentType !== 'song') { ?>
        <a href="<?php echo $web_path; ?>/search.php?type=song"><?php echo T_('Songs'); ?></a><?php
    } else {
        echo T_('Songs');
    } ?></td>
    <td><?php if ($currentType !== 'album') { ?>
        <a href="<?php echo $web_path; ?>/search.php?type=album"><?php echo T_('Albums'); ?></a><?php
    } else {
        echo T_('Albums');
    } ?></td>
    <td><?php if ($currentType !== 'artist') { ?>
        <a href="<?php echo $web_path; ?>/search.php?type=artist"><?php echo T_('Artists'); ?></a><?php
    } else {
        echo T_('Artists');
    } ?></td>
    <?php if (AmpConfig::get('label')) { ?>
        <td><?php if ($currentType !== 'label') { ?>
            <a href="<?php echo $web_path; ?>/search.php?type=label"><?php echo T_('Labels'); ?></a> <?php
        } else {
            echo T_('Labels');
        } ?></td>
    <?php } ?>
        <td><?php if ($currentType !== 'playlist') { ?>
            <a href="<?php echo $web_path; ?>/search.php?type=playlist"><?php echo T_('Playlists'); ?></a> <?php
        } else {
            echo T_('Playlists');
        } ?></td>
    <?php if (AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class)) { ?>
        <td><?php if ($currentType !== 'video') { ?>
            <a href="<?php echo $web_path; ?>/search.php?type=video"><?php echo T_('Videos'); ?></a> <?php
        } else {
            echo T_('Videos');
        } ?></td>
    <?php } ?>
    </tr>
</table>
<table class="tabledata">
    <tr id="search_max_results">
    <td><?php echo T_('Maximum Results'); ?></td>
        <td>
                <select name="limit">
                        <option value="0"><?php echo T_('Unlimited'); ?></option>
                        <option value="5" <?php if ((int) $_REQUEST['limit'] == 5) {
            echo "selected=\"selected\"";
        }?>>5</option>
                        <option value="10" <?php if ((int) $_REQUEST['limit'] == 10) {
            echo "selected=\"selected\"";
        }?>>10</option>
                        <option value="25" <?php if ((int) $_REQUEST['limit'] == 25) {
            echo "selected=\"selected\"";
        }?>>25</option>
                        <option value="50" <?php if ((int) $_REQUEST['limit'] == 50) {
            echo "selected=\"selected\"";
        }?>>50</option>
                        <option value="100" <?php if ((int) $_REQUEST['limit'] == 100) {
            echo "selected=\"selected\"";
        }?>>100</option>
                        <option value="250" <?php if ((int) $_REQUEST['limit'] == 250) {
            echo "selected=\"selected\"";
        }?>>250</option>
                        <option value="500" <?php if ((int) $_REQUEST['limit'] == 500) {
            echo "selected=\"selected\"";
        }?>>500</option>
                </select>
        </td>
    </tr>
    <tr id="random_results">
        <td><?php echo T_('Random'); ?></td>
        <td><input type="checkbox" name="random" value="1" <?php if ((int) $_REQUEST['random'] == 1) {
            echo "checked";
        } ?> /></td>
    </tr>
</table>

<?php require Ui::find_template('show_rules.inc.php'); ?>

<div class="formValidation">
            <input class="button" type="submit" value="<?php echo T_('Search'); ?>" />&nbsp;&nbsp;
<?php if ($currentType == 'song' && Access::check('interface', 25)) { ?>
        <input id="savesearchbutton" class="button" type="submit" value="<?php echo T_('Save as Smart Playlist'); ?>" onClick="$('#hiddenaction').val('save_as_smartplaylist');" />&nbsp;&nbsp;
<?php
    } ?>
            <input type="hidden" id="hiddenaction" name="action" value="search" />
</div>
</form>
<script>
    document.getElementById('searchString').value = '';
</script>
<?php Ui::show_box_bottom(); ?>
