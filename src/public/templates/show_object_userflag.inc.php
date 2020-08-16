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
 */

/* Create some variables we are going to need */
$web_path = AmpConfig::get('web_path');
$base_url = '?action=set_userflag&userflag_type=' . $userflag->type . '&object_id=' . $userflag->id;
$othering = false;
$flagged  = (!$userflag->get_flag()) ? false : true; ?>
<div class="userflag">
<?php
    if ($flagged) {
        echo Ajax::text($base_url . '&userflag=0', '', 'userflag_i_' . $userflag->id . '_' . $userflag->type, '', 'userflag_true');
    } else {
        echo Ajax::text($base_url . '&userflag=1', '', 'userflag_i_' . $userflag->id . '_' . $userflag->type, '', 'userflag_false');
    } ?>
</div>
