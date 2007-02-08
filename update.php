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


/*!
	@header Update Document	
	This document handles updating from one version of Maintain to the next
	if this doc is readable you can't login. stop.update and gone.fishing 
	must also be in place for this doc to work.

*/

/* Start House Keeping */

	// We need this stuff
	define('NO_SESSION','1');
	require('lib/init.php');

	// Make a blank update object
	$update = new Update(0);

	// Get the version and format it
	$version = $update->get_version(); 
	
	$conf['script'] = 'update.php';	
	$prefs['font_size'] = 12;
	$prefs['bg_color1'] = "#c0c0c0";
	$prefs['font'] = "Verdana";
	conf($prefs,1);
	

/* End House Keeping */

if ($_REQUEST['action'] == 'update') { 
	
	/* Run the Update Mojo Here */
	$update->run_update();

	/* Get the New Version */
	$version = $update->get_version();

} 
$htmllang = str_replace("_","-",conf('lang'));

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<link rel="shortcut icon" href="<?php echo conf('web_path'); ?>/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo conf('site_charset'); ?>" />
<?php require_once(conf('prefix') . "/templates/install.css"); ?>
<title>Ampache - Update</title>
</head>
<body>
<div id="header"> 
<h1><?php echo _('Ampache Update'); ?></h1>
<p>For the love of Music</p>
</div>
<div id="text-box">
	<div class="notify">
This page handles all database updates to Ampache starting with 3.2. According to your database your current version is: <?php echo  $update->format_version($version); ; ?>. 
the following updates need to be performed<br /><br />
<div style="font-size:1.2em;font-weight:bold;text-align:center;"><?php $GLOBALS['error']->print_error('general'); ?></div>
	</div>
	<div class="content">
<?php $update->display_update(); ?>

<form method="post" enctype="multipart/form-data" action="<?php echo  conf('web_path'); ; ?>/update.php?action=update">
<?php if ($update->need_update()) { ?><input type="submit" value="Update Now!" /> <?php } ?>
</form>
	</div>
	<div id="bottom">
    	<p><b>Ampache Installation.</b><br />
    	Pour l'Amour de la Musique.</p>
   </div>
</div>
</body>
</html>


