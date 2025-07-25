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

// Gotta do some math here!
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

/** @var array $images */
/** @var int $object_id */
/** @var string $object_type */
/** @var string $burl */

$web_path = AmpConfig::get_web_path();

$total_images = count($images);
$rows         = floor($total_images / 5);
$count        = 0;
$ajax_str     = ((AmpConfig::get('ajax_load')) ? '#' : '');
$select_art   = '/' . $ajax_str . 'arts.php?action=select_art'; ?>
<?php Ui::show_box_top(T_('Select New Art'), 'box box_album_art'); ?>
<table class="table-data">
<tr>
<?php
while ($count <= $rows) {
    $j=0;
    while ($j < 5) {
        $key        = $count * 5 + $j;
        $image_url  = $web_path . '/image.php?type=session&image_index=' . $key . '&cache_bust=' . date('YmdHis') . bin2hex(random_bytes(20)) . '&object_type=' . $object_type;
        $dimensions = [
            'width' => 0,
            'height' => 0,
        ];
        if (!empty($_SESSION['form']['images'][$key])) {
            $dimensions = Core::image_dimensions(Art::get_from_source($_SESSION['form']['images'][$key], $object_type));
        }
        if ((int) $dimensions['width'] == 0 || (int) $dimensions['height'] == 0) {
            $image_url = $web_path . '/images/blankalbum.png';
        }
        if (!isset($images[$key])) {
            echo "<td>&nbsp;</td>\n";
        } else { ?>
            <td>
                <a href="<?php echo $image_url; ?>" title="<?php echo $_SESSION['form']['images'][$key]['title']; ?>" rel="prettyPhoto" target="_blank"><img src="<?php echo $image_url; ?>" alt="<?php echo T_('Art'); ?>" height="" width="175" /></a>
                <br />
                <p>
                <?php if (is_array($dimensions) && (!(int) $dimensions['width'] == 0 || !(int) $dimensions['height'] == 0)) { ?>
                [<?php echo (int) ($dimensions['width']); ?>x<?php echo (int) ($dimensions['height']); ?>]
                [<a href="<?php echo $web_path . $select_art; ?>&image=<?php echo $key; ?>&object_type=<?php echo $object_type; ?>&object_id=<?php echo $object_id; ?>&burl=<?php echo base64_encode($burl); ?>"><?php echo T_('Select'); ?></a>]
                <?php
                } else { ?>
                <span class="error"><?php echo T_('Invalid'); ?></span>
                <?php } ?>
                </p>
            </td>
<?php
        } // end else
        $j++;
    } // end while cells
    if ($count < $rows) {
        echo "</tr>\n<tr>";
    } else {
        echo "</tr>";
    }
    $count++;
} ?>
</table>
<?php Ui::show_box_bottom(); ?>
