<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>

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
    <?php $libitem->show_action_buttons(); ?>
    </div>
</td>
