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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="en-US">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Ampache -- Debug Page</title>
        <link rel="shortcut icon" href="<?php echo $web_path; ?>/favicon.ico" />
        <link href="<?php echo AmpConfig::get('web_path'); ?>/modules/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo AmpConfig::get('web_path'); ?>/modules/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?>/templates/install-doped.css" type="text/css" media="screen" />
    </head>
    <body>
        <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <img src="<?php echo AmpConfig::get('web_path'); ?>/themes/reborn/images/ampache.png" title="Ampache" alt="Ampache">
                    <?php echo T_('Ampache'); ?> - For the love of Music
                </a>
            </div>
        </div>
        <div class="container" role="main">
            <div class="jumbotron">
                <h1><?php echo T_('Access Denied'); ?></h1>
                <p><?php echo T_('This event has been logged.'); ?></p>
            </div>
            <div class="alert alert-danger">
                <?php if (!AmpConfig::get('demo_mode')) { ?>
                <p><?php echo T_('You have been redirected to this page because you do not have access to this function.'); ?></p>
                <p><?php echo T_('If you believe this is an error please contact an Ampache administrator.'); ?></p>
                <p><?php echo T_('This event has been logged.'); ?></p>
                <?php } else { ?>
                <p><?php echo T_("You have been redirected to this page because you attempted to access a function that is disabled in the demo."); ?></p>
                <?php } ?>
            </div>
        </div>
    </body>
</html>
