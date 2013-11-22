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

$web_path = Config::get('web_path');
$htmllang = str_replace("_","-",Config::get('lang'));
$location = get_location();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo is_rtl(Config::get('lang')) ? 'rtl' : 'ltr';?>">

<head>
<link rel="shortcut icon" href="<?php echo $web_path; ?>/favicon.ico" />
<link rel="search" type="application/opensearchdescription+xml" title="<?php echo scrub_out(Config::get('site_title')); ?>" href="<?php echo $web_path; ?>/search.php?action=descriptor" />
<?php
if (Config::get('use_rss')) { ?>
<link rel="alternate" type="application/rss+xml" title="<?php echo T_('Now Playing'); ?>" href="<?php echo $web_path; ?>/rss.php" />
<link rel="alternate" type="application/rss+xml" title="<?php echo T_('Recently Played'); ?>" href="<?php echo $web_path; ?>/rss.php?type=recently_played" />
<?php } ?>
<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=<?php echo Config::get('site_charset'); ?>" />
<title><?php echo scrub_out(Config::get('site_title')); ?> - <?php echo $location['title']; ?></title>
<?php require_once Config::get('prefix') . '/templates/stylesheets.inc.php'; ?>
<link rel="stylesheet" href="<?php echo $web_path; ?>/modules/jquery/jquery-ui.min.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path; ?>/modules/tag-it/jquery.tagit.css" type="text/css" media="screen" />
<script src="<?php echo $web_path; ?>/modules/jquery/jquery.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/jquery/jquery-ui.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/prettyPhoto/js/jquery.prettyPhoto.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/tag-it/tag-it.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/edit-dialog.js" language="javascript" type="text/javascript"></script>
<?php
// If iframes, we check in javascript that parent container exist, otherwise we redirect to index. Otherwise HTML5 iframed Player will look broken.
if (Config::get('iframes') && $_SERVER['REQUEST_METHOD'] != 'POST') {
?>
<script language="javascript" type="text/javascript">
function forceIframe()
{
    if (self == top) {
        document.location = '<?php echo $web_path; ?>?target_link=' + encodeURIComponent(document.location);
    }
}

// Using the following work-around to set ajex.server.php path available from any javascript script.
var jsAjaxUrl = "<?php echo Config::get('ajax_url') ?>";

</script>
<?php
}
?>
<script type="text/javascript" charset="utf-8">
  $(document).ready(function(){
    $("a[rel^='prettyPhoto']").prettyPhoto({social_tools:false});
  });
</script>
</head>
<body <?php echo (Config::get('iframes')) ? "onLoad='forceIframe();'" : ""; ?>>
<!-- rfc3514 implementation -->
<div id="rfc3514" style="display:none;">0x0</div>
<div id="maincontainer">
    <div id="header"><!-- This is the header -->
        <h1 id="headerlogo">
          <a href="<?php echo Config::get('web_path') . ((Config::get('iframes')) ? '/?framed=1' : ''); ?>">
            <img src="<?php echo $web_path; ?><?php echo Config::get('theme_path'); ?>/images/ampache.png" title="<?php echo Config::get('site_title'); ?>" alt="<?php echo Config::get('site_title'); ?>" />
          </a>
        </h1>
        <div id="headerbox">
            <?php UI::show_box_top('','box box_headerbox'); ?>
            <?php require_once Config::get('prefix') . '/templates/show_search_bar.inc.php'; ?>
            <?php require_once Config::get('prefix') . '/templates/show_playtype_switch.inc.php'; ?>
            <span id="loginInfo"><a href="<?php echo Config::get('web_path'); ?>/preferences.php?tab=account"><?php echo $GLOBALS['user']->fullname; ?></a> <a target="_top" href="<?php echo Config::get('web_path'); ?>/logout.php">[<?php echo T_('Log out'); ?>]</a></span>
            <?php UI::show_box_bottom(); ?>
        </div> <!-- End headerbox -->
    </div><!-- End header -->
    <div id="sidebar"><!-- This is the sidebar -->
        <?php require_once Config::get('prefix') . '/templates/sidebar.inc.php'; ?>
    </div><!-- End sidebar -->
    <div id="rightbar"><!-- This is the rightbar -->
        <?php require_once Config::get('prefix') . '/templates/rightbar.inc.php'; ?>
    </div><!-- End rightbar -->
<!-- Tiny little iframe, used to cheat the system -->
<div id="ajax-loading">Loading . . .</div>
<iframe name="util_iframe" id="util_iframe" style="display:none;" src="<?php echo Config::get('web_path'); ?>/util.php"></iframe>
<div id="content">
<?php if (Config::get('int_config_version') != Config::get('config_version') AND $GLOBALS['user']->has_access(100)) { ?>
<div class="fatalerror">
    <?php echo T_('Error Config File Out of Date'); ?>
    <a href="<?php echo Config::get('web_path'); ?>/admin/system.php?action=generate_config"><?php echo T_('Generate New Config'); ?></a>
</div>
<?php } ?>
