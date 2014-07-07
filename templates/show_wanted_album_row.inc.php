<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

<td class="cel_album"><?php echo $libitem->f_name_link; ?></td>
<td class="cel_artist"><?php echo $libitem->f_artist_link; ?></td>
<td class="cel_year"><?php echo $libitem->year; ?></td>
<td class="cel_user"><?php echo $libitem->f_user; ?></td>
<td class="cel_action">
    <div id="wanted_action_<?php echo $libitem->mbid; ?>">
    <?php
        $libitem->show_action_buttons();
    ?>
    </div>
</td>
