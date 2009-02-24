<?php
/*

 Copyright (c) Ampache.org
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
// We need this stuff
define('NO_SESSION','1');
require 'lib/init.php';

// Get the version and format it
$version = Update::get_version(); 

if ($_REQUEST['action'] == 'update') { 
	
	/* Run the Update Mojo Here */
	Update::run_update();

	/* Get the New Version */
	$version = Update::get_version();

} 
$htmllang = str_replace("_","-",Config::get('lang'));

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<link rel="shortcut icon" href="<?php echo Config::get('web_path'); ?>/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo Config::get('site_charset'); ?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?php echo Config::get('web_path') . '/templates/install.css'; ?>" />
<title><?php echo _('Ampache Update'); ?></title>
</head>
<body>
<div id="header"> 
<h1><?php echo _('Ampache Update'); ?></h1>
<p>Pour l'Amour de la Musique.</p>
</div>
<div id="text-box">
	<div class="notify">
<?php printf(_('This page handles all database updates to Ampache starting with <strong>3.3.3.5</strong>. According to your database your current version is: <strong>%s</strong>.'), Update::format_version($version)); ?>
<?php echo _('the following updates need to be performed'); ?><br /><br />
<div style="font-size:1.2em;font-weight:bold;text-align:center;"><?php Error::display('general'); ?></div>
	</div>
	<div class="content">
<?php Update::display_update(); ?>

<form method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ?>/update.php?action=update">
<?php if (Update::need_update()) { ?><input type="submit" value="<?php echo _('Update Now!'); ?>" /> <?php } ?>
</form>
	</div>
	<div id="bottom">
    	<p><b><?php echo _('Ampache Installation.'); ?></b><br />
    	Pour l'Amour de la Musique.</p>
   </div>
</div>
</body>
</html>


