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
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Module\Util\Upload;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Tmp_Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;
use Ampache\Repository\PrivateMessageRepositoryInterface;

global $dic;
$web_path          = AmpConfig::get_web_path('/client');
$admin_path        = AmpConfig::get_web_path('/admin');
$access100         = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
$access25          = ($access100 || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER));
$site_lang         = AmpConfig::get('lang', 'en_US');
$site_title        = scrub_out(AmpConfig::get('site_title'));
$site_social       = AmpConfig::get('sociable');
$site_ajax         = AmpConfig::get('ajax_load');
$htmllang          = str_replace("_", "-", $site_lang);
$_SESSION['login'] = false;
$current_user      = Core::get_global('user');
$logo_url          = ($current_user instanceof User && Preference::get_by_user($current_user->getId(), 'custom_logo_user'))
    ? $current_user->get_avatar()['url_medium'] ?? Ui::get_logo_url()
    : Ui::get_logo_url();
$is_session        = (User::is_registered() && !empty($current_user) && ($current_user->id ?? 0) > 0);
$allow_upload      = $access25 && Upload::can_upload($current_user);

$count_temp_playlist = (!empty($current_user->playlist) && $current_user->playlist instanceof Tmp_Playlist)
    ? count($current_user->playlist->get_items())
    : 0;
