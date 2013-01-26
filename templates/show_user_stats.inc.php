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
<?php UI::show_box_top(sprintf('%s Favorites'), $working_user->fullname); ?>
<table class="tabledata">
<tr>
    <td valign="top">
    <?php
    if (count($favorite_artists)) {
        $items = $working_user->format_favorites($favorite_artists);
        $title = '<a href="' . Config::get('web_path') . '/stream.php?action=play_favorite&amp;type=artist">' .
            UI::get_icon('all', T_('Favorite Artists')) . '</a>&nbsp;' .  T_('Favorite Artists');
        show_info_box($title,'artist',$items);
    }
    else {
        echo "<span class=\"error\">" . T_('Not Enough Data') . "</span>";
    }
    ?>
    </td>
    <td valign="top">
    <?php
    if (count($favorite_albums)) {
        $items = $working_user->format_favorites($favorite_albums);
                $title = '<a href="' . Config::get('web_path') . '/stream.php?action=play_favorite&amp;type=album">' .
                        UI::get_icon('all', T_('Favorite Albums')) . '</a>&nbsp;' .  T_('Favorite Albums');
        show_info_box($title,'album',$items);
    }
    else {
        echo "<span class=\"error\">" . T_('Not Enough Data') . "</span>";
    }
    ?>
    </td>
    <td valign="top">
    <?php
    if (count($favorite_songs)) {
        $items = $working_user->format_favorites($favorite_songs);
                $title = '<a href="' . Config::get('web_path') . '/stream.php?action=play_favorite&amp;type=song">' .
                        UI::get_icon('all', T_('Favorite Songs')) . '</a>&nbsp;' .  T_('Favorite Songs');
        show_info_box($title,'your_song',$items);
    }
    else {
        echo "<span class=\"error\">" . T_('Not Enough Data') . "</span>";
    }
    ?>
    </td>
</tr>
</table>
<?php UI::show_box_bottom(); ?>
