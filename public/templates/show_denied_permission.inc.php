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

use Ampache\Config\AmpConfig;
use Ampache\Module\Util\Ui;

/** @var string $fileName */

$logo_url = AmpConfig::get('custom_login_logo', '');
if (empty($logo_url)) {
    $logo_url = Ui::get_logo_url('dark');
}
$web_path = AmpConfig::get_web_path(); ?>
<!DOCTYPE html>
<html lang="en-US">
    <head>
        <!-- Propelled by Ampache | ampache.org -->
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo T_('Ampache') . ' -- ' . T_("Debug Page"); ?></title>
        <?php Ui::show_custom_style(); ?>
        <link rel="stylesheet" href="<?php echo $web_path . '/lib/components/bootstrap/css/bootstrap.min.css'; ?>">
        <link rel="stylesheet" href="<?php echo $web_path . '/lib/components/bootstrap/css/bootstrap-theme.min.css'; ?>">
        <link rel="stylesheet" href="templates/install.css" type="text/css" media="screen">
    </head>
    <body>
        <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="container" style="height: 70px;">
                <a class="navbar-brand" href="<?php echo $web_path; ?>" id="logo">
                    <img src="<?php echo $logo_url; ?>" title="<?php echo T_('Ampache'); ?>" alt="<?php echo T_('Ampache'); ?>">
                    <?php echo scrub_out(AmpConfig::get('site_title')); ?>
                </a>
            </div>
        </div>
        <div id="guts" class="container" role="main">
            <div class="jumbotron" style="margin-top: 70px">
                <h1><?php echo T_('Permission Denied'); ?></h1>
                <p><?php echo $fileName; ?></p>
            </div>
            <div class="alert alert-danger">
                <?php if (!AmpConfig::get('demo_mode')) { ?>
                <p><?php echo T_('You do not have permission to write to this file or folder'); ?></p>
                <?php } else { ?>
                <p><?php echo T_('You have been redirected to this page because you attempted to access a function that is disabled in the demo.'); ?></p>
                <?php } ?>
            </div>
        </div>
    </body>
</html>
