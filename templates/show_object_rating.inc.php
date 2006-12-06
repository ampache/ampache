<?php
/*
 Copyright 2001 - 2006 Ampache.org
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
$web_path = conf('web_path');
$base_url = conf('ajax_url') . '?action=set_rating&amp;rating_type=' . $rating->type . '&amp;object_id=' . $rating->id . conf('ajax_info');


//set the background to no stars
echo "<ul class=\"star-rating\">\n";

/* Handle the "Not rated" possibility */
if ($rating->rating == '-1') { 
	echo "<li class=\"zero-stars\"><span onclick=\"ajaxPut('" . $base_url . "&amp;rating=-1');return true;\" title=\"don't play\" class=\"zero-stars\"></span></li>\n";
}
else { 
	echo "<li class=\"zero-stars\"><span onclick=\"ajaxPut('" . $base_url . "&amp;rating=-1');return true;\" title=\"remove rating\" class=\"zero-stars\"></span></li>\n";
}
// decide width of rating. image is 16 px wide
$width = $rating->rating*16;
if ($width < 0) $width = 0;

//set the current rating background 
echo "<li class=\"current-rating\" style=\"width:${width}px\" >Current rating: ";
if ($rating->rating <= 0) {
	echo "not rated yet </li>\n";
}
else echo "$rating->rating of 5</li>\n";

//it did not like my "1-star", "2-star" ... css styles, and I changed it to this after I realized star1... would have worked :\
?>
<li>
	<span onclick="ajaxPut('<?php echo $base_url; ?>&amp;rating=1');return true;" class="one-stars" title="1 <?php echo _('out of'); ?> 5"></span>
</li>
<li>
	<span onclick="ajaxPut('<?php echo $base_url; ?>&amp;rating=2');return true;" class="two-stars" title="2 <?php echo _('out of'); ?> 5"></span>
</li>
<li>
	<span onclick="ajaxPut('<?php echo $base_url; ?>&amp;rating=3');return true;" class="three-stars" title="3 <?php echo _('out of'); ?> 5"></span>
</li>
<li>
	<span onclick="ajaxPut('<?php echo $base_url; ?>&amp;rating=4');return true;" class="four-stars" title="4 <?php echo _('out of'); ?> 5"></span>
</li>
<li>
	<span onclick="ajaxPut('<?php echo $base_url; ?>&amp;rating=5');return true;" class="five-stars" title="5 <?php echo _('out of'); ?> 5"></span>
</li>
</ul>

