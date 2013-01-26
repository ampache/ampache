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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
    xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>"
    dir="<?php echo $dir;?>">

<head>
<meta http-equiv="refresh" content="10;URL=<?php echo($redirect_url);?>" />
<link rel="shortcut icon" href="<?php echo $web_path; ?>/favicon.ico" />
<title><?php echo( T_("Ampache error page"));?></title>
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/base.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path; ?><?php echo Config::get('theme_path'); ?>/templates/default.css" type="text/css" media="screen" />
</head>
<body>
<!-- rfc3514 implementation -->
<div id="rfc3514" style="display: none;">0x0</div>
<div id="maincontainer">
    <div id="header">
        <div id="headerlogo">
            <img src="<?php echo $web_path; echo Config::get('theme_path'); ?>/images/ampache.png"
                 title="<?php echo Config::get('site_title'); ?>"
                 alt="<?php echo Config::get('site_title'); ?>" />
        </div>
    </div>
    <div>&nbsp;</div>
    <div id="errormsg">
        <?php echo (T_("The folowing error has occured, you will automaticly be redirected after 10 seconds.") ); ?>
        <br /><br />
        <?php echo(T_("Error messages"));?>:<br />
        <?php Error::display('general'); ?>
    </div>
</div>
</body>
</html>
