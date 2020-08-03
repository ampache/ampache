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

/* Create some variables we are going to need */
$web_path = AmpConfig::get('web_path');
$base_url = '?action=set_rating&rating_type=' . $rating->type . '&object_id=' . $rating->id;
$rate     = ($rating->get_user_rating() ?: 0);
if ($global_rating) {
    $rate = $rating->get_average_rating();
} ?>
<div class="star-rating dynamic-star-rating<?php if ($global_rating) {
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
    echo T_('Current rating') . ': ';
    if ($rate < 1) {
        echo T_('not rated yet') . "</li>\n";
    } else {
        /* HINT: object rating */
        printf(T_('%s of 5'), $rate);
    } echo "</li>\n";

    for ($count = 1; $count < 6; $count++) { ?>
      <li>
          <?php echo Ajax::text($base_url . '&rating=' . $count, '', 'rating' . $count . '_' . $rating->id . '_' . $rating->type, '', 'star' . $count); ?>
      </li>
    <?php
    } ?>
  </ul>
       <?php echo Ajax::text($base_url . '&rating=-1', '', 'rating0_' . $rating->id . '_' . $rating->type, '', 'star0'); ?>
</div>
