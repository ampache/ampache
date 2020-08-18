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

/* Check and see if their remember me is the same or lower then local
 * if so disable the checkbox
 */
if (AmpConfig::get('session_length') >= AmpConfig::get('remember_length')) {
    $remember_disabled = 'disabled="disabled"';
}
$htmllang                             = str_replace("_", "-", AmpConfig::get('lang'));
is_rtl(AmpConfig::get('lang')) ? $dir = 'rtl' : $dir = 'ltr';

$web_path       = AmpConfig::get('web_path');

$_SESSION['login'] = true;
$mobile_session    = false;
$user_agent        = Core::get_server('HTTP_USER_AGENT');

if (strpos($user_agent, 'Mobile') && (strpos($user_agent, 'Android') || strpos($user_agent, 'iPhone') || strpos($user_agent, 'iPad'))) {
    $mobile_session = true;
} ?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo $dir; ?>">

<head>
    <!-- Propelled by Ampache | ampache.org -->
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once AmpConfig::get('prefix') . UI::find_template('stylesheets.inc.php'); ?>
    <title><?php echo scrub_out(AmpConfig::get('site_title')); ?></title>
</head>

<body id="loginPage">
    <div id="maincontainer">
        <?php if (!$mobile_session) {
    echo "<div id=\"header\"><!-- This is the header -->";
    echo "<a href=\"" . $web_path . "\"><h1 id=\"headerlogo\"></h1></a>";
    echo "</div>";
} ?>
        <div id="loginbox">
            <h2><?php echo scrub_out(AmpConfig::get('site_title')); ?></h2>
            <form name="login" method="post" enctype="multipart/form-data" action="<?php echo $web_path ?>/lostpassword.php">
                <div class="loginfield" id="emailfield">
                    <label for="email"><?php echo  T_('E-mail'); ?>:</label>
                    <input type="hidden" id="action" name="action" value="send" />
                    <input type="text" id="email" name="email" autofocus />
                </div>
                <div class="formValidation" id="submit-lostpassword">
                    <input class="button" id="submit-lostpassword-button" type="submit" value="<?php echo T_('Submit'); ?>" />
                </div>
            </form>
            <?php if ($mobile_session) {
    echo "<div id=\"mobileheader\"><!-- This is the header -->";
    echo "<h1 id=\"headerlogo\"></h1>";
    echo "</div>";
} ?>
        </div>
        <?php
        UI::show_footer(); ?>
