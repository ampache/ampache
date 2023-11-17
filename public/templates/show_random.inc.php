<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\Api\Ajax;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Browse;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;

/** @var VideoRepositoryInterface $videoRepository */
/** @var array $object_ids */

$web_path     = (string)AmpConfig::get('web_path', '');
$get_type     = (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
$length       = $_POST['length'] ?? 0;
$size_limit   = $_POST['size_limit'] ?? 0;
$random_count = $_POST['limit'] ?? 1;
$random_type  = (in_array($get_type, Random::VALID_TYPES))
    ? $get_type
    : null;
if (!$random_type) {
    header("Location: " . $web_path . '/random.php?action=get_advanced&type=song');
}
$browse_type = ($random_type == 'video')
    ? 'video'
    : 'song';

Ui::show_box_top(T_('Play Random Selection'), 'box box_random'); ?>
<form id="random" method="post" enctype="multipart/form-data" action="<?php echo $web_path; ?>/random.php?action=get_advanced&type=<?php echo (string) scrub_out($random_type); ?>">
<input type='hidden' name='random' value=1>
<div class="category_options">
    <a class="category <?php echo ($random_type == 'song') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/random.php?action=advanced&type=song">
        <?php echo T_('Songs'); ?>
    </a>
    <a class="category <?php echo ($random_type == 'album') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/random.php?action=advanced&type=album">
        <?php echo T_('Albums'); ?>
    </a>
    <a class="category <?php echo ($random_type == 'artist') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/random.php?action=advanced&type=artist">
        <?php echo T_('Artists'); ?>
    </a>
    <?php if (AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class)) { ?>
        <a class="category <?php echo ($random_type == 'video') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/random.php?action=advanced&type=video">
            <?php echo T_('Videos'); ?>
        </a>
    <?php } ?>
</div>

<table class="tabledata">
<tr id="search_item_count">
        <td><?php echo T_('Item Count'); ?></td>
        <td>
        <select name="limit">
<?php
        foreach (array(1, 5, 10, 20, 30, 50, 100, 500, 1000) as $i) {
            echo "\t\t\t" . '<option value="' . $i . '" ' .
                (($random_count == $i) ? 'selected="selected"' : '') . '>' .
                $i . "</option>\n";
        }
            echo "\t\t\t" . '<option value="-1" ' .
                (($random_count == '-1') ? 'selected="selected"' : '') . '>' .
                T_('All') . "</option>\n"; ?>
        </select>
        </td>
</tr>
<tr id="search_length">
        <td><?php echo T_('Length'); ?></td>
        <td>
                <?php $name = 'length_' . (int) (Core::get_post('length'));
${$name}                    = ' selected="selected"'; ?>
                <select name="length">
<?php
            echo "\t\t\t" . '<option value="0" ' .
(($length == 0) ? 'selected="selected"' : '') . '>' .
T_('Unlimited') . "</option>\n";
foreach (array(15, 30, 60, 120, 240, 480, 960) as $i) {
    echo "\t\t\t" . '<option value="' . $i . '" ' .
        (($length == $i) ? 'selected="selected"' : '') . '>';
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
        (($size_limit == 0) ? 'selected="selected"' : '') . '>' .
        T_('Unlimited') . "</option>\n";
foreach (array(64, 128, 256, 512, 1024) as $i) {
    echo "\t\t\t" . '<option value="' . $i . '"' .
        (($size_limit == $i) ? 'selected="selected"' : '') . '>' .
        Ui::format_bytes($i * 1048576) . "</option>\n";
} ?>
                </select>
        </td>
</tr>
</table>

<?php require Ui::find_template('show_rules.inc.php'); ?>

<div class="formValidation">
        <input type="submit" value="<?php echo T_('Enqueue'); ?>" />
</div>
</form>
<?php Ui::show_box_bottom(); ?>
<div id="browse">
<?php
    if (isset($object_ids) && is_array($object_ids)) {
        $browse = new Browse();
        $browse->set_type($browse_type);
        $browse->save_objects($object_ids);
        $browse->show_objects();
        $browse->store();
        echo Ajax::observe('window', 'load', Ajax::action('?action=refresh_rightbar', 'playlist_refresh_load'));
    } ?>
</div>

