<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
$htmllang = str_replace("_","-",AmpConfig::get('lang'));
$location = get_location();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo is_rtl(AmpConfig::get('lang')) ? 'rtl' : 'ltr';?>">
<head>
<link rel="shortcut icon" href="<?php echo $web_path; ?>/favicon.ico" />
<link rel="search" type="application/opensearchdescription+xml" title="<?php echo scrub_out(AmpConfig::get('site_title')); ?>" href="<?php echo $web_path; ?>/search.php?action=descriptor" />
<?php
if (AmpConfig::get('use_rss')) { ?>
<link rel="alternate" type="application/rss+xml" title="<?php echo T_('Now Playing'); ?>" href="<?php echo $web_path; ?>/rss.php" />
<link rel="alternate" type="application/rss+xml" title="<?php echo T_('Recently Played'); ?>" href="<?php echo $web_path; ?>/rss.php?type=recently_played" />
<?php } ?>
<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
<title><?php echo scrub_out(AmpConfig::get('site_title')); ?> - <?php echo $location['title']; ?></title>
<?php require_once AmpConfig::get('prefix') . '/templates/stylesheets.inc.php'; ?>
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/jquery-editdialog.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path; ?>/modules/jquery-ui/jquery-ui.min.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path; ?>/modules/tag-it/jquery.tagit.css" type="text/css" media="screen" />
<script src="<?php echo $web_path; ?>/modules/jquery/jquery.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/jquery-ui/jquery-ui.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/prettyPhoto/js/jquery.prettyPhoto.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/tag-it/tag-it.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/noty/packaged/jquery.noty.packaged.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/jscroll/jquery.jscroll.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/jquery/jquery.qrcode.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/rhinoslider/rhinoslider-1.05.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/tools.js" language="javascript" type="text/javascript"></script>
<?php
// If iframes, we check in javascript that parent container exist, otherwise we redirect to index. Otherwise iframed Web Player will look broken.
if (AmpConfig::get('iframes') && $_SERVER['REQUEST_METHOD'] != 'POST') {
?>
<script language="javascript" type="text/javascript">
function forceIframe()
{
    if (self == top) {
        document.location = '<?php echo $web_path; ?>?target_link=' + encodeURIComponent(document.location);
    }
}
</script>
<?php
}
?>
<script type="text/javascript" charset="utf-8">
    $(document).ready(function(){
        $("a[rel^='prettyPhoto']").prettyPhoto({social_tools:false});
    });

    // Using the following workaround to set global variable available from any javascript script.
    var jsAjaxUrl = "<?php echo AmpConfig::get('ajax_url') ?>";
    var jsWebPath = "<?php echo AmpConfig::get('web_path') ?>";
    var jsAjaxServer = "<?php echo AmpConfig::get('ajax_server') ?>";
    var jsSaveTitle = "<?php echo T_('Save') ?>";
    var jsCancelTitle = "<?php echo T_('Cancel') ?>";
</script>
<script type="text/javascript">
$.widget( "custom.catcomplete", $.ui.autocomplete, {
    _renderItem: function( ul, item ) {
            return $( "<li class='ui-menu-item'>" )
                .data("ui-autocomplete-item", item)
                .append( "<a href='" + item.link + "'>" + item.label + ((item.rels == '') ? "" : " - " + item.rels)  + "</a>" )
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
function init_slideshow_refresh()
{
    var ff = window.parent.document.getElementById('frame_footer');
    var maindiv = window.parent.document.getElementById('maindiv');
    if (ff != null && ff.getAttribute('className') == 'frame_footer_visible') {
        clearTimeout(tSlideshow);
        tSlideshow = null;

        $("#aslideshow").height($(document).height())
          .css({'display': 'inline'})
          .click(function(e) {
                update_action();
            });

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
function update_action()
{
    lastaction = new Date().getTime();
    if (iSlideshow != null) {
        iSlideshow = null;
        $("#aslideshow").css({'display': 'none'});
    }
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
<body <?php echo (AmpConfig::get('iframes')) ? "onLoad='forceIframe();'" : ""; ?>>
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
<!-- rfc3514 implementation -->
<div id="rfc3514" style="display:none;">0x0</div>
<div id="maincontainer">
    <div id="header" class="header-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>"><!-- This is the header -->
        <h1 id="headerlogo">
          <a href="<?php echo AmpConfig::get('web_path') . ((AmpConfig::get('iframes')) ? '/?framed=1' : ''); ?>">
            <img src="<?php echo $web_path; ?><?php echo AmpConfig::get('theme_path'); ?>/images/ampache.png" title="<?php echo AmpConfig::get('site_title'); ?>" alt="<?php echo AmpConfig::get('site_title'); ?>" />
          </a>
        </h1>
        <div id="headerbox">
            <?php UI::show_box_top('','box box_headerbox'); ?>
            <?php require_once AmpConfig::get('prefix') . '/templates/show_search_bar.inc.php'; ?>
            <?php require_once AmpConfig::get('prefix') . '/templates/show_playtype_switch.inc.php'; ?>
            <span id="loginInfo"><a href="<?php echo AmpConfig::get('web_path'); ?>/preferences.php?tab=account"><?php echo $GLOBALS['user']->fullname; ?></a> <a target="_top" href="<?php echo AmpConfig::get('web_path'); ?>/logout.php">[<?php echo T_('Log out'); ?>]</a></span>
            <span id="updateInfo">
<?php
if (AmpConfig::get('autoupdate') && Access::check('interface','100')) {
    if (AutoUpdate::is_update_available()) {
        AutoUpdate::show_new_version();
    }
}
?>
            </span>
            <?php UI::show_box_bottom(); ?>
        </div> <!-- End headerbox -->
    </div><!-- End header -->
    <div id="sidebar" class="sidebar-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>"><!-- This is the sidebar -->
        <?php require_once AmpConfig::get('prefix') . '/templates/sidebar.inc.php'; ?>
    </div><!-- End sidebar -->
    <div id="rightbar" class="rightbar-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>"><!-- This is the rightbar -->
        <?php require_once AmpConfig::get('prefix') . '/templates/rightbar.inc.php'; ?>
    </div><!-- End rightbar -->
<!-- Tiny little iframe, used to cheat the system -->
<div id="ajax-loading">Loading . . .</div>
<iframe name="util_iframe" id="util_iframe" style="display:none;" src="<?php echo AmpConfig::get('web_path'); ?>/util.php"></iframe>
<div id="content" class="content-<?php echo AmpConfig::get('ui_fixed') ? 'fixed' : 'float'; ?>">
<?php if (AmpConfig::get('int_config_version') != AmpConfig::get('config_version') AND $GLOBALS['user']->has_access(100)) { ?>
<div class="fatalerror">
    <?php echo T_('Error Config File Out of Date'); ?>
    <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/system.php?action=generate_config"><?php echo T_('Generate New Config'); ?></a>
</div>
<?php } ?>
