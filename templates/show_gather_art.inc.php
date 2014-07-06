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

UI::show_box_top(T_('Art Search'), 'box box_gather_art');
echo "<strong>" . T_('Starting Art Search') . ". . .</strong><br />\n";
echo T_('Searched') . ": <span id=\"count_art_" . $catalog_id . "\">" . T_('None') . "</span><br />";
echo T_('Reading') . ":<span id=\"read_art_$catalog_id\"></span><br />";
echo "<br />\n";
UI::show_box_bottom();
