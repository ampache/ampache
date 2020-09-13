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

<td class="cel_date"><a href="<?php echo $libitem->url; ?>" target="_blank"><?php echo $libitem->startDate; ?></a></td>
<td class="cel_place"><a href="<?php echo $libitem->venue->url ?>" target="_blank"><?php echo (count($libitem->venue->image) >= 1 && !empty($libitem->venue->image[1])) ? '<img src="' . $libitem->venue->image[1] . '"/>' : ''; ?> <?php echo $libitem->venue->name; ?></a></td>
<td class="cel_location"><?php echo $libitem->venue->location->city; ?>, <?php echo $libitem->venue->location->country; ?></td>
