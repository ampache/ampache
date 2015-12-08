<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

?>
<?php if (!defined('INSTALL')) {
    exit;
} ?>
<?php $results = 0; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
    <!-- Propulsed by Ampache | ampache.org -->
    <meta http-equiv="Content-Type" content="text/html; Charset=<?php echo $charset; ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
    <meta http-equiv="pragma" content="no-cache" />

    <title>Ampache :: For the love of Music - Install</title>
    <link href="lib/components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="lib/components/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
    <link rel="stylesheet" href="templates/install-doped.css" type="text/css" media="screen" />
    <script src="lib/components/jquery/jquery.min.js" language="javascript" type="text/javascript"></script>
    <script src="lib/components/bootstrap/js/bootstrap.min.js" language="javascript" type="text/javascript"></script>
</head>
<body>
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="themes/reborn/images/ampache.png" title="Ampache" alt="Ampache">
                <?php echo T_('Ampache Installation'); ?> - For the love of Music
            </a>
        </div>
    </div>
    <div class="container" role="main">
