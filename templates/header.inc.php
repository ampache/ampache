<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

if (INIT_LOADED != '1') { exit; }

$web_path = Config::get('web_path');
$htmllang = str_replace("_","-",Config::get('lang'));
$location = get_location();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">

<head>
<link rel="shortcut icon" href="<?php echo $web_path; ?>/favicon.ico" />
<?php
if (Config::get('use_rss')) { ?>
<link rel="alternate" type="application/rss+xml" title="<?php echo Config::get('rss_main_title'); ?>" href="<?php echo $web_path; ?>/rss.php" />
<link rel="alternate" type="application/rss+xml" title="Ampache Latest Artists Additions" href="<?php echo $web_path; ?>/rss.php?type=latestartist" />
<link rel="alternate" type="application/rss+xml" title="Ampache Latest Albums Additions" href="<?php echo $web_path; ?>/rss.php?type=latestalbum" />
<link rel="alternate" type="application/rss+xml" title="Ampache Most Popular Albums" href="<?php echo $web_path; ?>/rss.php?type=popularalbum" />
<link rel="alternate" type="application/rss+xml" title="Ampache Most Popular Artists" href="<?php echo $web_path; ?>/rss.php?type=popularalbum" />
<link rel="alternate" type="application/rss+xml" title="Ampache Most Popular Songs" href="<?php echo $web_path; ?>/rss.php?type=popularsong" />
<link rel="alternate" type="application/rss+xml" title="Ampache Recently Played" href="<?php echo $web_path; ?>/rss.php?type=recentlyplayed" />
<?php } ?>
<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=<?php echo Config::get('site_charset'); ?>" />
<title><?php echo Config::get('site_title'); ?> - <?php echo $location['title']; ?></title>
<link rel="stylesheet" href="<?php echo $web_path; ?><?php echo Config::get('theme_path'); ?>/templates/default.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/print.css" type="text/css" media="print" />
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/handheld.css" type="text/css" media="handheld" />
</head>
<body>
<script src="<?php echo $web_path; ?>/lib/javascript-base.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/kajax/ajax.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/prototype/prototype.js" language="javascript" type="text/javascript"></script>
<div id="rfc3514" style="display:none;">0x0</div>
<div id="maincontainer" <?php 
	if ($GLOBALS['theme']['orientation'] == 'horizontal') { 
		echo " class=\"horizontal_menu\" ";
	}else{
		echo " class=\"vertical_menu\" ";
	}?>>
		
	<div id="topbar"><!-- This is the topbar row -->
		<div id="topbarleft">
		<a href="http://www.ampache.org">
		<img src="<?php echo $web_path; ?><?php echo Config::get('theme_path'); ?>/images/ampache.png" title="Ampache: For the love of music" alt="Ampache: For the love of music" />
		</a>
		</div><!--End topbarleft -->
		<div id="topbarright">
			<?php show_box_top('','box box_topbarright'); ?>
			<span id="loginInfo"><?php echo _('You are currently logged in as') . " " . $GLOBALS['user']->fullname; ?></span>
			<?php require_once Config::get('prefix') . '/templates/show_search_bar.inc.php'; ?>
			<?php show_box_bottom(); ?>
		</div> <!-- End topbarright -->
	</div><!-- End topbar -->
	<div id="sidebar"><!-- This is the sidebar -->
		<?php require_once Config::get('prefix') . '/templates/sidebar.inc.php'; ?>
	</div><!-- End sidebar -->
	<div id="rightbar"><!-- This is the rightbar -->
		<?php require_once Config::get('prefix') . '/templates/rightbar.inc.php'; ?>
	</div><!-- End rightbar -->
<!-- I hate IE... 
<table class="smeg-ie" width="100%"><tr><td> -->
<!-- Tiny little iframe, used to cheat the system --> 
<div id="ajax-loading">Loading . . .</div>
<iframe id="util_iframe" style="display:none;" src="<?php echo Config::get('web_path'); ?>/util.php"></iframe>
<div id="content">
<?php if (Config::get('int_config_version') != Config::get('config_version') AND $GLOBALS['user']->has_access(100)) { ?>
<div class="fatalerror">
	<?php echo _('Error Config File Out of Date'); ?>
	<a href="<?php echo Config::get('web_path'); ?>/admin/system.php?action=generate_config"><?php echo _('Generate New Config'); ?></a>
</div>
<?php } ?>
