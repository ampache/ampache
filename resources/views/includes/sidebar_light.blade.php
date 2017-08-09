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
?>
<!-- // TODO Add guest authorization for favorites and upload capability -->
<ul id="sidebar-light">
    <li><img src="{{ url('images/topmenu-artist.png') }}" title="{{ T_('Artists') }}" /><br />{{ T_('Artists') }}</li>
    <li><img src="{{ url('images/topmenu-album.png') }}" title="{{ T_('Albums') }}" /><br />{{ T_('Albums') }}</li>
    <li><img src="{{ url('/images/topmenu-playlist.png') }}" title="{{ T_('Playlists') }}" /><br />{{ T_('Playlists') }}</li>
    <li><img src="{{ url('images/topmenu-tagcloud.png') }}" title="{{ T_('Tag Cloud') }}" /><br />{{ T_('Tag Cloud') }}</li>
    <?php if (Config::get('features.live_stream')) {
    ?>
    <li><img src="{{ url('images/topmenu-radio.png') }}" title="{{ T_('Radio Stations') }}" /><br />{{ T_('Radio') }}</li>
    <?php
} ?>
    <?php if (Config::get('feature.userflags')/* && (Auth::user()->isRegisteredUser()) */)  {
        ?>
    <li><img src="{{ url('/images/topmenu-favorite.png') }}" title="{{ T_('Favorites') }}" /><br />{{ T_('Favorites') }}</li>
    <?php
    } ?>
    <?php if (Config::get('feature.allow_upload') /* && (Auth::user()->isRegisteredUser()) */) {
        ?>
    <li><img src="{{ url('/images/topmenu-upload.png') }}" title="{{ T_('Upload') }}" /><br />{{ T_('Upload') }} ?></a></li>
    <?php
    } ?>
</ul>
