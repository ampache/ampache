<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

if (INIT_LOADED != '1') { exit; }

$web_path = AmpConfig::get('web_path');
$htmllang = str_replace("_", "-", AmpConfig::get('lang'));
$location = get_location();
$_SESSION['login'] = false;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!--
   _                                   _           
  /_\   _ __ ___   _ __    __ _   ___ | |__    ___ 
 //_\\ | '_ ` _ \ | '_ \  / _` | / __|| '_ \  / _ \
/  _  \| | | | | || |_) || (_| || (__ | | | ||  __/
\_/ \_/|_| |_| |_|| .__/  \__,_| \___||_| |_| \___|
                  |_|                              
-->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo is_rtl(AmpConfig::get('lang')) ? 'rtl' : 'ltr';?>">
    <head>
        <link rel="shortcut icon" href="<?php echo $web_path; ?>/favicon.ico" />
        <link rel="search" type="application/opensearchdescription+xml" title="<?php echo scrub_out(AmpConfig::get('site_title')); ?>" href="<?php echo $web_path; ?>/search.php?action=descriptor" />
        <?php if (AmpConfig::get('use_rss')) { ?>
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Now Playing'); ?>" href="<?php echo $web_path; ?>/rss.php" />
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Recently Played'); ?>" href="<?php echo $web_path; ?>/rss.php?type=recently_played" />
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Newest Albums'); ?>" href="<?php echo $web_path; ?>/rss.php?type=latest_album" />
        <?php } ?>
        <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
        <title><?php echo AmpConfig::get('site_title'); ?> - <?php echo $location['title']; ?></title>
        <link rel="stylesheet" href="<?php echo $web_path; ?>/templates/jquery-editdialog.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/jquery-ui-ampache/jquery-ui.min.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/templates/jquery-file-upload.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/jstree/themes/default/style.min.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/tag-it/jquery.tagit.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/rhinoslider/css/rhinoslider-1.05.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/jquery-mediaTable/jquery.mediaTable.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/jquery-datetimepicker/jquery.datetimepicker.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/bootstrap/css/bootstrap.min.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/bootstrap/css/bootstrap-theme.min.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/font-awesome/css/font-awesome.min.css" type="text/css" media="screen" />
        <?php require_once AmpConfig::get('prefix') . '/templates/stylesheets.inc.php'; ?>
        <script src="<?php echo $web_path; ?>/modules/jquery/jquery.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/jquery-ui/jquery-ui.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/prettyPhoto/js/jquery.prettyPhoto.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/tag-it/tag-it.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/noty/packaged/jquery.noty.packaged.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/jquery-cookie/jquery.cookie.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/jscroll/jquery.jscroll.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/jquery-qrcode/jquery.qrcode.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/rhinoslider/js/rhinoslider-1.05.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/responsive-elements/responsive-elements.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/jquery-mediaTable/jquery.mediaTable.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/jquery-datetimepicker/jquery.datetimepicker.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/jquery-knob/jquery.knob.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/jquery-file-upload/jquery.iframe-transport.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/jquery-file-upload/jquery.fileupload.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/bootstrap/js/bootstrap.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/tools.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/user-interface.js" language="javascript" type="text/javascript"></script>
        <?php
        if (AmpConfig::get('ajax_load')) {
            $iframed = true;
        ?>
            <script src="<?php echo $web_path; ?>/lib/javascript/dynamicpage.js" language="javascript" type="text/javascript"></script>
        <?php
            require_once AmpConfig::get('prefix') . '/templates/show_html5_player_headers.inc.php';
        }
        ?>
        <script type="text/javascript" charset="utf-8">
            $(document).ready(function(){
                $("a[rel^='prettyPhoto']").prettyPhoto({social_tools:false});
                <?php if (AmpConfig::get('geolocation')) { ?>
                    geolocate_user();
                <?php } ?>
            });

            // Using the following workaround to set global variable available from any javascript script.
            var jsAjaxUrl = "<?php echo AmpConfig::get('ajax_url') ?>";
            var jsWebPath = "<?php echo $web_path; ?>";
            var jsAjaxServer = "<?php echo AmpConfig::get('ajax_server') ?>";
            var jsSaveTitle = "<?php echo T_('Save') ?>";
            var jsCancelTitle = "<?php echo T_('Cancel') ?>";
        </script>

        <?php
        if (AmpConfig::get('ajax_load')) {
            $iframed = true;
        ?>
            <script src="<?php echo $web_path; ?>/lib/javascript/dynamicpage.js" language="javascript" type="text/javascript"></script>
        <?php
            require_once AmpConfig::get('prefix') . '/templates/show_html5_player_headers.inc.php';
        ?>
        <script type="text/javascript">
            function NavigateTo(url)
            {
                window.location.hash = url.substring(jsWebPath.length + 1);
            }

            function getCurrentPage()
            {
                if (window.location.hash.length > 0) {
                    return btoa(window.location.hash.substring(1));
                }

                return btoa(window.location.href.substring(jsWebPath.length + 1));
            }
        </script>
        <?php
        } else {
        ?>
        <script type="text/javascript">
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
        <script type="text/javascript">
            $.widget( "custom.catcomplete", $.ui.autocomplete, {
                _renderItem: function( ul, item ) {
                        var itemhtml = "<a href='" + item.link + "'>";
                        if (item.image != '') {
                            itemhtml += "<img src='" + item.image + "' class='searchart' />";
                        }
                        itemhtml += "<span class='searchitemtxt'>" + item.label + ((item.rels == '') ? "" : " - " + item.rels)  + "</span></a>"

                        return $( "<li class='ui-menu-item'>" )
                            .data("ui-autocomplete-item", item)
                            .append( itemhtml )
                            .appendTo( ul );
                },
                _renderMenu: function( ul, items ) {
                    var that = this, currentType = "";
                    $.each( items, function( index, item ) {
                        if (item.type != currentType) {
                            ul.append( "<li class='ui-autocomplete-category'>" + item.type + "</li>" );
                            currentType = item.type;
                        }

                        that._renderItem( ul, item );
                    });
                }
            });

            $(function() {
                $( "#searchString" )
                // don't navigate away from the field on tab when selecting an item
                    .bind( "keydown", function( event ) {
                        if ( event.keyCode === $.ui.keyCode.TAB && $( this ).data( "ui-autocomplete" ).menu.active ) {
                            event.preventDefault();
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
                            if (this.value.length < 2) {
                                return false;
                            }
                        },
                        focus: function() {
                            // prevent value inserted on focus
                            return false;
                        },
                        select: function( event, ui ) {
                            if (ui.item != null) {
                                $(this).val(ui.item.value);
                            }
                            return false;
                        }
                    });
            });
        </script>
        <script type="text/javascript">
            var lastaction = new Date().getTime();
            var refresh_slideshow_interval=<?php echo AmpConfig::get('slideshow_time'); ?>;
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
    <body>
        <?php if (AmpConfig::get('sociable') && AmpConfig::get('notify')) { ?>
        <script type="text/javascript" language="javascript">
            var lastrefresh = new Date().getTime();
            var refresh_sociable_interval=<?php echo AmpConfig::get('refresh_limit') ?>;
            function refresh_sociable()
            {
                <?php echo Ajax::action('?page=index&action=shoutbox&since=\' + lastrefresh + \'', ''); ?>;
                lastrefresh = new Date().getTime();
            }
            $(document).ready(function() {
                window.setInterval(function(){refresh_sociable();}, refresh_sociable_interval * 1000);
            });
        </script>
        <div id="live_shoutbox"></div>
        <?php } ?>
        <div id="aslideshow">
            <div id="aslideshow_container">
                <div id="fslider"></div>
                <div id="fslider_script"></div>
            </div>
        </div>
        <script type="text/javascript" language="javascript">
            $("#aslideshow").click(function(e) {
                if (!$(e.target).hasClass('rhino-btn')) {
                    update_action();
                }
            });
        </script>
        <?php if (AmpConfig::get('cookie_disclaimer') && !isset($_COOKIE['cookie_disclaimer'])) { ?>
        <script type="text/javascript" language="javascript">
            noty({text: '<?php echo T_("We have placed cookies on your computer to help make this website better. You can change your") . " <a href=\"" . AmpConfig::get('web_path') . "/cookie_disclaimer.php\">" . T_("cookie settings") . "</a> " . T_("at any time. Otherwise, we\'ll assume you\'re OK to continue.<br /><br />Click on this message do not display it again."); ?>',
                type: 'warning',
                layout: 'bottom',
                timeout: false,
                callback: {
                    afterClose: function() {
                        $.cookie('cookie_disclaimer', '1', { expires: 365 });
                    }
                },
            });
        </script>
        <?php } ?>
        <!-- rfc3514 implementation -->
        <div id="rfc3514" style="display:none;">0x0</div>
        <div id="maincontainer" class="application show-nav-bar show-action-bar show-breadcrumb-bar">
            <div class="nav-bar">
                <ul class="nav nav-bar-nav">
                    <!--<li><a class="back-btn" href="#" data-original-title="" title=""><i class="glyphicon glyphicon-left-arrow"></i></a></li>-->
                    <li>
                        <a class="home-btn" href="<?php echo $web_path; ?>/index.php" title="<?php echo AmpConfig::get('site_title'); ?>" alt="<?php echo AmpConfig::get('site_title'); ?>">
                            <i class="fa fa-home fa-lg"></i>
                        </a>
                    </li>
                </ul>

                <div class="nav-bar-search-container">
                    <form id="#nav-bar-search-form" class="nav-bar-form nav-bar-left hidden-xs" method="post" action="<?php echo $web_path; ?>/search.php?type=song" enctype="multipart/form-data">
                        <div class="form-group form-group-search">
                            <label class="control-label-search" for="nav-bar-search">
                                <i class="fa fa-search fa-lg"></i>
                                <a class="clear-search-btn hidden" href="#"><i class="fa fa-times-circle"></i></a>
                            </label>

                            <input type="search" id="nav-bar-search" class="form-control form-control-search" placeholder="<?php echo T_("Search"); ?>" value="">
                        </div>
                    </form>
                </div>

                <ul class="nav nav-bar-nav nav-bar-right">
                    <?php
                    if (AmpConfig::get('autoupdate') && Access::check('interface','100')) {
                        if (AutoUpdate::is_update_available() && AutoUpdate::is_git_repository()) {
                    ?>
                    <li class="">
                        <a class="install-updates-btn" title="<?php echo T_('Mises à jour disponibles'); ?>" data-toggle="tooltip" rel="nohtml" href="' . AmpConfig::get('web_path') . '/update.php?type=sources&action=update">
                            <i class="fa fa-up-arrow fa-lg"></i>
                        </a>
                    </li>
                    <?php
                        }
                    }
                    ?>
                    
                    <li>
                        <a class="settings-btn" href="#!/settings" title="" data-toggle="tooltip" data-original-title="Réglages">
                            <i class="fa fa-cogs fa-lg"></i>
                        </a>
                    </li>
                    <li id="nav-dropdown" class="nav-dropdown dropdown">
                        <a class="dropdown-toggle" href="#nav-dropdown" data-toggle="dropdown" data-original-title="" title="">
                            <i class="fa fa-user fa-lg"></i><i class="caret-icon"></i>
                            <span class="total-badge badge hidden">0</span>
                        </a>

                        <ul class="dropdown-menu signed-in">
                            <li class="signed-in-item">
                                <a class="username-btn" href="<?php echo $web_path; ?>/preferences.php?tab=account" target="_self"><?php echo $GLOBALS['user']->fullname; ?></a>
                            </li>
                            <li class="signed-in-item divider"></li>

                            <li class="signed-in-item"><a class="friends-btn" href="#">Amis <span class="friend-requests-badge badge hidden">0</span></a></li>
                            <li class="signed-in-item"><a href="#!/playlist/queue">File d'attente</a></li>
                            <li class="signed-in-item"><a href="#!/playlist/recommendations">Recommandé</a></li>

                            <li class="signed-in-item divider"></li>

                            <li><a href="#!/announcements">Annonces <span class="announcements-badge badge hidden">0</span></a></li>
                            <li><a href="https://plex.tv/downloads" target="_blank">Applications...</a></li>
                            <li><a href="http://support.plex.tv/hc/en-us" target="_blank">Aide...</a></li>

                            <li class="divider"></li>

                            <li class="signed-out-item"><a class="sign-in-btn" href="#">Connexion</a></li>
                            <li class="signed-in-item"><a class="sign-out-btn" rel="nohtml" href="<?php echo $web_path; ?>/logout.php"><?php echo T_('Log out'); ?></a></li>
                        </ul>
                    </li>
                </ul>                
                
                <?php /*
                <div id="headerbox">
                    <?php UI::show_box_top('','box box_headerbox'); ?>
                    <?php if (User::is_registered()) { ?>
                        <?php require_once AmpConfig::get('prefix') . '/templates/show_playtype_switch.inc.php'; ?>
                        <span id="loginInfo"><a href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo $GLOBALS['user']->fullname; ?></a> <a rel="nohtml" href="<?php echo $web_path; ?>/logout.php">[<?php echo T_('Log out'); ?>]</a></span>
                    <?php } else { ?>
                        <span id="loginInfo">
                            <a href="<?php echo $web_path; ?>/login.php" rel="nohtml"><?php echo T_('Login'); ?></a>
                            <?php if (AmpConfig::get('allow_public_registration')) { ?>
                                / <a href="<?php echo $web_path; ?>/register.php" rel="nohtml"><?php echo T_('Register'); ?></a>
                            <?php } ?>
                        </span>
                    <?php } ?>
                    <span id="updateInfo">
                    <?php
                    if (AmpConfig::get('autoupdate') && Access::check('interface','100')) {
                        if (AutoUpdate::is_update_available()) {
                            AutoUpdate::show_new_version();
                        }
                    }
                    $count_temp_playlist = count($GLOBALS['user']->playlist->get_items());
                    ?>
                    </span>
                    <?php UI::show_box_bottom(); ?>
                </div> <!-- End headerbox --> */ 
                ?>
            </div><!-- End header -->

            <?php $isCollapsed = $_COOKIE['sidebar_state'] == "collapsed"; ?>
            <div id="sidebar" class="sidebar-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>">
                <div id="sidebar-header" class="<?php echo $isCollapsed ? 'sidebar-header-collapsed' : ''; ?>" ><span id="sidebar-header-content"><?php echo $isCollapsed ? '>>>' : '<<<'; ?></span></div>
                <div id="sidebar-content" class="<?php echo $isCollapsed ? 'sidebar-content-collapsed' : ''; ?>" >
                    <?php require_once AmpConfig::get('prefix') . '/templates/sidebar.inc.php'; ?>
                </div>
            </div>
            <!-- Handle collapsed visibility -->
            <script type="text/javascript">
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
                        $('#sidebar-header-content').text('<<<');
                    } else {
                        $('#sidebar-content').addClass("sidebar-content-collapsed");
                        $('#sidebar-header').addClass("sidebar-header-collapsed");
                        $('#sidebar-content-light').addClass("sidebar-content-light-collapsed");
                        $('#sidebar-header-content').text('>>>');
                    }

                    $('#sidebar').show(500);
                });

                $.cookie('sidebar_state', newstate, { expires: 30, path: '/'});
            });
            </script>

            <div id="rightbar" class="rightbar-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?> <?php echo $count_temp_playlist ? '' : 'hidden' ?>">
                <?php require_once AmpConfig::get('prefix') . '/templates/rightbar.inc.php'; ?>
            </div>

            <!-- Tiny little div, used to cheat the system -->
            <div id="ajax-loading">Loading . . .</div>
            <div id="util_div" style="display:none;"></div>
            <iframe name="util_iframe" id="util_iframe" style="display:none;" src="<?php echo $web_path; ?>/util.php"></iframe>
            
            <div class="background-container">
                <div class="background" style="background-image: url(blob:...);"></div>
            </div>
            <div id="content" class="scroll-container dark-scrollbar content-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?> <?php echo (($count_temp_playlist || AmpConfig::get('play_type') == 'localplay') ? '' : 'content-right-wild'); echo $isCollapsed ? ' content-left-wild' : ''; ?>">

                <?php if (AmpConfig::get('int_config_version') != AmpConfig::get('config_version') AND $GLOBALS['user']->has_access(100)) { ?>
                <div class="fatalerror">
                    <?php echo T_('Error Config File Out of Date'); ?>
                    <a rel="nohtml" href="<?php echo $web_path; ?>/admin/system.php?action=generate_config"><?php echo T_('Generate New Config'); ?></a> |
                    <a rel="nohtml" href="<?php echo $web_path; ?>/admin/system.php?action=write_config"><?php echo T_('Write New Config'); ?></a>
                </div>
                <?php } ?>
                <div id="guts">
