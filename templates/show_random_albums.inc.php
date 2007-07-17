<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

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
$web_path = Config::get('web_path'); 
?>
<?php show_box_top(_('Albums of the Moment')); ?>
<table class="tabledata">
<tr>
	<?php 
	foreach ($albums as $album_id) { 
		$album = new Album($album_id); 
		$album->format(); 
		$name = scrub_out('[' . $album->artist . '] ' . $album->name); 
        ?>
        <td>
                <a href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo $album_id; ?>">
                <?php if (Config::get('show_album_art')) { ?>
                <img src="<?php echo $web_path; ?>/image.php?thumb=3&amp;id=<?php echo $album_id; ?>" width="80" height="80" border="0" alt="<?php echo $name; ?>" title="<?php echo $name; ?>" />
                <?php } else { ?>
                <?php echo '[' . $album->f_artist . '] ' . $album->f_name; ?>
                <?php } ?>
                </a><br>
                <?php
                if(Config::get('ratings')){
                        echo "<div style=\"float:left; display:inline;\" id=\"rating_" . $album->id . "_album\">";
                        show_rating_static($album->id, 'album');}
                        echo "</div>";
                ?>
        </td>
        <?php } ?>
</tr>
</table> 
<?php show_box_bottom(); ?>
