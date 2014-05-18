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
        <link href="modules/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="modules/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
        <link rel="stylesheet" href="templates/install-doped.css" type="text/css" media="screen" />
    </head>
    <body>
        <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <img src="themes/reborn/images/ampache.png" title="Ampache" alt="Ampache">
                    <?php echo T_('Ampache'); ?> - For the love of Music
                </a>
            </div>
        </div>
        <div class="container" role="main">
        <div class="page-header requirements">
            <h1><?php echo T_('Ampache Debug'); ?></h1>
        </div>
        <div class="well">
            <p><?php echo T_('You may have reached this page because a configuration error has occured. Debug information is below.'); ?></p>
        </div>
        <div>
            <table class="table" cellpadding="3" cellspacing="0">
                <tr>
                    <th><?php echo T_('CHECK'); ?></th>
                    <th><?php echo T_('STATUS'); ?></th>
                    <th><?php echo T_('DESCRIPTION'); ?></th>
                </tr>
                <?php require $prefix . '/templates/show_test_table.inc.php'; ?>
            </table>
        </div>
    </body>
</html>
