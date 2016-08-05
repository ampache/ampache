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

$htmllang = str_replace("_", "-", AmpConfig::get('lang'));
$web_path = AmpConfig::get('web_path');

$_SESSION['login'] = true;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
    <head>
        <!-- Propulsed by Ampache | ampache.org -->
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
        <title><?php echo AmpConfig::get('site_title'); ?> - <?php echo T_('Registration'); ?></title>
        <?php require_once AmpConfig::get('prefix') . UI::find_template('stylesheets.inc.php'); ?>
    </head>
    <body id="registerPage">
        <div id="maincontainer">
            <div id="header">
                <a href="<?php echo $web_path; ?>"><h1 id="headerlogo"></h1></a>
                <span><?php echo T_('Registration Complete'); ?>.</span>
            </div>
            <script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js" language="javascript" type="text/javascript"></script>
            <script src="<?php echo $web_path; ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
            <script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>
            <div>
                <?php echo T_('Your account has been created.'); ?>
                <?php
                if (AmpConfig::get('admin_enable_required')) {
                    echo T_('Please wait for an administrator to activate your account.');
                } else {
                    echo T_('An activation key has been sent to the e-mail address you provided. Please check your e-mail for further information.');
                }
                ?>
                <br /><br />
                <a href="<?php echo $web_path; ?>/login.php"><?php echo T_('Return to Login Page'); ?></a>
                <p><?php echo AmpConfig::get('site_title'); ?></p>
            </div>
        </div>
<?php
UI::show_footer();
?>
