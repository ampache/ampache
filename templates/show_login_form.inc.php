<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

// show_login_form.inc.php

/* Check and see if their remember me is the same or lower then local
 * if so disable the checkbox
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;

$web_path = AmpConfig::get_web_path();

$t_ampache = T_('Ampache');
$htmllang  = str_replace("_", "-", AmpConfig::get('lang', 'en_US'));
$dir       = (is_rtl(AmpConfig::get('lang', 'en_US')))
    ? 'rtl'
    : 'ltr';

$remember_disabled = (AmpConfig::get('session_length', 3600) >= AmpConfig::get('remember_length', 604800))
    ? 'disabled="disabled"'
    : '';

$user_agent     = Core::get_server('HTTP_USER_AGENT');
$mobile_session = strpos($user_agent, 'Mobile') && (strpos($user_agent, 'Android') || strpos($user_agent, 'iPhone') || strpos($user_agent, 'iPad'));

$logo_url = AmpConfig::get('custom_login_logo');
if (!$logo_url) {
    $logo_url = Ui::get_logo_url();
}

// Init::redirect() hands us the page you actually asked for; fall back to the browser referrer.
// $_POST comes first so a failed attempt re-renders the form with the destination still attached,
// otherwise a wrong password would quietly drop you back to the index page after the retry.
// Only ever emit our own urls, the login action validates this again before redirecting to it.
$referrer = (string) ($_POST['referrer'] ?? $_GET['referrer'] ?? Core::get_server('HTTP_REFERER'));
if (
    $referrer !== ''
    && (
        !str_starts_with($referrer, $web_path)
        // HTTP_REFERER is the login page itself on a retry; that isn't somewhere to send anyone
        || str_contains($referrer, 'login.php')
    )
) {
    $referrer = '';
}

// The mini player button just points the referrer at the mini player, so logging in lands there.
// Flagged when that's already where you're headed, so the button shows it's the current choice.
$mini_url      = $web_path . '/m/';
$mini_referrer = (
    $referrer !== ''
    && (str_starts_with($referrer, $mini_url) || rtrim($referrer, '/') === rtrim($mini_url, '/'))
);

$auth_methods = AmpConfig::get('auth_methods', []);
$oidc_enabled = is_array($auth_methods) && in_array('oidc', $auth_methods, true);

if (
    $oidc_enabled
    && AmpConfig::get('oidc_auto_redirect')
    && !isset($_GET['force_display'])
    && !AmpError::occurred()
    && !headers_sent()
) {
    header('Location: ' . $web_path . '/login.php?action=oidc');

    return;
}

define('TABLE_RENDERED', 1);
if (!AmpConfig::get('disable_xframe_sameorigin', false)) {
    header("X-Frame-Options: SAMEORIGIN");
}
$_SESSION['login'] = true; ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo $dir; ?>">

<head>
    <!-- Propelled by Ampache | ampache.org -->
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset', 'UTF-8'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once Ui::find_template('stylesheets.inc.php'); ?>
    <title><?php echo scrub_out(AmpConfig::get('site_title')); ?></title>
</head>

<body id="loginPage">
    <div id="maincontainer">
        <?php if (!$mobile_session) {
            echo "<div id=\"header\"><!-- This is the header -->";
            echo "<a href=\"" . $web_path . "\" id=\"logo\"><img src=\"" . $logo_url . "\" title=\"" . $t_ampache . "\" alt=\"" . $t_ampache . "\"></a>";
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
                        <input type="hidden" id="referrer" name="referrer" value="<?php echo scrub_out($referrer); ?>" />
                        <input type="hidden" name="action" value="login" />
                    </div>
                </div>
                <div class="loginmessage"><?php echo AmpConfig::get('login_message'); ?></div>
                <div class="loginoptions">
                <?php if (AmpConfig::get('allow_public_registration') && (Mailer::is_mail_enabled() || AmpConfig::get('user_no_email_confirm', false))) { ?>
                            <a class="button nohtml" id="registerbutton" href="<?php echo $web_path; ?>/register.php"><?php echo T_('Register'); ?></a>
                <?php } ?>
                <?php if (Mailer::is_mail_enabled() && AmpConfig::get('allow_lost_password', true)) { ?>
                        <a class="button nohtml" id="lostpasswordbutton" href="<?php echo $web_path; ?>/lostpassword.php"><?php echo T_('Lost Password'); ?></a>
                <?php } ?>
                <?php if (AmpConfig::get('show_mini_player', true)) { ?>
                        <a class="button nohtml<?php echo ($mini_referrer) ? ' selected' : ''; ?>" id="miniplayerbutton" href="<?php echo $web_path; ?>/login.php?referrer=<?php echo urlencode($mini_url); ?>"><?php echo T_('Mini player'); ?></a>
                <?php } ?>
                <?php if ($oidc_enabled) { ?>
                        <a class="button nohtml" id="oidcbutton" href="<?php echo $web_path; ?>/login.php?action=oidc"><?php echo scrub_out(AmpConfig::get('oidc_button_text', T_('Sign in with OpenID Connect'))); ?></a>
                <?php } ?>
                </div>
            </form>
            <script>
                // The server never sees the '#browse.php?...' part of a url, but browsers carry the
                // fragment across the redirect that sent us here, so recover it from the address bar
                // and hand it back as the referrer.
                (function () {
                    var webPath  = "<?php echo addslashes($web_path); ?>";
                    var referrer = document.getElementById('referrer');

                    function keepHash() {
                        var hash = window.location.hash;
                        if (referrer && hash.length > 1 && hash.indexOf('.php') > -1) {
                            referrer.value = webPath + '/index.php' + hash;
                        }
                    }

                    keepHash();
                    document.forms.login.addEventListener('submit', keepHash);
                })();
            </script>
            <?php if ($mobile_session) {
                echo '<div id="mobileheader"><!-- This is the header -->';
                echo "<h1 id=\"logo\"><img src=\"" . $logo_url . "\" title=\"" . $t_ampache . "\" alt=\"" . $t_ampache . "\"></h1>";
                echo '</div>';
            }
if (AmpConfig::get('cookie_disclaimer')) {
    echo '<div id="cookie_notice">';
    echo T_("Ampache places cookies on your computer to help make this website better.");
    echo '<br>';
    echo T_("Cookies are used for core site functionality and are not used for tracking or analytics.");
    echo '<br>';
    echo T_("By logging in you agree to the use of cookies while using this site.");
    echo '</div>';
}
Ui::show_footer();
