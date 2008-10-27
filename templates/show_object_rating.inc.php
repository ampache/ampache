<?php
/*
 Copyright 2001 - 2008 Ampache.org
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
?>

<div class="star-rating dynamic-star-rating">
  <ul>
    <?php
    // decide width of rating (5 stars -> 20% per star)
    $width = $rating->preciserating*20;
    if ($width < 0) $width = 0;
    
    //set the current rating background 
    echo "<li class=\"current-rating\" style=\"width:${width}%\" >" . _('Current rating: ');
    if ($rating->rating <= 0) {
    	echo _('not rated yet') . "</li>\n";
    }
    else printf(_('%s of 5'), $rating->preciserating); echo "</li>\n";
    
    for ($i=1; $i<6; $i++)
    {
    ?>
      <li>
      	<?php echo Ajax::text($base_url . '&rating='.$i,'','rating'.$i.'_' . $rating->id,'','star'.$i); ?>
      </li>
    <?php
    }
    ?>
  </ul>
   	<?php echo Ajax::text($base_url . '&rating=-1','','rating0_' . $rating->id,'','star0'); ?>
</div>
