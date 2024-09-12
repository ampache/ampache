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
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\User\Registration;
use Ampache\Module\Util\Captcha\captcha;
use Ampache\Module\Util\Ui;

/** @var Registration\RegistrationAgreementRendererInterface $registrationAgreementRenderer */

$t_ampache = T_('Ampache');
$htmllang  = str_replace("_", "-", AmpConfig::get('lang', 'en_US'));
$web_path  = AmpConfig::get_web_path('/client');

$display_fields   = (array) AmpConfig::get('registration_display_fields');
$mandatory_fields = (array) AmpConfig::get('registration_mandatory_fields');

$_SESSION['login'] = true; ?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">

<head>
    <!-- Propelled by Ampache | ampache.org -->
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo AmpConfig::get('site_title') . ' - ' . T_('Registration'); ?></title>
    <?php require_once Ui::find_template('stylesheets.inc.php'); ?>
</head>

<body id="registerPage">
    <script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js"></script>

    <div id="maincontainer">
        <div id="header">
            <a href="<?php echo $web_path; ?>">
                <h1 id="logo"><img src="<?php echo Ui::get_logo_url(); ?>" title="<?php echo $t_ampache; ?>" alt="<?php echo $t_ampache; ?>"></h1>
            </a>
        </div>
<?php $action    = scrub_in(Core::get_request('action'));
$fullname        = scrub_in(Core::get_request('fullname'));
$fullname_public = (Core::get_request('fullname_public') === "1");
$username        = scrub_in(Core::get_request('username'));
$email           = scrub_in(Core::get_request('email'));
$website         = scrub_in(Core::get_request('website'));
$state           = scrub_in(Core::get_request('state'));
$city            = scrub_in(Core::get_request('city')); ?>
        <div id="content">
            <div id="registerbox">
                <h2><?php echo T_('Registration'); ?></h2>
                <form name="update_user" method="post" action="<?php echo $web_path; ?>/register.php" enctype="multipart/form-data">
                    <?php
            /*  If we should show the user agreement */
            if (AmpConfig::get('user_agreement')) { ?>
                        <h3><?php echo T_('User Agreement'); ?></h3>
                        <div class="registrationAgreement">
                            <div class="agreementContent">
                                <?php echo $registrationAgreementRenderer->render(); ?>
                            </div>
                        </div>
                    <?php
            } ?>

                    <div class="registerInformation">
                        <p><span class="require">* </span><?php echo T_('Required fields'); ?></p>
                    </div>
                    <div class="registerfield require">
                        <label for="username"><?php echo T_('Username'); ?>:</label>
                        <input id="username" type="text" name="username" maxlength="128" value="<?php echo scrub_out((string) $username); ?>" />
                        <?php echo AmpError::display('username'); ?>
                        <?php echo AmpError::display('duplicate_user'); ?>
                    </div>
                    <?php if (in_array('fullname', $display_fields)) { ?>
                        <div class="registerfield <?php if (in_array('fullname', $mandatory_fields)) {
                            echo 'require';
                        } ?>">
                            <label for="fullname"><?php echo T_('Full Name'); ?>:</label>
                            <input id="fullname" type="text" name="fullname" maxlength="255" value="<?php echo scrub_out((string) $fullname); ?>" />
                            <?php echo AmpError::display('fullname'); ?>
                        </div>
                    <?php } ?>

                    <div class="registerfield require">
                        <label for="email"><?php echo T_('E-mail'); ?>:</label>
                        <input id="email" type="text" name="email" maxlength="128" value="<?php echo scrub_out((string) $email); ?>" />
                        <?php echo AmpError::display('email'); ?>
                    </div>
                    <?php if (in_array('website', $display_fields)) { ?>
                        <div class="registerfield <?php if (in_array('website', $mandatory_fields)) {
                            echo 'require';
                        } ?>">
                            <label for="website"><?php echo T_('Website'); ?>:</label>
                            <input id="website" type="text" name="website" maxlength="255" value="<?php echo scrub_out((string) $website); ?>" />
                            <?php echo AmpError::display('website'); ?>
                        </div>
                    <?php } ?>
                    <?php if (in_array('state', $display_fields)) { ?>
                        <div class="registerfield <?php if (in_array('state', $mandatory_fields)) {
                            echo 'require';
                        } ?>">
                            <label for="state"><?php echo T_('State'); ?>:</label>
                            <input id="state" type="text" name="state" maxlength="64" value="<?php echo scrub_out((string) $state); ?>" />
                            <?php echo AmpError::display('state'); ?>
                        </div>
                    <?php } ?>
                    <?php if (in_array('city', $display_fields)) { ?>
                        <div class="registerfield <?php if (in_array('city', $mandatory_fields)) {
                            echo 'require';
                        } ?>">
                            <label for="city"><?php echo T_('City'); ?>:</label>
                            <input id="city" type="text" name="city" maxlength="64" value="<?php echo scrub_out((string) $city); ?>" />
                            <?php echo AmpError::display('city'); ?>
                        </div>
                    <?php } ?>

                    <div class="registerfield require">
                        <label for="password_1"><?php echo T_('Password'); ?>:</label>
                        <input id="password_1" type="password" name="password_1" maxlength="64" />
                        <?php echo AmpError::display('password'); ?>
                    </div>

                    <div class="registerfield require">
                        <label for="password_2"><?php echo T_('Confirm Password'); ?>:</label>
                        <input id="password_2" type="password" name="password_2" maxlength="64" />
                    </div>

                    <?php if (AmpConfig::get('captcha_public_reg')) {
                        echo captcha::form("&nbsp;");
                        echo AmpError::display('captcha');
                    } ?>
                    <div class="submit-registration">
                        <?php if (AmpConfig::get('user_agreement')) { ?>
                            <div id="agreementCheckbox">
                                <label for="accept_agreement"><?php echo T_('I Accept'); ?><span class=alert-danger> *</span></label>
                                <input id="accept_agreement" type="checkbox" name="accept_agreement" />
                            </div>
                            <?php } ?>
                        <div id="submit-registration-button">
                            <input type="hidden" name="action" value="add_user" />
                            <input id="submit_registration" type="submit" name="submit_registration" value="<?php echo T_('Register'); ?>" />
                        </div>
                        <?php echo AmpError::display('user_agreement'); ?>
                    </div>
                </form>
            </div>

            <?php
            Ui::show_footer(); ?>
