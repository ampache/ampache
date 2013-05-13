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
<?php if (!defined('INSTALL')) { exit; } ?>
<?php $results = 0; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<title>Ampache :: Pour l'Amour de la Musique - Install</title>
<link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
<meta http-equiv="Content-Type" content="text/html; Charset=<?php echo $charset; ?>" />
</head>
<body>
<script src="modules/prototype/prototype.js" language="javascript" type="text/javascript"></script>
<script src="lib/javascript/base.js" language="javascript" type="text/javascript"></script>
<div id="header">
<h1><?php echo T_('Ampache Installation'); ?></h1>
<p>For the love of Music</p>
</div>
<div id="text-box">
    <div class="notify">
        <h3><?php echo T_('Requirements'); ?></h3>
        <p>
        <?php echo T_('This page handles the installation of the Ampache database and the creation of the ampache.cfg.php file. Before you continue please make sure that you have the following prerequisites:'); ?>
        </p>
        <ul>
            <li><?php echo T_('A MySQL server with a username and password that can create/modify databases'); ?></li>
                        <li><?php echo sprintf(T_('Your webserver has read access to the files %s and %s'),$prefix . '/sql/ampache.sql',$prefix . '/config/ampache.cfg.php.dist'); ?></li>
        </ul>
        <p>
<?php echo sprintf(T_("Once you have ensured that the above requirements are met please fill out the information below. You will only be asked for the required config values. If you would like to make changes to your Ampache install at a later date simply edit %s"), $prefix . '/config/ampache.cfg.php'); ?>
        </p>
</div>
