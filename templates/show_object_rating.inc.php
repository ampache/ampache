<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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

/* Create some variables we are going to need */
$web_path = AmpConfig::get('web_path');
$base_url = '?action=set_rating&rating_type=' . $rating->type . '&object_id=' . $rating->id;
$othering = false;
$rate     = $rating->get_user_rating();
if (!$rate) {
    $rate     = $rating->get_average_rating();
    $othering = true;
}
?>

<div class="star-rating dynamic-star-rating<?php if ($othering) {
    echo ' global-star-rating';
} ?>">
  <ul>
    <?php
    // decide width of rating (5 stars -> 20% per star)
    $width = $rate * 20;
    if ($width < 0) {
        $width = 0;
    }

    //set the current rating background
    echo '<li class="current-rating" style="width:' . $width . '%" >';
    echo T_('Current rating: ');
    if ($rate <= 0) {
        echo T_('not rated yet') . "</li>\n";
    } else {
        printf(T_('%s of 5'), $rate);
    } echo "</li>\n";

    for ($i = 1; $i < 6; $i++) {
        ?>
      <li>
          <?php echo Ajax::text($base_url . '&rating=' . $i, '', 'rating' . $i . '_' . $rating->id . '_' . $rating->type, '', 'star' . $i); ?>
      </li>
    <?php
    }
    ?>
  </ul>
       <?php echo Ajax::text($base_url . '&rating=-1', '', 'rating0_' . $rating->id . '_' . $rating->type, '', 'star0'); ?>
</div>
