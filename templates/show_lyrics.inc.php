<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

/*
 * show_lyrics.inc.php
 * show lyrics for selected song, if user has selected to do so in preferences
 *
 * Al Ayres
 * al.ayres@gmail.com
 * Modified: 02/24/2009
 *
 * @todo get lyrics from id3tag, if possible.
*/
/* HINT: Song Title */
UI::show_box_top(sprintf(T_('%s Lyrics'), $song->title), 'box box_lyrics');
?>
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
    <td>
        <?php
        if($return == "Sorry Lyrics, Not found") {
            echo T_("Sorry Lyrics Not Found.");
        }
        else {
            echo $link;
            echo "<pre>" . $return . "</pre>";
        }
        ?>
    </td>
</tr>
</table>
<?php UI::show_box_bottom(); ?>
