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

/* Check and see if their remember me is the same or lower then local
 * if so disable the checkbox
 */
$remember_disabled = '';
if (AmpConfig::get('session_length') >= AmpConfig::get('remember_length')) {
    $remember_disabled = 'disabled="disabled"';
}
$htmllang                             = str_replace("_","-",AmpConfig::get('lang'));
is_rtl(AmpConfig::get('lang')) ? $dir = 'rtl' : $dir = 'ltr';

$web_path = AmpConfig::get('web_path');

$_SESSION['login'] = true;
define('TABLE_RENDERED', 1);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo $dir; ?>">
    <head>
        <!-- Propulsed by Ampache | ampache.org -->
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
        <?php require_once AmpConfig::get('prefix') . UI::find_template('stylesheets.inc.php'); ?>
        <title> <?php echo AmpConfig::get('site_title'); ?> </title>
        <script type="text/javascript" language="javascript">
            function focus(){ document.login.username.focus(); }
        </script>
    </head>

    <body id="loginPage" onload="focus();">
        <div id="maincontainer">
            <div id="header"><!-- This is the header -->
                <a href="<?php echo $web_path; ?>"><h1 id="headerlogo"></h1></a>
            </div>
            <div id="loginbox">
                <h2><?php echo AmpConfig::get('site_title'); ?></h2>
                <form name="login" method="post" enctype="multipart/form-data" action="<?php echo $web_path; ?>/login.php">
                    <div class="loginfield" id="usernamefield">
                        <label for="username"><?php echo  T_('Username'); ?>:</label>
                        <input class="text_input" type="text" id="username" name="username" value="<?php echo scrub_out($_REQUEST['username']); ?>" />
                    </div>
                    <div class="loginfield" id="passwordfield">
                        <label for="password"><?php echo  T_('Password'); ?>:</label>
                        <input class="text_input" type="password" id="password" name="password" value="" />
                    </div>
                    <div class="loginfield" id="remembermefield"><label for="rememberme">
                        <?php echo T_('Remember Me'); ?>&nbsp;</label><input type="checkbox" id="rememberme" name="rememberme" <?php echo $remember_disabled; ?> />
                    </div>
                    <?php echo AmpConfig::get('login_message'); ?>
                    <?php AmpError::display('general'); ?>

                    <div class="formValidation">
                        <a rel="nohtml" class="button" id="lostpasswordbutton" href="<?php echo $web_path; ?>/lostpassword.php"><?php echo T_('Lost password'); ?></a>
                        <input class="button" id="loginbutton" type="submit" value="<?php echo T_('Login'); ?>" />
                        <input type="hidden" name="referrer" value="<?php echo scrub_out($_SERVER['HTTP_REFERRER']); ?>" />
                        <input type="hidden" name="action" value="login" />

                        <?php if (AmpConfig::get('allow_public_registration')) {
    ?>
                            <a rel="nohtml" class="button" id="registerbutton" href="<?php echo AmpConfig::get('web_path');
    ?>/register.php"><?php echo T_('Register');
    ?></a>
                        <?php 
} // end if allow_public_registration ?>
                    </div>
                </form>
        <?php
        if (@is_readable(AmpConfig::get('prefix') . '/config/motd.php')) {
            ?>
            </div>
            <div id="motd">
        <?php
                UI::show_box_top(T_('Message of the Day'));
            require_once AmpConfig::get('prefix') . '/config/motd.php';
            UI::show_box_bottom();
        }
        UI::show_footer();
        ?>
