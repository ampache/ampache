<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
$web_path = conf('web_path');
$htmllang = str_replace("_","-",conf('lang'));
$location = get_location();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">

<head>
<link rel="shortcut icon" href="<?php echo $web_path; ?>/favicon.ico" />
<?php
if (conf('use_rss')) { ?>
<link rel="alternate" type="application/rss+xml" title="<?php echo conf('rss_main_title'); ?>" href="<?php echo $web_path; ?>/rss.php" />
<?php } ?>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo conf('site_charset'); ?>" />
<title><?php echo conf('site_title'); ?> - <?php echo $location['title']; ?></title>
</head>
<body>
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/default.css" type="text/css">
<script src="<?php echo $web_path; ?>/lib/general.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/kajax/ajax.js" language="javascript" type="text/javascript"></script>
<div id="maincontainer">
	<div id="topbar"><!-- This is the topbar row -->
		<div id="topbarleft">
		<a href="http://www.ampache.org">
		<img src="<?php echo $web_path; ?><?php echo conf('theme_path'); ?>/images/ampache.gif" border="0" title="Ampache: For the love of music" alt="Ampache: For the love of music" />
		</a>
		</div><!--End topbarleft -->
		<div id="topbarright">
			<a href="http://www.ampache.org/index.php">Ampache v.<?php echo conf('version'); ?></a><br />
			<b><?php echo _('You are currently logged in as') . " " . $GLOBALS['user']->fullname; ?></b>
		</div> <!-- End topbarright -->
	</div><!-- End topbar -->
	<div id="sidebar"><!-- This is the sidebar -->
		<?php require_once(conf('prefix') . '/templates/sidebar.inc.php'); ?>
		<?php if ($localplay = init_localplay()) { ?>
		<?php require_once(conf('prefix') . '/templates/show_localplay_control.inc.php'); ?>
		<?php } ?>
	</div><!-- End sidebar -->
	<div id="content">
<!-- I hate IE... --> 
<table><tr><td>
