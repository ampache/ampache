<?php

declare(strict_types=0);

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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** require@ public/templates/header.inc.php */
/** require@ src/Application/Api/Ajax/Handler/IndexAjaxHandler.php */
/** require@ src/Application/Api/Ajax/Handler/LocalPlayAjaxHandler.php */

$web_path = AmpConfig::get_web_path();

$admin_path = AmpConfig::get_web_path('/admin');

$is_session    = (User::is_registered() && !empty(Core::get_global('user')) && (Core::get_global('user')->id ?? 0) > 0);
$cookie_string = (make_bool(AmpConfig::get('cookie_secure')))
    ? "expires: 30, path: '/', secure: true, samesite: 'Strict'"
    : "expires: 30, path: '/', samesite: 'Strict'";

// strings for the main page and templates
$t_home            = T_('Home');
$t_browse          = T_('Browse');
$t_preferences     = T_('Preferences');
$t_expander        = T_('Expand/Collapse');
$t_songs           = T_('Songs');
$t_artists         = T_('Artists');
$t_a_artists       = T_('Album Artists');
$t_albums          = T_('Albums');
$t_labels          = T_('Labels');
$t_broadcasts      = T_('Broadcasts');
$t_radioStations   = T_('Radio Stations');
$t_radio           = T_('Radio');
$t_podcasts        = T_('Podcasts');
$t_videos          = T_('Videos');
$t_genres          = T_('Genres');
$t_upload          = T_('Upload');
$t_uploads         = T_('Uploads');
$t_dashboards      = T_('Dashboards');
$t_podcastEpisodes = T_('Podcast Episodes');
$t_playlist        = T_('Playlist');
$t_playlists       = T_('Playlists');
$t_smartPlaylists  = T_('Smart Playlists');
$t_smartlists      = T_('Smartlists');
$t_democratic      = T_('Democratic');
$t_random          = T_('Random');
$t_localplay       = T_('Localplay');
$t_search          = T_('Search');
$t_information     = T_('Information');
$t_recent          = T_('Recent');
$t_newest          = T_('Newest');
$t_popular         = T_('Popular');
$t_topRated        = T_('Top Rated');
$t_favorites       = T_('Favorites');
$t_wanted          = T_('Wanted');
$t_shares          = T_('Shares');
$t_statistics      = T_('Statistics');
$t_logout          = T_('Log out'); ?>
<ul id="sidebar-tabs">
<?php if (User::is_registered()) {
    if (!array_key_exists('state', $_SESSION) || !array_key_exists('sidebar_tab', $_SESSION['state'])) {
        $_SESSION['state']['sidebar_tab'] = 'home';
    }
    $className = 'sidebar_' . $_SESSION['state']['sidebar_tab'];

    // List of buttons ( id, title, icon, access level)
    $sidebar_items[] = [
        'id' => 'home',
        'title' => $t_home,
        'icon' => 'headphones',
        'access' => AccessLevelEnum::GUEST
    ];
    if (AmpConfig::get('allow_localplay_playback') && AmpConfig::get('localplay_controller') && Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::GUEST)) {
        $sidebar_items[] = [
            'id' => 'localplay',
            'title' => $t_localplay,
            'icon' => 'volume_up',
            'access' => AccessLevelEnum::GUEST,
        ];
    }
    if ($is_session) {
        $sidebar_items[] = [
            'id' => 'preferences',
            'title' => $t_preferences,
            'icon' => 'page_info',
            'access' => AccessLevelEnum::GUEST,
        ];
    }
    $sidebar_items[] = [
        'id' => 'admin',
        'title' => T_('Admin'),
        'icon' => 'dns',
        'access' => AccessLevelEnum::MANAGER
    ];
    foreach ($sidebar_items as $item) {
        if (Access::check(AccessTypeEnum::INTERFACE, $item['access'])) {
            $active    = ('sidebar_' . $item['id'] == $className) ? ' active' : '';
            $li_params = "id='sb_tab_" . $item['id'] . "' class='sb1" . $active . "'"; ?>
        <li <?php print_r($li_params); ?>>
    <?php print_r(Ajax::button("?page=index&action=sidebar&button=" . $item['id'], $item['icon'], $item['title'], 'sidebar_' . $item['id']));
            if ($item['id'] == $_SESSION['state']['sidebar_tab']) { ?>
            <div id="sidebar-page" class="sidebar-page-float">
                <?php require_once Ui::find_template('sidebar_' . $_SESSION['state']['sidebar_tab'] . '.inc.php'); ?>
            </div>
    <?php } ?>
        </li>
    <?php
        } elseif ($item['title'] === 'Admin' && !AmpConfig::get('simple_user_mode')) {
            echo "<li id='sb_tab_" . $item['id'] . "' class='sb1'>" . Ui::get_material_symbol('lock', T_('Admin Disabled')) . "</li>";
        } ?>
    <?php
    } ?>
    <?php if ($is_session && !empty(Session::get())) { ?>
    <li id="sb_tab_logout" class="sb1">
        <a target="_top" href="<?php echo $web_path; ?>/logout.php?session=<?php echo Session::get(); ?>" id="sidebar_logout" class="nohtml" >
        <?php echo Ui::get_material_symbol('logout', $t_logout); ?>
        </a>
    </li>
    <?php } ?>
<?php
} else { ?>
    <li id="sb_tab_home" class="sb1">
        <div id="sidebar-page" class="sidebar-page-float">
        <?php require_once Ui::find_template('sidebar_home.inc.php'); ?>
        </div>
    </li>
<?php } ?>
</ul>
<script>
    $(function() {
        $(".header").click(function () {

            $header = $(this);
            // getting the next element
            $content = $header.next();
            // open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
            $content.slideToggle(500, function() {
                $header.children(".header-img").toggleClass("expanded collapsed");
                var sbstate = "expanded";
                if ($header.children(".header-img").hasClass("collapsed")) {
                    sbstate = "collapsed";
                }
                Cookies.set('sb_' + $header.children(".header-img").attr('id'), sbstate, {<?php echo $cookie_string; ?>});
            });
        });
    });

    $(document).ready(function() {
        // Get a string of all the cookies.
        var cookieArray = document.cookie.split(";");
        var result = new Array();
        // Create a key/value array with the individual cookies.
        for (var elem in cookieArray) {
            var temp = cookieArray[elem].split("=");
            // We need to trim whitespaces.
            temp[0] = $.trim(temp[0]);
            temp[1] = $.trim(temp[1]);
            // Only take sb_* cookies (= sidebar cookies)
            if (temp[0].substring(0, 3) == "sb_") {
                result[temp[0].substring(3)] = temp[1];
            }
        }
        // Finds the elements and if the cookie is collapsed, it collapsed the found element.
        for (var key in result) {
            if ($("#" + key).length && result[key] == "collapsed") {
                if ($("#" + key).hasClass("expanded")) {
                    $("#" + key).removeClass("expanded");
                    $("#" + key).addClass("collapsed");
                    $("#" + key).parent().next().slideToggle(0);
                }
            }
        }
    });
</script>