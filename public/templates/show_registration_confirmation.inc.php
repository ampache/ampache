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
use Ampache\Module\Application\Register\AddUserAction;
use Ampache\Module\Util\Ui;

/** @var AddUserAction $this */

$web_path = AmpConfig::get_web_path();

$t_ampache         = T_('Ampache');
$htmllang          = str_replace("_", "-", AmpConfig::get('lang', 'en_US'));
$_SESSION['login'] = true; ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
    <head>
        <!-- Propelled by Ampache | ampache.org -->
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset', 'UTF-8'); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo AmpConfig::get('site_title') . ' - ' . T_('Registration'); ?></title>
        <?php require_once Ui::find_template('stylesheets.inc.php'); ?>
    </head>
    <body id="registerPage">
        <div id="maincontainer">
            <div id="header">
                <a href="<?php echo $web_path; ?>"><h1 id="logo"><img src="<?php echo Ui::get_logo_url(); ?>" title="<?php echo $t_ampache; ?>" alt="<?php echo $t_ampache; ?>"></h1></a>
            </div>
            <script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js"></script>
            <div id="content">
                <div id="guts">
<?php $url = $web_path . '/login.php';
$text      = T_('Return to Login Page');
if (AmpConfig::get('admin_enable_required')) {
    $text = T_('Please wait for an administrator to activate your account');
}
if (!AmpConfig::get('user_no_email_confirm')) {
    $text = T_('An activation key has been sent to the e-mail address you provided. Please check your e-mail for further information');
}
$this->ui->showConfirmation(T_('Your account has been created'), $text, $url); ?>
                </div>
            </div>
        </div>
<?php Ui::show_footer(); ?>
