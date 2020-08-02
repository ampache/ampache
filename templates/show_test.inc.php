<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>
<!DOCTYPE html>
<html lang="en-US">
    <head>
        <!-- Propelled by Ampache | ampache.org -->
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo T_("Ampache") . " -- " . T_("Debug Page"); ?></title>
        <link href="lib/components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="lib/components/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
        <link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
    </head>
    <body>
        <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="container" style="height: 70px;">
                <a class="navbar-brand" href="#">
                    <img src="<?php echo UI::get_logo_url('dark'); ?>" title="<?php echo T_('Ampache'); ?>" alt="<?php echo T_('Ampache'); ?>">
                    <?php echo T_('Ampache') . ' :: ' . T_('For the Love of Music'); ?>
                </a>
            </div>
        </div>
        <div class="container" role="main">
        <div class="page-header requirements">
            <h1><?php echo T_('Ampache Debug'); ?></h1>
        </div>
        <div class="well">
            <p>
                <?php echo T_('You may have reached this page because a configuration error has occurred. Debug information is below.'); ?>
                <?php if (!is_readable($configfile)) { ?>
                | <a href="install.php"><?php echo T_('Web Installation'); ?></a>
                <?php
} ?>
            </p>
        </div>
        <div>
            <table class="table">
                <tr>
                    <th><?php echo T_('Check'); ?></th>
                    <th><?php echo T_('Status'); ?></th>
                    <th><?php echo T_('Description'); ?></th>
                </tr>
                <?php require $prefix . '/templates/show_test_table.inc.php'; ?>
            </table>
        </div>
    </body>
</html>
