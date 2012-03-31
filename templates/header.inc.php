<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Header
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
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
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

if (INIT_LOADED != '1') { exit; }

$web_path = Config::get('web_path');
$htmllang = str_replace("_","-",Config::get('lang'));
$location = get_location();
$dir = is_rtl(Config::get('lang')) ? "rtl" : "ltr";
$themecss = Config::get('theme_path') . '/templates/';
$css = ($dir == 'rtl') ? $themecss.'default-rtl.css' : $themecss.'default.css';
$cssdir = Config::get('prefix').$themecss;
if(!is_file($cssdir.'default-rtl.css')) {
	$css = $themecss.'default.css';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo $dir;?>">

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
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/base.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path; ?><?php echo $css; ?>" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/print.css" type="text/css" media="print" />
</head>
<body>
<script src="<?php echo $web_path; ?>/modules/prototype/prototype.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>
<!-- rfc3514 implementation -->
<div id="rfc3514" style="display:none;">0x0</div>
<div id="maincontainer">
	<div id="header"><!-- This is the header -->
		<h1 id="headerlogo">
		  <a href="<?php echo Config::get('web_path'); ?>">
		    <img src="<?php echo $web_path; ?><?php echo Config::get('theme_path'); ?>/images/ampache.png" title="<?php echo Config::get('site_title'); ?>" alt="<?php echo Config::get('site_title'); ?>" />
		  </a>
		</h1>
		<div id="headerbox">
			<?php show_box_top('','box box_headerbox'); ?>
			<?php require_once Config::get('prefix') . '/templates/show_search_bar.inc.php'; ?>
			<?php require_once Config::get('prefix') . '/templates/show_playtype_switch.inc.php'; ?>
			<span id="loginInfo"><a href="<?php echo Config::get('web_path'); ?>/preferences.php?tab=account"><?php echo $GLOBALS['user']->fullname; ?></a> <a href="<?php echo Config::get('web_path'); ?>/logout.php">[<?php echo T_('Log out'); ?>]</a></span>
			<?php show_box_bottom(); ?>
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
