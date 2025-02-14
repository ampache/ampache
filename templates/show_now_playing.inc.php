<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

/**
 * This is the Now Playing container, it holds the master div for Now Playing
 * and loops through what's current playing as passed and includes
 * the now_playing_row's This will display regardless, but potentially
 * goes all ajaxie if you've got javascript on
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

/** @var list<array{media: Media, client: User, agent: string,}> $results */

if (count($results)) {
    $user     = (!empty(Core::get_global('user')))
        ? Core::get_global('user')
        : new User(-1);
    $catalogs = User::get_user_catalogs($user->id);
    $rss_link = (AmpConfig::get('use_rss')) ? '&nbsp' . Ui::getRssLink(RssFeedTypeEnum::NOW_PLAYING) : '';
    $refresh  = "&nbsp" . Ajax::button('?page=index&action=refresh_now_playing', 'refresh', T_('Refresh'), 'refresh_now_playing', 'box_np');
    Ui::show_box_top(T_('Now Playing') . $rss_link . $refresh, 'box_np');

    $t_username        = T_('Username');
    $t_song            = T_('Song');
    $t_video           = T_('Video');
    $t_album           = T_('Album');
    $t_artist          = T_('Artist');
    $t_year            = T_('Year');
    $t_genres          = T_('Genres');
    $t_similar_artists = T_('Similar Artists');
    $t_loading         = T_('Loading...');
    $t_similar_songs   = T_('Similar Songs');

    foreach ($results as $item) {
        /** @var Song|Video $media */
        $media = $item['media'];
        if (!is_object($media) || !in_array($media->catalog, $catalogs)) {
            continue;
        }

        $np_user = $item['client'];
        $np_user->format();
        if (!$np_user->fullname) {
            $np_user->fullname = "Ampache User";
        }

        $agent = $item['agent'];
        echo "<div class=\"np_row\">";
        if (get_class($media) == Song::class) {
            require Ui::find_template('show_now_playing_row.inc.php');
        } elseif (get_class($media) == Video::class) {
            require Ui::find_template('show_now_playing_video_row.inc.php');
        }
        echo "</div>";
    }
    Ui::show_box_bottom();
}
