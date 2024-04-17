<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\Api\Ajax;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\System\Core;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;
use Ampache\Repository\PrivateMessageRepositoryInterface;

$web_path          = (string)AmpConfig::get('web_path', '');
$access100         = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
$access25          = ($access100 || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER));
$site_lang         = AmpConfig::get('lang', 'en_US');
$site_title        = scrub_out(AmpConfig::get('site_title'));
$site_social       = AmpConfig::get('sociable');
$site_ajax         = AmpConfig::get('ajax_load');
$htmllang          = str_replace("_", "-", $site_lang);
$_SESSION['login'] = false;
$current_user      = Core::get_global('user');
$is_session        = (User::is_registered() && !empty($current_user) && ($current_user->id ?? 0) > 0);
$allow_upload      = $access25 && Upload::can_upload($current_user);
$cookie_string     = (make_bool(AmpConfig::get('cookie_secure')))
    ? "expires: 30, path: '/', secure: true, samesite: 'Strict'"
    : "expires: 30, path: '/', samesite: 'Strict'";

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
global $dic;

$ajaxUriRetriever = $dic->get(AjaxUriRetrieverInterface::class);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo is_rtl($site_lang) ? 'rtl' : 'ltr'; ?>">
    <head>
        <!-- Propelled by Ampache | ampache.org -->
        <link rel="search" type="application/opensearchdescription+xml" title="<?php echo $site_title; ?>" href="<?php echo $web_path; ?>/search.php?action=descriptor">
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

        <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/modules/jquery-ui-ampache/jquery-ui.min.css" type="text/css" media="screen">
        <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/components/tag-it/css/jquery.tagit.css" type="text/css" media="screen">
        <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/modules/rhinoslider/css/rhinoslider-1.05.css" type="text/css" media="screen">
        <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/components/datetimepicker/jquery.datetimepicker.min.css" type="text/css" media="screen">
        <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/components/jquery-contextmenu/jquery.contextMenu.min.css" type="text/css" media="screen">
        <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/components/filepond/filepond.min.css" type="text/css" media="screen">

        <script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jquery-ui/jquery-ui.min.js"></script>
        <script src="<?php echo $web_path; ?>/lib/components/prettyphoto/js/jquery.prettyPhoto.min.js"></script>
        <script src="<?php echo $web_path; ?>/lib/components/tag-it/js/tag-it.min.js"></script>
        <script src="<?php echo $web_path; ?>/lib/components/js-cookie/js.cookie.js"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jscroll/jquery.jscroll.min.js" defer></script>
        <script src="<?php echo $web_path; ?>/lib/components/jquery-qrcode/jquery.qrcode.min.js" defer></script>
        <script src="<?php echo $web_path; ?>/lib/modules/rhinoslider/js/rhinoslider-1.05.min.js" defer></script>
        <script src="<?php echo $web_path; ?>/lib/components/datetimepicker/jquery.datetimepicker.full.min.js" defer></script>
        <script src="<?php echo $web_path; ?>/lib/components/filepond/filepond.min.js"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jquery-contextmenu/jquery.contextMenu.js"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/base.js" defer></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" defer></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/tools.js" defer></script>
        <?php if (file_exists(__DIR__ . '/../lib/javascript/custom.js')) { ?>
            <script src="<?php echo $web_path; ?>/lib/javascript/custom.js" defer></script>
        <?php } ?>
        <script>
            $(document).ready(function(){
                $("a[rel^='prettyPhoto']").prettyPhoto({
                    social_tools: false,
                    deeplinking: false
                });
                <?php if (AmpConfig::get('geolocation')) { ?>
                    geolocate_user();
                <?php } ?>
            });

            // Using the following workaround to set global variable available from any javascript script.
            var jsAjaxUrl = "<?php echo $ajaxUriRetriever->getAjaxUri(); ?>";
            var jsWebPath = "<?php echo $web_path; ?>";
            var jsAjaxServer = "<?php echo $ajaxUriRetriever->getAjaxServerUri(); ?>";
            var jsSiteTitle = "<?php echo addslashes(AmpConfig::get('site_title', '')); ?>";
            var jsHomeTitle = "<?php echo addslashes(T_('Home')); ?>";
            var jsUploadTitle = "<?php echo addslashes(T_('Upload')); ?>";
            var jsLocalplayTitle = "<?php echo addslashes(T_('Localplay')); ?>";
            var jsRandomTitle = "<?php echo addslashes(T_('Random Play')); ?>";
            var jsPlaylistTitle = "<?php echo addslashes(T_('Playlist')); ?>";
            var jsSmartPlaylistTitle = "<?php echo addslashes(T_('Smart Playlist')); ?>";
            var jsSearchTitle = "<?php echo addslashes(T_('Search')); ?>";
            var jsPreferencesTitle = "<?php echo addslashes(T_('Preferences')); ?>";
            var jsAdminCatalogTitle = "<?php echo addslashes(T_('Catalogs')); ?>";
            var jsAdminUserTitle = "<?php echo addslashes(T_('User Tools')); ?>";
            var jsAdminMailTitle = "<?php echo addslashes(T_('E-mail Users')); ?>";
            var jsAdminManageAccessTitle = "<?php echo addslashes(T_('Access Control')); ?>";
            var jsAdminPreferencesTitle = "<?php echo addslashes(T_('Server Config')); ?>";
            var jsAdminManageModulesTitle = "<?php echo addslashes(T_('Modules')); ?>";
            var jsAdminLicenseTitle = "<?php echo addslashes(T_('Media Licenses')); ?>";
            var jsAdminFilterTitle = "<?php echo addslashes(T_('Catalog Filters')); ?>";
            var jsBrowseMusicTitle = "<?php echo addslashes(T_('Browse')); ?>";
            var jsAlbumTitle = "<?php echo addslashes(T_('Album')); ?>";
            var jsArtistTitle = "<?php echo addslashes(T_('Artist')); ?>";
            var jsStatisticsTitle = "<?php echo addslashes(T_('Statistics')); ?>";
            var jsSongTitle = "<?php echo addslashes(T_('Song')); ?>";
            var jsDemocraticTitle = "<?php echo addslashes(T_('Democratic')); ?>";
            var jsLabelsTitle = "<?php echo addslashes(T_('Labels')); ?>";
            var jsDashboardTitle = "<?php echo addslashes(T_('Dashboards')); ?>";
            var jsPodcastTitle = "<?php echo addslashes(T_('Podcast')); ?>";
            var jsPodcastEpisodeTitle = "<?php echo addslashes(T_('Podcast Episode')); ?>";
            var jsRadioTitle = "<?php echo addslashes(T_('Radio Stations')); ?>";
            var jsVideoTitle = "<?php echo addslashes(T_('Video')); ?>";
            var jsSaveTitle = "<?php echo addslashes(T_('Save')); ?>";
            var jsCancelTitle = "<?php echo addslashes(T_('Cancel')); ?>";
        </script>

        <?php if ($site_ajax) {
            $iframed = true; ?>
        <script src="<?php echo $web_path; ?>/lib/javascript/dynamicpage.js"></script>
        <?php require_once Ui::find_template('show_html5_player_headers.inc.php'); ?>
        <script>
            function NavigateTo(url)
            {
                window.location.hash = url.substring(jsWebPath.length + 1);
            }

            function getCurrentPage()
            {
                if (window.location.hash.length > 0) {
                    var wpage = window.location.hash.substring(1);
                    if (wpage !== 'prettyPhoto') {
                        return btoa(wpage);
                    } else {
                        return "";
                    }
                }

                return btoa(window.location.href.substring(jsWebPath.length + 1));
            }
            $(document).ajaxSuccess(function() {
                var title = window.location.hash.replace(/[#$&=_]/g, '');
                title = title.replace(/\?.*/gi, '');
                title = title.replace(/\b(?:action|type|tab|.php|\[\]|[a-z]* id|[0-9]*)\b/gi, '');
                title = title.trim();
                if (title === 'index') {
                    document.title = jsSiteTitle + ' | ' + jsHomeTitle;
                } else if (title === 'browse') {
                    document.title = jsSiteTitle + ' | ' + jsBrowseMusicTitle;
                } else if (title === 'albums') {
                    document.title = jsSiteTitle + ' | ' + jsAlbumTitle;
                } else if (title === 'artists') {
                    document.title = jsSiteTitle + ' | ' + jsArtistTitle;
                } else if (title === 'song') {
                    document.title = jsSiteTitle + ' | ' + jsSongTitle;
                } else if (title === 'democratic') {
                    document.title = jsSiteTitle + ' | ' + jsDemocraticTitle;
                } else if (title === 'labels') {
                    document.title = jsSiteTitle + ' | ' + jsLabelsTitle;
                } else if (title === 'mashup') {
                    document.title = jsSiteTitle + ' | ' + jsDashboardTitle;
                } else if (title === 'podcast') {
                    document.title = jsSiteTitle + ' | ' + jsPodcastTitle;
                } else if (title === 'podcast_episode') {
                    document.title = jsSiteTitle + ' | ' + jsPodcastEpisodeTitle;
                } else if (title === 'radio') {
                    document.title = jsSiteTitle + ' | ' + jsRadioTitle;
                } else if (title === 'video' || title === 'tvshow_seasons' || title === 'tvshows') {
                    document.title = jsSiteTitle + ' | ' + jsVideoTitle;
                } else if (title === 'localplay') {
                    document.title = jsSiteTitle + ' | ' + jsLocalplayTitle;
                } else if (title === 'random') {
                    document.title = jsSiteTitle + ' | ' + jsRandomTitle;
                } else if (title === 'playlist') {
                    document.title = jsSiteTitle + ' | ' + jsPlaylistTitle;
                } else if (title === 'smartplaylist') {
                    document.title = jsSiteTitle + ' | ' + jsSmartPlaylistTitle;
                } else if (title === 'search') {
                    document.title = jsSiteTitle + ' | ' + jsSearchTitle;
                } else if (title === 'preferences') {
                    document.title = jsSiteTitle + ' | ' + jsPreferencesTitle;
                } else if (title === 'stats') {
                    document.title = jsSiteTitle + ' | ' + jsStatisticsTitle;
                } else if (title === 'upload') {
                    document.title = jsSiteTitle + ' | ' + jsUploadTitle;
                } else if (title === 'admin/catalog' || title === 'admin/index') {
                    document.title = jsSiteTitle + ' | ' + jsAdminCatalogTitle;
                } else if (title === 'admin/users') {
                    document.title = jsSiteTitle + ' | ' + jsAdminUserTitle;
                } else if (title === 'admin/mail') {
                    document.title = jsSiteTitle + ' | ' + jsAdminMailTitle;
                } else if (title === 'admin/access') {
                    document.title = jsSiteTitle + ' | ' + jsAdminManageAccessTitle;
                } else if (title === 'admin/preferences' || title === 'admin/system') {
                    document.title = jsSiteTitle + ' | ' + jsAdminPreferencesTitle;
                } else if (title === 'admin/modules') {
                    document.title = jsSiteTitle + ' | ' + jsAdminManageModulesTitle;
                } else if (title === 'admin/filter') {
                    document.title = jsSiteTitle + ' | ' + jsAdminFilterTitle;
                } else if (title === 'admin/license') {
                    document.title = jsSiteTitle + ' | ' + jsAdminLicenseTitle;
                } else {
                    document.title = jsSiteTitle;
                }
            });
        </script>
        <?php
        } else { ?>
        <script>
            function NavigateTo(url)
            {
                window.location.href = url;
            }

            function getCurrentPage()
            {
                return btoa(window.location.href);
            }
        </script>
        <?php } ?>
        <script>
            $.widget( "custom.catcomplete", $.ui.autocomplete, {
                _renderItem: function( ul, item ) {
                        var itemhtml = "";
                        if (item.link !== '') {
                            itemhtml += "<a href='" + item.link + "'>";
                        } else {
                            itemhtml += "<a>";
                        }
                        if (item.image !== '') {
                            itemhtml += "<img src='" + item.image + "' class='searchart' alt=''>";
                        }
                        itemhtml += "<span class='searchitemtxt'>" + item.label + ((item.rels === '') ? "" : " - " + item.rels) + "</span>";
                        itemhtml += "</a>";

                        return $( "<li class='ui-menu-item'>" )
                            .data("ui-autocomplete-item", item)
                            .append( itemhtml + "</li>")
                            .appendTo( ul );
                },
                _renderMenu: function( ul, items ) {
                    var that = this, currentType = "";
                    $.each( items, function( index, item ) {
                        if (item.type !== currentType) {
                            $( "<li class='ui-autocomplete-category'>")
                                .data("ui-autocomplete-item", item)
                                .append( item.type + "</li>" )
                                .appendTo( ul );

                            currentType = item.type;
                        }
                        that._renderItem( ul, item );
                    });
                }
            });

            $(function() {
                var minSearchChars = 2;
                $( "#searchString" )
                    // don't navigate away from the field on tab when selecting an item
                    .bind( "keydown", function( event ) {
                        if ( event.keyCode === $.ui.keyCode.TAB && $( this ).data( "custom-catcomplete" ).widget().is(":visible") ) {
                            event.preventDefault();
                        }
                    })
                    // reopen previous search results
                    .bind( "click", function( event ) {
                        if ($( this ).val().length >= minSearchChars) {
                            $( this ).data( "custom-catcomplete" ).search();
                        }
                    })
                    .catcomplete({
                        source: function( request, response ) {
                            $.getJSON( jsAjaxUrl, {
                                page: 'search',
                                action: 'search',
                                target: $('#searchStringRule').val(),
                                search: request.term,
                                xoutput: 'json'
                            }, response );
                        },
                        search: function() {
                            // custom minLength
                            if ($( this ).val().length < minSearchChars) {
                                return false;
                            }
                        },
                        focus: function() {
                            // prevent value inserted on focus
                            return false;
                        },
                        select: function( event, ui ) {
                            if (event.keyCode === $.ui.keyCode.ENTER) {
                                NavigateTo(ui.item.link);
                            }

                            return false;
                        }
                    });
            });
        </script>
        <script>
            var lastaction = new Date().getTime();
            var refresh_slideshow_interval=<?php if (Preference::exists('flickr_api_key')) {
                echo AmpConfig::get('slideshow_time');
            } else {
                echo 0;
            } ?>;
            var iSlideshow = null;
            var tSlideshow = null;
            function init_slideshow_check()
            {
                if (refresh_slideshow_interval > 0) {
                    if (tSlideshow != null) {
                        clearTimeout(tSlideshow);
                    }
                    tSlideshow = window.setTimeout(function(){init_slideshow_refresh();}, refresh_slideshow_interval * 1000);
                }
            }
            function swap_slideshow()
            {
                if (iSlideshow == null) {
                    init_slideshow_refresh();
                } else {
                    stop_slideshow();
                }
            }
            function init_slideshow_refresh()
            {
                if ($("#webplayer").is(":visible")) {
                    clearTimeout(tSlideshow);
                    tSlideshow = null;

                    $("#aslideshow").height($(document).height())
                      .css({'display': 'inline'});

                    iSlideshow = true;
                    refresh_slideshow();
                }
            }
            function refresh_slideshow()
            {
                if (iSlideshow != null) {
                    <?php echo Ajax::action('?page=index&action=slideshow', ''); ?>;
                } else {
                    init_slideshow_check();
                }
            }
            function stop_slideshow()
            {
                if (iSlideshow != null) {
                    iSlideshow = null;
                    $("#aslideshow").css({'display': 'none'});
                }
            }
            function update_action()
            {
                lastaction = new Date().getTime();
                stop_slideshow();
                init_slideshow_check();
            }
            $(document).mousemove(function(e) {
                if (iSlideshow == null) {
                    update_action();
                }
            });
            $(document).ready(function() {
                init_slideshow_check();
            });
        </script>
    </head>
    <body id="main-page">
        <div id="aslideshow">
            <div id="aslideshow_container">
                <div id="fslider"></div>
                <div id="fslider_script"></div>
            </div>
        </div>
        <script>
            $("#aslideshow").click(function(e) {
                if (!$(e.target).hasClass('rhino-btn')) {
                    update_action();
                }
            });
        </script>

        <?php if (AmpConfig::get('libitem_contextmenu')) { ?>
        <script>
            function libitem_action(item, action)
            {
                var iinfo = item.attr('id').split('_', 2);
                var object_type = iinfo[0];
                var object_id = iinfo[1];

                if (typeof action !== 'undefined' && action !== '') {
                    ajaxPut(jsAjaxUrl + action + '&object_type=' + object_type + '&object_id=' + object_id);
                } else {
                    showPlaylistDialog(this, object_type, object_id);
                }
            }

            $.contextMenu({
                selector: ".libitem_menu",
                items: {
                    play: {name: "<?php echo $t_play; ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay'); }},
                    play_next: {name: "<?php echo T_('Play next'); ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay&playnext=true'); }},
                    play_last: {name: "<?php echo T_('Play last'); ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay&append=true'); }},
                    add_tmp_playlist: {name: "<?php echo T_('Add to Temporary Playlist'); ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?action=basket'); }},
                    add_playlist: {name: "<?php echo T_('Add to playlist'); ?>", callback: function(key, opt){ libitem_action(opt.$trigger, ''); }}
                }
            });
        </script>
        <?php } ?>

        <!-- rfc3514 implementation -->
        <div id="rfc3514" style="display:none;">0x0</div>
        <div id="notification" class="notification-out"><?php echo Ui::get_material_symbol('info', T_('Information')); ?><span id="notification-content"></span></div>
        <div id="maincontainer">
            <div id="header" class="header-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>"><!-- This is the header -->
                <h1 id="headerlogo">
                  <a href="<?php echo $web_path; ?>/index.php">
                    <img src="<?php echo Ui::get_logo_url(); ?>" title="<?php echo $site_title; ?>" alt="<?php echo $site_title; ?>">
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
                            <a href="<?php echo $web_path; ?>/browse.php?action=pvmsg" title="<?php echo T_('New messages'); ?>">(<?php global $dic;
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
            <?php } ?>
            <?php $sidebarLight = AmpConfig::get('sidebar_light');
$isCollapsed                    = (($sidebarLight && (!isset($_COOKIE['sidebar_state']))) ||
            ($sidebarLight && (isset($_COOKIE['sidebar_state']) && $_COOKIE['sidebar_state'] != "expanded")) ||
            (isset($_COOKIE['sidebar_state']) && $_COOKIE['sidebar_state'] == "collapsed")); ?>

            <div id="sidebar" class="sidebar-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>">
                <div id="sidebar-header" class="<?php echo $isCollapsed ? 'sidebar-header-collapsed' : ''; ?>" >
                    <span id="sidebar-header-content"></span>
                </div>
                <div id="sidebar-content" class="<?php echo $isCollapsed ? 'sidebar-content-collapsed' : ''; ?>" >
                    <?php require_once Ui::find_template('sidebar.inc.php'); ?>
                </div>
                <div id="sidebar-content-light" class="<?php echo $isCollapsed ? 'sidebar-content-light-collapsed' : ''; ?>" >
                    <?php require_once Ui::find_template('sidebar.light.inc.php'); ?>
                </div>
            </div>
            <!-- Handle collapsed visibility -->
            <script>
            $('#sidebar-header').click(function(){
                var newstate = "collapsed";
                if ($('#sidebar-header').hasClass("sidebar-header-collapsed")) {
                    newstate = "expanded";
                }

                if (newstate != "expanded") {
                    $("#content").addClass("content-left-wild", 600);
                } else {
                    $("#content").removeClass("content-left-wild", 1000);
                }

                $('#sidebar').hide(500, function() {
                    if (newstate == "expanded") {
                        $('#sidebar-content-light').removeClass("sidebar-content-light-collapsed");
                        $('#sidebar-content').removeClass("sidebar-content-collapsed");
                        $('#sidebar-header').removeClass("sidebar-header-collapsed");
                    } else {
                        $('#sidebar-content').addClass("sidebar-content-collapsed");
                        $('#sidebar-header').addClass("sidebar-header-collapsed");
                        $('#sidebar-content-light').addClass("sidebar-content-light-collapsed");
                    }

                    $('#sidebar').show(500);
                });

                Cookies.set('sidebar_state', newstate, {<?php echo $cookie_string; ?>});
            });
            </script>
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
                    //if (!AmpConfig::get('hide_ampache_messages', false)) {
                    //    AutoUpdate::show_ampache_message();
                    //}
                    if (
                        AmpConfig::get('autoupdate') &&
                        AutoUpdate::is_update_available()
                    ) {
                        AutoUpdate::show_new_version();
                        echo '<br>';
                    }
                    if (Plugin::is_update_available()) {
                        Plugin::show_update_available();
                        echo '<br>';
                    }
                    if (AmpConfig::get('int_config_version') > AmpConfig::get('config_version')) { ?>
                            <div class="fatalerror">
                                <?php echo T_('Your Ampache config file is out of date!'); ?>
                                <br>
                                <a class="nohtml" href="<?php echo $web_path; ?>/admin/system.php?action=write_config"><?php echo T_('Update your current config file automatically'); ?></a> |
                                <a class="nohtml" href="<?php echo $web_path; ?>/admin/system.php?action=generate_config"><?php echo T_('Download a copy of the new version'); ?></a>
                                <br>
                            </div>
                <?php }
                    echo '</div>';
                }
if (AmpConfig::get("ajax_load")) {
    require Ui::find_template('show_web_player_embedded.inc.php');
} ?>
                <div id="guts">
