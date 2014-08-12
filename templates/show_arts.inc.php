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

// Gotta do some math here!
$total_images = count($images);
$rows = floor($total_images/4);
$i = 0;
?>
<?php UI::show_box_top(T_('Select New Art'), 'box box_album_art'); ?>
<table class="table-data">
<tr>
<?php
while ($i <= $rows) {
    $j=0;
    while ($j < 4) {
        $key = $i*4+$j;
        $image_url = AmpConfig::get('web_path') . '/image.php?type=session&image_index=' . $key;
        $dimensions = Core::image_dimensions(Art::get_from_source($_SESSION['form']['images'][$key], $object_type));
        if (!isset($images[$key])) { echo "<td>&nbsp;</td>\n"; } else {
?>
            <td align="center">
                <a href="<?php echo $image_url; ?>" title="<?php echo $_SESSION['form']['images'][$key]['title']; ?>" rel="prettyPhoto" target="_blank"><img src="<?php echo $image_url; ?>" alt="<?php echo T_('Art'); ?>" border="0" height="175" width="175" /></a>
                <br />
                <p align="center">
                <?php if (is_array($dimensions)) { ?>
                [<?php echo intval($dimensions['width']); ?>x<?php echo intval($dimensions['height']); ?>]
                <?php } else { ?>
                <span class="error"><?php echo T_('Invalid'); ?></span>
                <?php } ?>
                [<a href="<?php echo AmpConfig::get('web_path'); ?>/arts.php?action=select_art&image=<?php echo $key; ?>&object_type=<?php echo $object_type; ?>&object_id=<?php echo $object_id; ?>&burl=<?php echo rawurlencode($burl); ?>"><?php echo T_('Select'); ?></a>]
                </p>
            </td>
<?php
        } // end else
        $j++;
    } // end while cells
    if ($i < $rows) { echo "</tr>\n<tr>"; } else { echo "</tr>"; }
    $i++;
} // end while
?>
</table>
<?php UI::show_box_bottom(); ?>
