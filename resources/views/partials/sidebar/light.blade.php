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
    <li><img src="{{ asset('images/topmenu-artist.png') }}" title="{{ 'Artists' }}" /><br />{{ 'Artists' }}</li>
    <li><img src="{{ asset('images/topmenu-album.png') }}" title="{{ 'Albums' }}" /><br />{{ 'Albums' }}</li>
    <li><img src="{{ asset('/images/topmenu-playlist.png') }}" title="{{ 'Playlists' }}" /><br />{{ 'Playlists' }}</li>
    <li><img src="{{ asset('images/topmenu-tagcloud.png') }}" title="{{ 'Tag Cloud' }}" /><br />{{ 'Tag Cloud' }}</li>
    <?php if (config('features.live_stream')) {
    ?>
    <li><img src="{{ url('images/topmenu-radio.png') }}" title="{{ 'Radio Stations' }}" /><br />{{ 'Radio' }}</li>
    <?php
} ?>
    <?php if (config('feature.userflags')/* && (Auth::user()->isRegisteredUser()) */) {
        ?>
    <li><img src="{{ url('/images/topmenu-favorite.png') }}" title="{{ 'Favorites' }}" /><br />{{ 'Favorites' }}</li>
    <?php
    } ?>
    <?php if (config('feature.allow_upload') /* && (Auth::user()->isRegisteredUser()) */) {
        ?>
    <li><img src="{{ url('/images/topmenu-upload.png') }}" title="{{ 'Upload' }}" /><br />{{ 'Upload' }} ?></a></li>
    <?php
    } ?>
</ul>
