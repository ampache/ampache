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

$base_url = '?action=set_rating&rating_type=' . $rating->type . '&object_id=' . $rating->id;
$rate = $rating->get_user_rating();
if (!$rate) {
    $rate = $rating->get_average_rating();
}
if (!$rate || $rate > 5)
    $rate = 0;

$id = 'rating' . '_' . $rating->id . '_' . $rating->type . '_';
$id_container = $id .'rating';
?>
<span id="<?php echo $id_container; ?>" class="rating-container rating-container-inline">
    <span class="rating user-rating">
        <?php
            for ($i = 0; $i < 5; $i++) {
                $base_value = ($i < $rate ? 'fa-star' : 'fa-star-o');
                $save_value = ($i < $rate ? 'save-on' : '');
                echo '<i id="'. $id . $i.'" class="star-icon fa '. $base_value .' '. $save_value . '"></i>';
                echo Ajax::run('$("#'. $id . $i.'").click(function () { saveStarIcons("'. Ajax::url($base_url . '&rating=' . ($i + 1)) .'", "'. $id .'", '. $i .');});');
                echo Ajax::run('$("#'. $id . $i.'").mouseover(function () { toggleStarIcons("'. $id .'", "'. $base_value .'", '. $i .');});');
            }
        ?>
    </span>
</span>
<?php
echo Ajax::run('$("#'. $id_container . '").mouseleave(function () { resetStarIcons("'. $id .'");});');
?>