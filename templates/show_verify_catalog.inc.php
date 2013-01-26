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

UI::show_box_top(T_('Verify Catalog'), 'box box_verify_catalog');
/* HINT: Catalog Name */
printf(T_('Updating the %s catalog'), "<strong>[ $this->name ]</strong>");
echo "<br />\n";
printf(T_ngettext('%d item found checking tag information', '%d items found checking tag information', $number), $number);
echo "<br />\n\n";
echo T_('Verified') . ': <span id="verify_count_' . $this->id . '">' . $catalog_verify_found . '</span><br />';
echo T_('Reading') . ': <span id="verify_dir_' . $this->id . '">' . $catalog_verify_directory . '</span><br />';
UI::show_box_bottom();
?>
