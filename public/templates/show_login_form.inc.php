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

/* Check and see if their remember me is the same or lower then local
 * if so disable the checkbox
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;

$t_ampache = T_('Ampache');
$web_path = AmpConfig::get_web_path();
$htmllang = str_replace("_", "-", AmpConfig::get('lang', 'en_US'));
$dir      = is_rtl(AmpConfig::get('lang', 'en_US'))
    ? 'rtl'
    : 'ltr';

$remember_disabled = (AmpConfig::get('session_length', 3600) >= AmpConfig::get('remember_length', 604800))
    ? 'disabled="disabled"'
    : '';

$user_agent     = Core::get_server('HTTP_USER_AGENT');
$mobile_session = strpos($user_agent, 'Mobile') && (strpos($user_agent, 'Android') || strpos($user_agent, 'iPhone') || strpos($user_agent, 'iPad'));

define('TABLE_RENDERED', 1);
if (!AmpConfig::get('disable_xframe_sameorigin', false)) {
    header("X-Frame-Options: SAMEORIGIN");
}
$_SESSION['login'] = true; ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo $dir; ?>">

<head>
    <!-- Propelled by Ampache | ampache.org -->
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once Ui::find_template('stylesheets.inc.php'); ?>
    <title><?php echo scrub_out(AmpConfig::get('site_title')); ?></title>
</head>

<body id="loginPage">
    <div id="maincontainer">
        <?php if (!$mobile_session) {
            echo "<div id=\"header\"><!-- This is the header -->";
            echo "<a href=\"" . $web_path . "\" id=\"headerlogo\"><img src=\"" . Ui::get_logo_url() . "\" title=\"" . $t_ampache . "\" alt=\"" . $t_ampache . "\"></a>";
            echo "</div>";
        } ?>
        <div id="loginbox">
            <h2><?php echo scrub_out(AmpConfig::get('site_title')); ?></h2>
            <form name="login" method="post" enctype="multipart/form-data" action="<?php echo $web_path; ?>/login.php">
                <div class="loginfield" id="usernamefield">
                    <label for="username"><?php echo T_('Username'); ?>:</label>
                    <input type="text" id="username" name="username" maxlength="128" value="<?php echo scrub_out(Core::get_request('username')); ?>" autofocus />
                </div>
                <div class="loginfield" id="passwordfield">
                    <label for="password"><?php echo T_('Password'); ?>:</label>
                    <input id="password" type="password" name="password" maxlength="64" value="" />
                </div>
                <?php echo AmpError::display('general'); ?>
                <div class="loginfield">
                    <div id="remembermefield">
                        <label for="rememberme"><?php echo T_('Remember Me'); ?></label>
                        <input type="checkbox" id="rememberme" name="rememberme" <?php echo $remember_disabled; ?> />
                    </div>
                    <div class="formValidation">
                        <input class="button" id="loginbutton" type="submit" value="<?php echo T_('Login'); ?>" />
                        <input type="hidden" name="referrer" value="<?php echo scrub_out(Core::get_server('HTTP_REFERER')); ?>" />
                        <input type="hidden" name="action" value="login" />
                    </div>
                </div>
                <div class="loginmessage"><?php echo AmpConfig::get('login_message'); ?></div>
                <div class="loginoptions">
                <?php if (AmpConfig::get('allow_public_registration')) { ?>
                            <a class="button nohtml" id="registerbutton" href="<?php echo $web_path; ?>/register.php"><?php echo T_('Register'); ?></a>
                <?php } ?>
                <?php if (Mailer::is_mail_enabled()) { ?>
                        <a class="button nohtml" id="lostpasswordbutton" href="<?php echo $web_path; ?>/lostpassword.php"><?php echo T_('Lost Password'); ?></a>
                <?php } ?>
                </div>
            </form>
            <?php if ($mobile_session) {
                echo '<div id="mobileheader"><!-- This is the header -->';
                echo "<h1 id=\"headerlogo\"><img src=\"" . Ui::get_logo_url() . "\" title=\"" . $t_ampache . "\" alt=\"" . $t_ampache . "\"></h1>";
                echo '</div>';
            }
if (AmpConfig::get('cookie_disclaimer')) {
    echo '<div id="cookie_notice>';
    echo T_("Ampache places cookies on your computer to help make this website better.");
    echo '</br>';
    echo T_("Cookies are used for core site functionality and are not used for tracking or analytics.");
    echo '</br>';
    echo T_("By logging in you agree to the use of cookies while using this site.");
    echo '</div>';
}
UI::show_footer();
