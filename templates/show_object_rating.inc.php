<?php
/*

 Copyright 2001 - 2006 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/


if ($type != 'song') { 
	echo "<strong>" . _("Rating") . ":</strong>";
}

/* Create some variables we are going to need */
$base_url 	= conf('web_path') . '/ratings.php?action=set_rating&mode=' . conf('flash') . '&rating_type=' . $rating->type . '&object_id=' . $rating->id . '&username=' . $GLOBALS['user']->username;
$score		= '0';

/* count up to 6 */
while ($score < 6) { 
	/* Handle the "Not rated" possibility */
	if ($score == '0' AND $score === $rating->rating) { 
		echo "<img src=\"" . conf('web_path') . "/images/ratings/x.gif\" border=\"0\" alt=\"" . get_rating_name($score) . "\">\n";
	}
	elseif ($score == '0') { 
		echo "<a href=\"" . $base_url . "&rating=$score\">\n";
		echo "\t<img src=\"" . conf('web_path') . "/images/ratings/x_off.gif\" border=\"0\" alt=\"" . get_rating_name($score) . "\">\n";
		echo "</a>";
	}
	elseif ($score === $rating->rating) { 
		echo "<img src=\"" . conf('web_path') . "/images/ratings/star.gif\" border=\"0\" alt=\"" . get_rating_name($score) . "\">\n";
	}
	else { 
		echo "<a href=\"" . $base_url . "&rating=$score\">\n\t<img src=\"" . conf('web_path') . "/images/ratings/star_off.gif\" border=\"0\" alt=\"" . get_rating_name($score) . "\">\n</a>\n";
	}
	/* Next! */
	$score++;
} // end while

?>
