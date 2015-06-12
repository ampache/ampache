<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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

/* Create some variables we are going to need */
$web_path = AmpConfig::get('web_path');
$base_url = '?action=set_userflag&userflag_type=' . $userflag->type . '&object_id=' . $userflag->id;
$othering = false;
$flagged = $userflag->get_flag();
?>

<div class="userflag">
<?php
    if ($flagged) {
        echo Ajax::text($base_url . '&userflag=0', '', 'userflag_i_' . $userflag->id . '_' . $userflag->type, '', 'userflag_true');
    } else {
        echo Ajax::text($base_url . '&userflag=1', '', 'userflag_i_' . $userflag->id . '_' . $userflag->type, '', 'userflag_false');
    }
?>
</div>
