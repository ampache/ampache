<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Module\System\AmpError;
use Ampache\Module\Util\Ui;

/** @var string $redirect_url */

$t_ampache = T_('Ampache');
$htmllang  = $htmllang ?? 'en_US';
$dir       = $dir ?? 'ltr'; ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo $dir; ?>">
<head>
<!-- Propelled by Ampache | ampache.org -->
<meta http-equiv="refresh" content="10;URL=<?php echo $redirect_url; ?>" />
<?php Ui::show_custom_style(); ?>
<title><?php echo T_('Ampache Error Page'); ?></title>
<link rel="stylesheet" href="themes/reborn/templates/default.css">
<link rel="stylesheet" href="lib/components/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="templates/install.css" type="text/css" media="screen">
</head>
<body>
    <!-- rfc3514 implementation -->
    <div id="rfc3514" style="display: none;">0x0</div>
    <div id="reloader" style="display: none;"></div>
    <div class="navbar navbar-dark navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container" style="height: 70px;">
            <a class="navbar-brand" href="#">
                <img src="./images/ampache-dark.png" title="<?php echo $t_ampache; ?>" alt="<?php echo $t_ampache; ?>">
                <?php echo $t_ampache . ' :: ' . T_('For the Love of Music'); ?>
            </a>
        </div>
    </div>
    <div class="container" role="main">
        <div class="jumbotron" style="margin-top: 70px">
            <h1><?php echo T_('Error'); ?></h1>
            <p><?php echo(T_("The following error has occurred, you will be automatically redirected in 10 seconds.")); ?></p>
        </div>
        <h2><?php echo(T_("Error Messages")); ?>:</h2>
        <?php echo AmpError::display('general'); ?>
    </div>
</body>
</html>
