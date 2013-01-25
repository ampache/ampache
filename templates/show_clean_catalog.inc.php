<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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

show_box_top();
/* HINT: Catalog Name */
printf(T_('Cleaning the %s Catalog'), "<strong>[ $this->name ]</strong>");
echo "...<br />";
echo T_('Checking') . ': <span id="clean_count_' . $this->id . '"></span><br />';
echo T_('Reading') . ':<span id="clean_dir_' . $this->id . '"></span><br />';
show_box_bottom();
?>
