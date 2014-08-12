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

<td class="cel_date"><a href="<?php echo $libitem->url; ?>" target="_blank"><?php echo $libitem->startDate; ?></a></td>
<td class="cel_place"><a href="<?php echo $libitem->venue->url ?>" target="_blank"><?php echo (count($libitem->venue->image) >= 1 && !empty($libitem->venue->image[1])) ? '<img src="' . $libitem->venue->image[1] . '" border="0" />' : ''; ?> <?php echo $libitem->venue->name; ?></a></td>
<td class="cel_location"><?php echo $libitem->venue->location->city; ?>, <?php echo $libitem->venue->location->country; ?></td>
