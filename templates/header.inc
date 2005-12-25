<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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

$htmllang = str_replace("_","-",conf('lang'));
$location = get_location();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<link rel="shortcut icon" href="<?php echo conf('web_path'); ?>/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo conf('site_charset'); ?>" />
<?php show_template('style'); ?>
<title><?php echo conf('site_title'); ?> - <?php echo $location['title']; ?></title>
</head>
<body>
<script src="<?php echo conf('web_path'); ?>/lib/general.js" language="javascript" type="text/javascript"></script>

<div id="maincontainer">
<!-- This is the topbar row -->
<div id="topbar">
	<div id="topbarleft">
	<a href="http://www.ampache.org">
        <img class="pageheader" src="<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache.gif" border="0" title="Ampache: For the love of music" alt="Ampache: For the love of music" />
	</a>
	</div>
	<div id="topbarright">
			<a href="http://www.ampache.org/index.php">Ampache v.<?php echo conf('version'); ?></a><br />
			<b><?php echo _("You are currently logged in as") . " " . $GLOBALS['user']->username; ?></b>
			<br />
		<?php echo _("Browse"); ?>:
		<form method="post" action="<?php echo conf('web_path'); ?>/browse.php" enctype="multipart/form-data" style="Display:inline;">
		<select name="action">
			<option value="album">Album</option>
			<option value="artist">Artist</option>
			<option value="genre">Genre</option>
		</select>
		<input type="submit" value="<?php echo _("Go!"); ?>" class="button" />
		</form>
	</div>
</div>
<!-- This is the row for body + sidebar -->
<div id="sidebar">
	<!-- This is the sidebar -->
	<?php require_once(conf('prefix') . '/templates/sidebar.inc.php'); ?>
</div>
	<div id="content">
	<table><tr><td>
	<!-- Start Main Page -->
