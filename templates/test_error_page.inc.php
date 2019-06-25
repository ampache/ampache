<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
    xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>"
    dir="<?php echo $dir;?>">

<head>
<!-- Propulsed by Ampache | ampache.org -->
<?php UI::show_custom_style(); ?>
<title><?php echo("Ampache error page");?></title>
<link href="lib/components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="lib/components/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
<link rel="stylesheet" href="templates/install-doped.css" type="text/css" media="screen" />
</head>
<body>
<!-- rfc3514 implementation -->
    <div id="rfc3514" style="display: none;">0x0</div>
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="<?php echo UI::get_logo_url('dark'); ?>" title="Ampache" alt="Ampache">
                <?php echo 'Ampache'; ?> - For the love of Music
            </a>
        </div>
    </div>
    <div class="container" role="main">
        <div class="jumbotron">
            <h1><?php echo 'Error'; ?></h1>
            <p><?php echo("Unable to load required dependencies. <a href=\"https://github.com/ampache/ampache/wiki/Installation\" rel=\"nofollow\">Please visit the wiki for installation help</a>"); ?></p>
        </div>
        <?php AmpError::display('general'); ?>
    </div>
</body>
</html>
