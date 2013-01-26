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
?>
<?php UI::show_box_top(sprintf('%s Recommendations'), $working_user->fullname); ?>
<table class="tabledata">
<tr>
    <td valign="top">
    <?php
    if (count($recommended_artists)) {
        $items = $working_user->format_recommendations($recommended_artists,'artist');
        show_info_box(T_('Recommended Artists'),'artist',$items);
    }
    else {
        echo "<span class=\"error\">" . T_('Not Enough Data') . "</span>";
    }
    ?>
    </td>
    <td valign="top">
    <?php
    if (count($recommended_albums)) {
        $items = $working_user->format_recommendations($recommended_albums,'album');
        show_info_box(T_('Recommended Albums'),'album',$items);
    }
    else {
        echo "<span class=\"error\">" . T_('Not Enough Data') . "</span>";
    }
    ?>
    </td>
    <td valign="top">
    <?php
    if (count($recommended_songs)) {
        $items = $working_user->format_recommendations($recommended_songs,'song');
        show_info_box(T_('Recommended Songs'),'song',$items);
    }
    else {
        echo "<span class=\"error\">" . T_('Not Enough Data') . "</span>";
    }
    ?>
    </td>
</tr>
</table>
<?php UI::show_box_bottom(); ?>
