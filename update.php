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

// We need this stuff
define('NO_SESSION', 1);
define('OUTDATED_DATABASE_OK', 1);
require_once 'lib/init.php';

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
<title><?php echo T_('Ampache Update'); ?></title>
</head>
<body>
<div id="header">
<h1><?php echo T_('Ampache Update'); ?></h1>
<p>Pour l'Amour de la Musique.</p>
</div>
<div id="text-box">
    <div class="notify">
<?php printf(T_('This page handles all database updates to Ampache starting with <strong>3.3.3.5</strong>. According to your database your current version is: <strong>%s</strong>.'), Update::format_version($version)); ?>
<?php echo T_('the following updates need to be performed'); ?><br /><br />
<div style="font-size:1.2em;font-weight:bold;text-align:center;"><?php Error::display('general'); ?></div>
    </div>
    <div class="content">
<?php Update::display_update(); ?>

<form method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ?>/update.php?action=update">
<?php if (Update::need_update()) { ?><input type="submit" value="<?php echo T_('Update Now!'); ?>" /> <?php } ?>
</form>
    </div>
    <div id="bottom">
        <p><b><?php echo T_('Ampache Installation.'); ?></b><br />
        Pour l'Amour de la Musique.</p>
   </div>
</div>
</body>
</html>


