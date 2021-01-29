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

$logo_url = AmpConfig::get('custom_login_logo');
if (empty($logo_url)) {
    $logo_url = UI::get_logo_url('dark');
}

$web_path = AmpConfig::get('web_path'); ?>
<!DOCTYPE html>
<html lang="en-US">
    <head>
        <!-- Propelled by Ampache | ampache.org -->
        <meta http-equiv="refresh" content="10;URL=<?php echo(AmpConfig::get('web_path'));?>" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo T_("Ampache") . ' -- ' . T_("Debug Page"); ?></title>
        <?php UI::show_custom_style(); ?>
        <link href="<?php echo $web_path; ?>/lib/components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo $web_path; ?>/lib/components/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
        <link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
    </head>
    <body>
        <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="container" style="height: 70px;">
                <a class="navbar-brand" href="#">
                    <img src="<?php echo $logo_url; ?>" title="<?php echo T_('Ampache'); ?>" alt="<?php echo T_('Ampache'); ?>">
                    <?php echo scrub_out(AmpConfig::get('site_title')); ?>
                </a>
            </div>
        </div>
        <div id="guts" class="container" role="main">
            <div class="jumbotron" style="margin-top: 70px">
                <h1><?php echo T_('Access Denied'); ?></h1>
                <p><?php echo T_('This event has been logged'); ?></p>
            </div>
            <div class="alert alert-danger">
                <?php if (!AmpConfig::get('demo_mode')) { ?>
                <p><?php echo T_('You have been redirected to this page because you do not have access to this function'); ?></p>
                <p><?php echo T_('If you believe this is an error please contact an Ampache administrator'); ?></p>
                <p><?php echo T_('This event has been logged') . ": " . T_("You will be automatically redirected in 10 seconds."); ?></p>
                <?php
} else { ?>
                <p><?php echo T_("You have been redirected to this page because you attempted to access a function that is disabled in the demo."); ?></p>
                <?php
    } ?>
            </div>
        </div>
    </body>
</html>
