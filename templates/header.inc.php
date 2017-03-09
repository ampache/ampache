<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

if (INIT_LOADED != '1') {
    exit;
}

$web_path          = AmpConfig::get('web_path');
$htmllang          = str_replace("_", "-", AmpConfig::get('lang'));
$location          = get_location();
$_SESSION['login'] = false;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo is_rtl(AmpConfig::get('lang')) ? 'rtl' : 'ltr';?>">
    <head>
        <!-- Propulsed by Ampache | ampache.org -->
        <link rel="search" type="application/opensearchdescription+xml" title="<?php echo scrub_out(AmpConfig::get('site_title')); ?>" href="<?php echo $web_path; ?>/search.php?action=descriptor" />
        <?php if (AmpConfig::get('use_rss')) {
    ?>
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Now Playing');
    ?>" href="<?php echo $web_path;
    ?>/rss.php" />
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Recently Played');
    ?>" href="<?php echo $web_path;
    ?>/rss.php?type=recently_played" />
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Newest Albums');
    ?>" href="<?php echo $web_path;
    ?>/rss.php?type=latest_album" />
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Newest Artists');
    ?>" href="<?php echo $web_path;
    ?>/rss.php?type=latest_artist" />
        <?php if (AmpConfig::get('sociable')) {
    ?>
        <link rel="alternate" type="application/rss+xml" title="<?php echo T_('Newest Shouts');
    ?>" href="<?php echo $web_path;
    ?>/rss.php?type=latest_shout" />
        <?php 
}
    ?>
        <?php 
} ?>
        <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
        <title><?php echo AmpConfig::get('site_title'); ?> - <?php echo $location['title']; ?></title>
        <?php require_once AmpConfig::get('prefix') . UI::find_template('stylesheets.inc.php'); ?>
        <link rel="stylesheet" href="<?php echo $web_path . UI::find_template('jquery-editdialog.css'); ?>" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/jquery-ui-ampache/jquery-ui.min.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path . UI::find_template('jquery-file-upload.css'); ?>" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/components/jstree/dist/themes/default/style.min.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/components/tag-it/css/jquery.tagit.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/rhinoslider/css/rhinoslider-1.05.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/modules/jquery-mediaTable/jquery.mediaTable.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/components/datetimepicker/jquery.datetimepicker.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/components/jQuery-contextMenu/dist/jquery.contextMenu.min.css" type="text/css" media="screen" />
        <script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jquery-ui/jquery-ui.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/prettyphoto/js/jquery.prettyPhoto.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/tag-it/js/tag-it.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/vendor/needim/noty/js/noty/packaged/jquery.noty.packaged.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jquery-cookie/jquery.cookie.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jscroll/jquery.jscroll.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jquery-qrcode/src/jquery.qrcode.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/rhinoslider/js/rhinoslider-1.05.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/responsive-elements/responsive-elements.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/modules/jquery-mediaTable/jquery.mediaTable.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/datetimepicker/jquery.datetimepicker.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jQuery-Knob/js/jquery.knob.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jQuery-File-Upload/js/jquery.iframe-transport.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jQuery-File-Upload/js/jquery.fileupload.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/components/jQuery-contextMenu/dist/jquery.contextMenu.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/tools.js" language="javascript" type="text/javascript"></script>

        <script type="text/javascript" charset="utf-8">
            $(document).ready(function(){
                $("a[rel^='prettyPhoto']").prettyPhoto({social_tools:false});
                <?php if (AmpConfig::get('geolocation')) {
    ?>
                    geolocate_user();
                <?php 
} ?>
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
            <script src="<?php echo $web_path;
            ?>/lib/javascript/dynamicpage.js" language="javascript" type="text/javascript"></script>
        <?php
            require_once AmpConfig::get('prefix') . UI::find_template('show_html5_player_headers.inc.php');
            ?>
        <script type="text/javascript">
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
        <?php 
        } ?>
        <script type="text/javascript">
            $.widget( "custom.catcomplete", $.ui.autocomplete, {
                _renderItem: function( ul, item ) {
                        var itemhtml = "";
                        if (item.link !== '') {
                            itemhtml += "<a href='" + item.link + "'>";
                        } else {
                            itemhtml += "<a>";
                        }
                        if (item.image != '') {
                            itemhtml += "<img src='" + item.image + "' class='searchart' />";
                        }
                        itemhtml += "<span class='searchitemtxt'>" + item.label + ((item.rels == '') ? "" : " - " + item.rels)  + "</span>";
                        itemhtml += "</a>";

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
        
        <?php if (AmpConfig::get('cookie_disclaimer') && !isset($_COOKIE['cookie_disclaimer'])) {
    ?>
        <script type="text/javascript" language="javascript">
        noty({text: '<?php printf(json_encode(nl2br(/* HINT: Translator, "%s" is replaced by "cookie settings" */T_("We have placed cookies on your computer to help make this website better. You can change your %s at any time.\nOtherwise, we will assume you are OK to continue.\n\nClick on this message to not display it again."))),
                    "<a href=\"" . AmpConfig::get('web_path') . "/cookie_disclaimer.php\">" . T_('cookie settings') . "</a>");
    ?>',
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
        <?php 
} ?>
        
        <?php if (AmpConfig::get('libitem_contextmenu')) {
    ?>
        <script type="text/javascript" language="javascript">
            function libitem_action(item, action)
            {
                var iinfo = item.attr('id').split('_', 2);
                var object_type = iinfo[0];
                var object_id = iinfo[1];
                
                if (action !== undefined && action !== '') {
                    ajaxPut(jsAjaxUrl + action + '&object_type=' + object_type + '&object_id=' + object_id);
                } else {
                    showPlaylistDialog(this, object_type, object_id);
                }
            }
            
            $.contextMenu({
                selector: ".libitem_menu",
                items: {
                    play: {name: "<?php echo T_('Play') ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay'); }},
                    play_next: {name: "<?php echo T_('Play next') ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay&playnext=true'); }},
                    play_last: {name: "<?php echo T_('Play last') ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay&append=true'); }},
                    add_tmp_playlist: {name: "<?php echo T_('Add to temporary playlist') ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?action=basket'); }},
                    add_playlist: {name: "<?php echo T_('Add to existing playlist') ?>", callback: function(key, opt){ libitem_action(opt.$trigger, ''); }}
                }
            });
        </script>
        <?php 
} ?>
        
        <!-- rfc3514 implementation -->
        <div id="rfc3514" style="display:none;">0x0</div>
        <div id="notification" class="notification-out"><img src="<?php echo $web_path; ?>/images/icon_info.png" /><span id="notification-content"></span></div>
        <div id="maincontainer">
            <div id="header" class="header-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>"><!-- This is the header -->
                <h1 id="headerlogo">
                  <a href="<?php echo $web_path; ?>/index.php">
                    <img src="<?php echo UI::get_logo_url(); ?>" title="<?php echo AmpConfig::get('site_title'); ?>" alt="<?php echo AmpConfig::get('site_title'); ?>" />
                  </a>
                </h1>
                <div id="headerbox">
                    <?php UI::show_box_top('','box box_headerbox'); ?>
                    <?php require_once AmpConfig::get('prefix') . UI::find_template('show_search_bar.inc.php'); ?>
                    <?php if (User::is_registered()) {
    ?>
                        <?php require_once AmpConfig::get('prefix') . UI::find_template('show_playtype_switch.inc.php');
    ?>
                        <span id="loginInfo">
                            <a href="<?php echo $web_path;
    ?>/stats.php?action=show_user&user_id=<?php echo $GLOBALS['user']->id;
    ?>"><?php echo $GLOBALS['user']->fullname;
    ?></a>
                            <?php if (AmpConfig::get('sociable')) {
    ?>
                            <a href="<?php echo $web_path;
    ?>/browse.php?action=pvmsg" title="<?php echo T_('New messages');
    ?>">(<?php echo count(PrivateMsg::get_private_msgs($GLOBALS['user']->id, true));
    ?>)</a>
                            <?php 
}
    ?>
                            <a rel="nohtml" href="<?php echo $web_path;
    ?>/logout.php">[<?php echo T_('Log out');
    ?>]</a>
                        </span>
                    <?php 
} else {
    ?>
                        <span id="loginInfo">
                            <a href="<?php echo $web_path;
    ?>/login.php" rel="nohtml"><?php echo T_('Login');
    ?></a>
                            <?php if (AmpConfig::get('allow_public_registration')) {
    ?>
                                / <a href="<?php echo $web_path;
    ?>/register.php" rel="nohtml"><?php echo T_('Register');
    ?></a>
                            <?php 
}
    ?>
                        </span>
                    <?php 
} ?>

                    <?php UI::show_box_bottom(); ?>
                </div> <!-- End headerbox -->
            </div><!-- End header -->

        <?php if (AmpConfig::get('topmenu')) {
    ?>
            <div id="topmenu_container" class="topmenu_container-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float';
    ?>">
                <div id="topmenu_item">
                    <a href="<?php echo $web_path ?>/index.php">
                        <img src="<?php echo $web_path ?>/images/topmenu-home.png" />
                        <span><?php echo T_('Home') ?></span>
                    </a>
                </div>
                <div id="topmenu_item">
                    <a href="<?php echo $web_path ?>/browse.php?action=artist">
                        <img src="<?php echo $web_path ?>/images/topmenu-artist.png" />
                        <span><?php echo T_('Artists') ?></span>
                    </a>
                </div>
                <div id="topmenu_item">
                    <a href="<?php echo $web_path ?>/browse.php?action=playlist">
                        <img src="<?php echo $web_path ?>/images/topmenu-playlist.png" />
                        <span><?php echo T_('Playlists') ?></span>
                    </a>
                </div>
                <div id="topmenu_item">
                    <a href="<?php echo $web_path ?>/browse.php?action=tag">
                        <img src="<?php echo $web_path ?>/images/topmenu-tagcloud.png" />
                        <span><?php echo T_('Tag Cloud') ?></span>
                    </a>
                </div>
                <?php if (AmpConfig::get('userflags') && Access::check('interface', '25')) {
    ?>
                <div id="topmenu_item">
                    <a href="<?php echo $web_path ?>/stats.php?action=userflag">
                        <img src="<?php echo $web_path ?>/images/topmenu-favorite.png" />
                        <span><?php echo T_('Favorites') ?></span>
                    </a>
                </div>
                <?php 
}
    ?>
                <?php if (AmpConfig::get('allow_upload') && Access::check('interface', '25')) {
    ?>
                <div id="topmenu_item">
                    <a href="<?php echo $web_path ?>/upload.php">
                        <img src="<?php echo $web_path ?>/images/topmenu-upload.png" />
                        <span><?php echo T_('Upload') ?></span>
                    </a>
                </div>
                <?php 
}
    ?>
            </div>
        <?php 
} ?>
            <?php $isCollapsed = ((AmpConfig::get('sidebar_light') && $_COOKIE['sidebar_state'] != "expanded") || $_COOKIE['sidebar_state'] == "collapsed"); ?>
            <div id="sidebar" class="sidebar-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>">
                <div id="sidebar-header" class="<?php echo $isCollapsed ? 'sidebar-header-collapsed' : ''; ?>" ><span id="sidebar-header-content"><?php echo $isCollapsed ? '>>>' : '<<<'; ?></span></div>
                <div id="sidebar-content" class="<?php echo $isCollapsed ? 'sidebar-content-collapsed' : ''; ?>" >
                    <?php require_once AmpConfig::get('prefix') . UI::find_template('sidebar.inc.php'); ?>
                </div>
                <div id="sidebar-content-light" class="<?php echo $isCollapsed ? 'sidebar-content-light-collapsed' : ''; ?>" >
                    <?php require_once AmpConfig::get('prefix') . UI::find_template('sidebar.light.inc.php'); ?>
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
                <?php require_once AmpConfig::get('prefix') . UI::find_template('rightbar.inc.php'); ?>
            </div>

            <!-- Tiny little div, used to cheat the system -->
            <div id="ajax-loading">Loading . . .</div>
            <div id="util_div" style="display:none;"></div>
            <iframe name="util_iframe" id="util_iframe" style="display:none;" src="<?php echo $web_path; ?>/util.php"></iframe>
            
            <div id="content" class="content-<?php echo AmpConfig::get('ui_fixed') ? (AmpConfig::get('topmenu') ? 'fixed-topmenu' : 'fixed') : 'float'; ?> <?php echo (($count_temp_playlist || AmpConfig::get('play_type') == 'localplay') ? '' : 'content-right-wild'); echo $isCollapsed ? ' content-left-wild' : ''; ?>">

                <?php
                    if (Access::check('interface','100')) {
                        echo '<div id=update_notify>';
                        if (AmpConfig::get('autoupdate') && AutoUpdate::is_update_available()) {
                            AutoUpdate::show_new_version();
                            echo '<br />';
                        }
                        $count_temp_playlist = count($GLOBALS['user']->playlist->get_items());
                        
                        if (AmpConfig::get('int_config_version') != AmpConfig::get('config_version')) {
                            ?>
                            <div class="fatalerror">
                                <?php echo T_('Error: Your config file is out of date!');
                            ?>
                                <br />
                                <a rel="nohtml" href="<?php echo $web_path;
                            ?>/admin/system.php?action=generate_config"><?php echo T_('Generate and download new config file');
                            ?></a> |
                                <a rel="nohtml" href="<?php echo $web_path;
                            ?>/admin/system.php?action=write_config"><?php echo T_('Write new config file to disk');
                            ?></a>
                            </div>
                <?php

                        }
                        echo '</div>';
                    }
                ?>
                <div id="guts">
