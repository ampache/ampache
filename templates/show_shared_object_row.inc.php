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

<td class="cel_object"><?php echo $libitem->f_object_link; ?></td>
<td class="cel_object_type"><?php echo $libitem->object_type; ?></td>
<td class="cel_user"><?php echo $libitem->f_user; ?></td>
<td class="cel_creation_date"><?php echo $libitem->f_creation_date; ?></td>
<td class="cel_lastvisit_date"><?php echo $libitem->f_lastvisit_date; ?></td>
<td class="cel_counter"><?php echo $libitem->counter; ?></td>
<td class="cel_max_counter"><?php echo $libitem->max_counter; ?></td>
<td class="cel_allow_stream"><?php echo $libitem->f_allow_stream; ?></td>
<td class="cel_allow_download"><?php echo $libitem->f_allow_download; ?></td>
<td class="cel_expire"><?php echo $libitem->expire_days; ?></td>
<td class="cel_public_url"><?php echo $libitem->public_url; ?></td>
<td class="cel_action">
    <div id="share_action_<?php echo $libitem->id; ?>">
    <?php
        $libitem->show_action_buttons();
    ?>
    </div>
</td>
