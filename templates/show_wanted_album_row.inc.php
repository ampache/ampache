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

<td class="cel_album"><?php echo $libitem->f_link; ?></td>
<td class="cel_artist"><?php echo $libitem->f_artist_link; ?></td>
<td class="cel_year"><?php echo $libitem->year; ?></td>
<td class="cel_user"><?php echo $libitem->f_user; ?></td>
<td class="cel_action">
    <div id="wanted_action_<?php echo $libitem->mbid; ?>">
    <?php $libitem->show_action_buttons(); ?>
    </div>
</td>