// strings for the main page and templates
$t_home      = T_('Home');
$t_play      = T_('Play');
$t_artists   = T_('Artists');
$t_albums    = T_('Albums');
$t_playlists = T_('Playlists');
$t_genres    = T_('Genres');
$t_favorites = T_('Favorites');
$t_upload    = T_('Upload');
$albumString = (AmpConfig::get('album_group'))
    ? 'album'
    : 'album_disk';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo is_rtl($site_lang) ? 'rtl' : 'ltr'; ?>">
    <head>
        <!-- Propelled by Ampache | ampache.org -->
        <link rel="search" type="application/opensearchdescription+xml" title="<?php echo $site_title; ?>" href="<?php echo $web_path; ?>/opensearch.php?action=descriptor">
        <?php if (AmpConfig::get('use_rss')) { ?>
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Now Playing'); ?>" href="<?php echo $web_path; ?>/rss.php?type=<?php echo RssFeedTypeEnum::NOW_PLAYING->value; ?>">
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Recently Played'); ?>" href="<?php echo $web_path; ?>/rss.php?type=<?php echo RssFeedTypeEnum::RECENTLY_PLAYED->value; ?>">
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Newest Albums'); ?>" href="<?php echo $web_path; ?>/rss.php?type=<?php echo RssFeedTypeEnum::LATEST_ALBUM->value; ?>">
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Newest Artists'); ?>" href="<?php echo $web_path; ?>/rss.php?type=<?php echo RssFeedTypeEnum::LATEST_ARTIST->value; ?>">
        <?php if ($site_social) { ?>
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Newest Shouts'); ?>" href="<?php echo $web_path; ?>/rss.php?type=<?php echo RssFeedTypeEnum::LATEST_SHOUT->value; ?>">
        <?php }
        } ?>
        <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=<?php echo AmpConfig::get('site_charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $site_title; ?></title>

        <?php require_once Ui::find_template('stylesheets.inc.php'); ?>
        <?php require_once Ui::find_template('scripts.inc.php'); ?>

        <?php if ($site_ajax) {
            $iframed = true;
            require_once Ui::find_template('show_html5_player_headers.inc.php');
        } ?>
    </head>
    <body id="main-page">
        <div id="aslideshow">
            <div id="aslideshow_container">
                <div id="fslider"></div>
                <div id="fslider_script"></div>
            </div>
        </div>

        <!-- rfc3514 implementation -->
        <div id="rfc3514" style="display:none;">0x0</div>
        <div id="reloader" style="display:none;"></div>
        <div id="notification" class="notification-out"><?php echo Ui::get_material_symbol('info', T_('Information')); ?><span id="notification-content"></span></div>
        <div id="maincontainer">
            <div id="header" class="header-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>"><!-- This is the header -->
                <h1 id="headerlogo">
                  <a href="<?php echo $web_path; ?>/index.php">
                    <img src="<?php echo $logo_url; ?>" title="<?php echo $site_title; ?>" alt="<?php echo $site_title; ?>">
                  </a>
                </h1>
                <div id="headerbox">
                    <?php Ui::show_box_top('', 'box box_headerbox');
require_once Ui::find_template('show_search_bar.inc.php');
if ($is_session) {
    require_once Ui::find_template('show_playtype_switch.inc.php'); ?>
                        <span id="loginInfo">
                            <a href="<?php echo $web_path; ?>/stats.php?action=show_user&user_id=<?php echo $current_user?->getId(); ?>"><?php echo $current_user?->fullname; ?></a>
                        <?php if ($site_social) { ?>
                            <a href="<?php echo $web_path; ?>/browse.php?action=pvmsg" title="<?php echo T_('New messages'); ?>">(<?php
                                echo $dic->get(PrivateMessageRepositoryInterface::class)->getUnreadCount($current_user); ?>)</a>
                        <?php } ?>
                        </span>
                    <?php
} elseif (AmpConfig::get('show_header_login')) { ?>
                        <span id="loginInfo">
                            <a href="<?php echo $web_path; ?>/login.php?force_display=1" class="nohtml"><?php echo T_('Login'); ?></a>
                        <?php if (AmpConfig::get('allow_public_registration') && Mailer::is_mail_enabled()) { ?>
                                / <a href="<?php echo $web_path; ?>/register.php" class="nohtml"><?php echo T_('Register'); ?></a>
                        <?php } ?>
                        </span>
<?php } ?>
<?php if ($site_ajax) { ?>
                        <div id="rightbar-minimize">
                            <a href="javascript:ToggleRightbarVisibility();"><?php echo Ui::get_material_symbol('dock_to_left', T_('Show/Hide Playlist')); ?></a>
                        </div>
<?php } ?>
<?php Ui::show_box_bottom(); ?>
                </div> <!-- End headerbox -->
            </div><!-- End header -->

<?php if (AmpConfig::get('topmenu')) { ?>
            <div id="topmenu_container" class="topmenu_container-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>">
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/index.php">
                        <?php echo Ui::get_image('topmenu-home', $t_home); ?>
                        <span><?php echo $t_home; ?></span>
                    </a>
                </div>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/browse.php?action=album_artist">
                        <?php echo Ui::get_image('topmenu-artist', $t_artists); ?>
                        <span><?php echo $t_artists; ?></span>
                    </a>
                </div>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/browse.php?action=playlist">
                        <?php echo Ui::get_image('topmenu-playlist', $t_playlists); ?>
                        <span><?php echo $t_playlists; ?></span>
                    </a>
                </div>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/browse.php?action=tag&type=artist">
                        <?php echo Ui::get_image('topmenu-tagcloud', $t_genres); ?>
                        <span><?php echo $t_genres; ?></span>
                    </a>
                </div>

                <?php if (AmpConfig::get('ratings') && $access25) { ?>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/stats.php?action=userflag_<?php echo $albumString; ?>">
                        <?php echo Ui::get_image('topmenu-favorite', $t_favorites); ?>
                        <span><?php echo $t_favorites; ?></span>
                    </a>
                </div>
                <?php } ?>
                <?php if ($allow_upload) { ?>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/upload.php">
                        <?php echo Ui::get_image('topmenu-upload', $t_upload); ?>
                        <span><?php echo $t_upload; ?></span>
                    </a>
                </div>
                <?php } ?>
            </div>
<?php }
$sidebarLight = AmpConfig::get('sidebar_light');
$hideSwitcher = AmpConfig::get('sidebar_hide_switcher', false);
$isCollapsed  = (
    ($sidebarLight && $hideSwitcher) ||
    ($sidebarLight && (!isset($_COOKIE['sidebar_state']))) ||
    ($sidebarLight && (isset($_COOKIE['sidebar_state']) && $_COOKIE['sidebar_state'] != "expanded")) ||
    (isset($_COOKIE['sidebar_state']) && $_COOKIE['sidebar_state'] == "collapsed")
); ?>
            <div id="sidebar" class="sidebar-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>">
            <?php if (!$hideSwitcher) {
                echo '<div id="sidebar-header" class="' . ($isCollapsed ? 'sidebar-header-collapsed' : '') . '" >';
                echo '<span id="sidebar-header-content"></span>';
                echo '</div>';
            } ?>
                <div id="sidebar-content" class="<?php echo $isCollapsed ? 'sidebar-content-collapsed' : ''; ?>" >
                    <?php require_once Ui::find_template('sidebar.inc.php'); ?>
                </div>
                <div id="sidebar-content-light" class="<?php echo $isCollapsed ? 'sidebar-content-light-collapsed' : ''; ?>" >
                    <?php require_once Ui::find_template('sidebar.light.inc.php'); ?>
                </div>
            </div>

            <div id="rightbar" class="rightbar-fixed">
                <?php require_once Ui::find_template('rightbar.inc.php'); ?>
            </div>

            <!-- Tiny little div, used to cheat the system -->
            <div id="ajax-loading"><?php echo T_('Loading') . ' . . .'; ?></div>
            <div id="util_div" style="display:none;"></div>
            <iframe name="util_iframe" id="util_iframe" style="display:none;" src="<?php echo $web_path; ?>/util.php"></iframe>

            <div id="content" class="content-<?php echo AmpConfig::get('ui_fixed') ? (AmpConfig::get('topmenu') ? 'fixed-topmenu' : 'fixed') : 'float'; ?> <?php echo (!$count_temp_playlist || AmpConfig::get('play_type') == 'localplay') ? '' : 'content-right-wild';
echo $isCollapsed ? ' content-left-wild' : ''; ?>">

                <?php if ($access100) {
                    echo '<div id=update_notify>';
                    if (!AmpConfig::get('hide_ampache_messages', false)) {
                        AutoUpdate::show_ampache_message();
                    }
                    if (AmpConfig::get('autoupdate')) {
                        $current_version = AutoUpdate::get_current_version();
                        $latest_version  = AutoUpdate::get_latest_version();
                        if (
                            (
                                !empty($latest_version) &&
                                $current_version !== $latest_version
                            ) ||
                            AutoUpdate::is_update_available()
                        ) {
                            AutoUpdate::show_new_version();
                            echo '<br />';
                        }
                    }

                    if (Plugin::is_update_available()) {
                        Plugin::show_update_available();
                        echo '<br>';
                    }

                    if (AmpConfig::get('int_config_version') > AmpConfig::get('config_version')) { ?>
                            <div class="fatalerror">
                                <?php echo T_('Your Ampache config file is out of date!'); ?>
                                <br>
                                <a class="nohtml" href="<?php echo $admin_path; ?>/system.php?action=write_config"><?php echo T_('Update your current config file automatically'); ?></a> |
                                <a class="nohtml" href="<?php echo $admin_path; ?>/system.php?action=generate_config"><?php echo T_('Download a copy of the new version'); ?></a>
                                <br>
                            </div>
                <?php }
                    echo '</div>';
                }
if (AmpConfig::get("ajax_load")) {
    require Ui::find_template('show_web_player_embedded.inc.php');
} ?>
                <div id="guts">
