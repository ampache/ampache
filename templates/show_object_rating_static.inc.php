<?php
/*
 Copyright 2001 - 2007 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/* Create some variables we are going to need */
$web_path = Config::get('web_path');
$base_url = Config::get('ajax_url') . '?action=set_rating&amp;rating_type=' . $rating->type . '&amp;object_id=' . $rating->id;

echo "<div class=\"star-rating\">\n";
echo "<ul>\n";

// decide width of rating (5 stars -> 20% per star)
$width = $rating->rating*20;
if ($width < 0) $width = 0;

//set the current rating background
echo "<li class=\"current-rating\" style=\"width:${width}%\" >Current rating: ";
if ($rating->rating <= 0) {
    echo "not rated yet </li>\n";
}
else echo "$rating->rating of 5</li>\n";

//it did not like my "1-star", "2-star" ... css styles, and I changed it to this after I realized star1... would have worked :\
?>
<li>
    <div class="one-stars" title="1 <?php echo _('out of'); ?> 5">1</div>
</li>
<li>
    <div class="two-stars" title="2 <?php echo _('out of'); ?> 5">2</div>
</li>
<li>
    <div class="three-stars" title="3 <?php echo _('out of'); ?> 5">3</div>
</li>
<li>
    <div class="four-stars" title="4 <?php echo _('out of'); ?> 5">4</div>
</li>
<li>
    <div class="five-stars" title="5 <?php echo _('out of'); ?> 5">5</div>
</li>
</ul>
</div>
