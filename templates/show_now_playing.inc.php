<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This is the now playing container, it holds the master div for now playing
 * and loops through what's current playing as passed and includes
 * the now_playing_row's This will display regardless, but potentially
 * goes all ajaxie if you've got javascript on
 */

if (count($results)) {
    $link = AmpConfig::get('use_rss') ? ' ' . Ampache_RSS::get_display('nowplaying') : ''; ?>
<?php UI::show_box_top(T_('Now Playing') . $link); ?>
<?php
foreach ($results as $item) {
        $media   = $item['media'];
        $np_user = $item['client'];
        $np_user->format();
        $agent = $item['agent'];

        /* If we've gotten a non-song object just skip this row */
        if (!is_object($media)) {
            continue;
        }
        if (!$np_user->fullname) {
            $np_user->fullname = "Ampache User";
        } ?>
<div class="np_row">
<?php
if (strtolower(get_class($media)) == 'song') {
            require AmpConfig::get('prefix') . UI::find_template('show_now_playing_row.inc.php');
        } else {
            require AmpConfig::get('prefix') . UI::find_template('show_now_playing_video_row.inc.php');
        } ?>
</div>
<?php
    } // end foreach
?>
<?php UI::show_box_bottom(); ?>
<?php
} // end if count results?>
