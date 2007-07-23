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
$base_url = '?action=set_rating&rating_type=' . $rating->type . '&object_id=' . $rating->id;


//set the background to no stars
echo "<ul class=\"star-rating\">\n";

// Add in the 0 / Remove rating level
echo "<li class=\"zero-stars\">" . Ajax::text($base_url . '&rating=-1','','rating0_' . $rating->id,'','zero-stars') . "</li>"; 

// decide width of rating. image is 16 px wide
$width = $rating->rating*16;

//set the current rating background 
echo "<li class=\"current-rating\" style=\"width:${width}px\" >Current rating: ";
if ($rating->rating <= 0) {
	echo "not rated yet </li>\n";
}
else echo "$rating->rating of 5</li>\n";

//it did not like my "1-star", "2-star" ... css styles, and I changed it to this after I realized star1... would have worked :\
?>
<li>
	<?php echo Ajax::text($base_url . '&rating=1','','rating1_' . $rating->id,'','one-stars'); ?>
</li>
<li>
	<?php echo Ajax::text($base_url . '&rating=2','','rating2_' . $rating->id,'','two-stars'); ?>
</li>
<li>
	<?php echo Ajax::text($base_url . '&rating=3','','rating3_' . $rating->id,'','three-stars'); ?>
</li>
<li>
	<?php echo Ajax::text($base_url . '&rating=4','','rating4_' . $rating->id,'','four-stars'); ?>
</li>
<li>
	<?php echo Ajax::text($base_url . '&rating=5','','rating5_' . $rating->id,'','five-stars'); ?>
</li>
</ul>

