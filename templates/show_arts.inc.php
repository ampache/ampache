<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Gotta do some math here!
$total_images = count($images);
$rows         = floor($total_images / 4);
$count        = 0; ?>
<?php UI::show_box_top(T_('Select New Art'), 'box box_album_art'); ?>
<table class="table-data">
<tr>
<?php
while ($count <= $rows) {
    $j=0;
    while ($j < 4) {
        $key        = $count * 4 + $j;
        $image_url  = AmpConfig::get('web_path') . '/image.php?type=session&image_index=' . $key . '&cache_bust=' . date('YmdHis') . mt_rand();
        $dimensions = Core::image_dimensions(Art::get_from_source($_SESSION['form']['images'][$key], $object_type));
        if (!isset($images[$key]) || !Art::check_dimensions($dimensions)) {
            echo "<td>&nbsp;</td>\n";
        } else { ?>
            <td>
                <a href="<?php echo $image_url; ?>" title="<?php echo $_SESSION['form']['images'][$key]['title']; ?>" rel="prettyPhoto" target="_blank"><img src="<?php echo $image_url; ?>" alt="<?php echo T_('Art'); ?>" height="175" width="175" /></a>
                <br />
                <p>
                <?php if (is_array($dimensions)) { ?>
                [<?php echo (int) ($dimensions['width']); ?>x<?php echo (int) ($dimensions['height']); ?>]
                <?php
            } else { ?>
                <span class="error"><?php echo T_('Invalid'); ?></span>
                <?php
            } ?>
                [<a href="<?php echo AmpConfig::get('web_path'); ?>/arts.php?action=select_art&image=<?php echo $key; ?>&object_type=<?php echo $object_type; ?>&object_id=<?php echo $object_id; ?>&burl=<?php echo base64_encode($burl); ?>"><?php echo T_('Select'); ?></a>]
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
} // end while?>
</table>
<?php UI::show_box_bottom(); ?>
