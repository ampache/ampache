<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
<link href="modules/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="modules/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
<link rel="stylesheet" href="templates/install-doped.css" type="text/css" media="screen" />
</head>
<body>
<!-- rfc3514 implementation -->
    <div id="rfc3514" style="display: none;">0x0</div>
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="themes/reborn/images/ampache.png" title="Ampache" alt="Ampache">
                <?php echo T_('Ampache'); ?> - For the love of Music
            </a>
        </div>
    </div>
    <div class="container" role="main">
        <div class="jumbotron">
            <h1><?php echo T_('Error'); ?></h1>
            <p><?php echo (T_("The folowing error has occured, you will automaticly be redirected after 10 seconds.") ); ?></p>
        </div>
        <h2><?php echo(T_("Error messages"));?>:</h2>
        <?php Error::display('general'); ?>
    </div>
</body>
</html>
