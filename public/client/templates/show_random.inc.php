<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Repository\VideoRepositoryInterface;

/** @var VideoRepositoryInterface $videoRepository */
/** @var list<int> $object_ids */

$web_path = AmpConfig::get_web_path('/client');

$get_type     = (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
$length       = $_POST['length'] ?? 0;
$size_limit   = $_POST['size_limit'] ?? 0;
$random_count = $_POST['limit'] ?? 1;
$currentType  = (in_array($get_type, Random::VALID_TYPES))
    ? $get_type
    : null;
if (!$currentType) {
    header("Location: " . $web_path . '/random.php?action=get_advanced&type=song');
}
$browse_type = ($currentType == 'video')
    ? 'video'
    : 'song';

Ui::show_box_top(T_('Play Random Selection'), 'box box_random'); ?>
<form id="random" method="post" enctype="multipart/form-data" action="<?php echo $web_path; ?>/random.php?action=get_advanced&type=<?php echo (string) scrub_out($currentType); ?>">
<input type='hidden' name='random' value=1>
<div class="category_options">
    <a class="category <?php echo ($currentType == 'song') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/random.php?action=advanced&type=song">
        <?php echo T_('Songs'); ?>
    </a>
    <a class="category <?php echo ($currentType == 'album') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/random.php?action=advanced&type=album">
        <?php echo T_('Albums'); ?>
    </a>
    <a class="category <?php echo ($currentType == 'artist') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/random.php?action=advanced&type=artist">
        <?php echo T_('Artists'); ?>
    </a>
    <?php if (AmpConfig::get('allow_video') && $videoRepository->getItemCount()) { ?>
        <a class="category <?php echo ($currentType == 'video') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/random.php?action=advanced&type=video">
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
        foreach ([1, 5, 10, 20, 30, 50, 100, 500, 1000] as $count) {
            echo "\t\t\t" . '<option value="' . $count . '" ' .
                (($random_count == $count) ? 'selected="selected"' : '') . '>' .
                $count . "</option>\n";
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
foreach ([15, 30, 60, 120, 240, 480, 960] as $value) {
    echo "\t\t\t" . '<option value="' . $value . '" ' .
        (($length == $value) ? 'selected="selected"' : '') . '>';
    if ($value < 60) {
        printf(nT_('%d minute', '%d minutes', $value), $value);
    } else {
        printf(nT_('%d hour', '%d hours', $value / 60), $value / 60);
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
foreach ([64, 128, 256, 512, 1024] as $value) {
    echo "\t\t\t" . '<option value="' . $value . '"' .
        (($size_limit == $value) ? 'selected="selected"' : '') . '>' .
        Ui::format_bytes($value * 1048576) . "</option>\n";
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
    if (!empty($object_ids)) {
        $browse = new Browse();
        $browse->set_type($browse_type);
        $browse->save_objects($object_ids);
        $browse->show_objects();
        $browse->store();
        echo Ajax::observe('window', 'load', Ajax::action('?action=refresh_rightbar', 'playlist_refresh_load'));
    } ?>
</div>

