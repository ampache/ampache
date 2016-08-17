<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

$logo_url = AmpConfig::get('custom_login_logo');
if (empty($logo_url)) {
    $logo_url = AmpConfig::get('web_path') . "/themes/reborn/images/ampache.png";
}

$web_path = AmpConfig::get('web_path');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="en-US">
    <head>
        <!-- Propulsed by Ampache | ampache.org -->
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Ampache -- Debug Page</title>
        <?php UI::show_custom_style(); ?>
        <link href="<?php echo $web_path; ?>/lib/components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo $web_path; ?>/lib/components/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo $web_path . UI::find_template('install-doped.css'); ?>" type="text/css" media="screen" />
    </head>
    <body>
        <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <img src="<?php echo $logo_url; ?>" title="Ampache" alt="Ampache">
                    <?php echo AmpConfig::get('site_title'); ?>
                </a>
            </div>
        </div>
        <div id="guts" class="container" role="main">
            <div class="jumbotron">
                <h1><?php echo T_('Access Denied'); ?></h1>
                <p><?php echo T_('This event has been logged.'); ?></p>
            </div>
            <div class="alert alert-danger">
                <?php if (!AmpConfig::get('demo_mode')) {
    ?>
                <p><?php echo T_('You have been redirected to this page because you do not have access to this function.'); ?></p>
                <p><?php echo T_('If you believe this is an error please contact an Ampache administrator.'); ?></p>
                <p><?php echo T_('This event has been logged.'); ?></p>
                <?php 
} else {
    ?>
                <p><?php echo T_("You have been redirected to this page because you attempted to access a function that is disabled in the demo."); ?></p>
                <?php 
} ?>
            </div>
        </div>
    </body>
</html>
