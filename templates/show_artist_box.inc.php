<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
?>
<table class="text-box">
<tr>
        <td>
        <span class="header1"><?php print _("Albums by") . " " . $artist->full_name; ?></span>
        <ul>
                <?php
                        if (conf('ratings')) {
                                show_rating($artist->id,'artist');
                        } // end if ratings
                echo "<br />\n";
                ?>
                <li><a href="<?php print $web_path; ?>/artists.php?action=show_all_songs&amp;artist=<?php print $artist_id; ?>"><?php print _("Show All Songs By") . " " . $artist->full_name; ?></a></li>
                <li><a href="<?php print $web_path; ?>/song.php?action=m3u&amp;artist=<?php print $artist_id; ?>"><?php print _("Play All Songs By") . " " . $artist->full_name; ?></a></li>
                <li><a href="<?php print $web_path; ?>/song.php?action=m3u&amp;artist_random=<?php print $artist_id; ?>"><?php print _("Play Random Songs By") . " " . $artist->full_name; ?></a></li>
                <?php  if ($user->has_access('100')) { ?>
                        <li><a href="<?php echo $web_path; ?>/artists.php?action=update_from_tags&amp;artist=<?php print $artist_id; ?>"><?php print _("Update from tags"); ?></a></li>
                        <li><a href="<?php echo $web_path; ?>/artists.php?action=show_rename&amp;artist=<?php echo $artist_id; ?>"><?php echo _("Rename Artist"); ?></a></li>
                <?php } ?>
        </ul>
        </td>
</tr>
</table>
