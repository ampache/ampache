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
 */
 ?>
<?php if (!defined('INSTALL')) {
     return false;
 } ?>
<?php $results = 0; ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
    <!-- Propelled by Ampache | ampache.org -->
    <meta http-equiv="Content-Type" content="text/html; Charset=<?php echo $charset; ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo T_('Ampache') . ' :: ' . T_('For the Love of Music') . ' - ' . T_('Installation'); ?></title>
    <link href="lib/components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="lib/components/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
    <link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
    <script src="lib/components/jquery/jquery.min.js"></script>
    <script src="lib/components/bootstrap/js/bootstrap.min.js"></script>
</head>
<body id="install-page">
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container" style="height: 70px;">
            <a class="navbar-brand" href="#">
                <img src="./images/ampache-dark.png" title="<?php echo T_('Ampache'); ?>" alt="<?php echo T_('Ampache'); ?>">
                <?php echo T_('Ampache') . ' :: ' . T_('For the Love of Music') . ' - ' . T_('Installation'); ?>
            </a>
        </div>
    </div>
    <div class="container" role="main">
