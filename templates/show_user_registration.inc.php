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

$display_fields   = (array) AmpConfig::get('registration_display_fields');
$mandatory_fields = (array) AmpConfig::get('registration_mandatory_fields');

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
        <script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>

        <div id="maincontainer">
            <div id="header">
                <a href="<?php echo $web_path ?>"><h1 id="headerlogo"></h1></a>
                <span><?php echo T_('Registration'); ?>...</span>
            </div>
            <?php
            $action          = scrub_in($_REQUEST['action']);
            $fullname        = scrub_in($_REQUEST['fullname']);
            $fullname_public = ($_REQUEST['fullname_public'] === "1");
            $username        = scrub_in($_REQUEST['username']);
            $email           = scrub_in($_REQUEST['email']);
            $website         = scrub_in($_REQUEST['website']);
            $state           = scrub_in($_REQUEST['state']);
            $city            = scrub_in($_REQUEST['city']);
            ?>
            <div id="registerbox">
                <form name="update_user" method="post" action="<?php echo $web_path; ?>/register.php" enctype="multipart/form-data">
                    <?php
                    /*  If we should show the user agreement */
                    if (AmpConfig::get('user_agreement')) {
                        ?>
                    <h3><?php echo T_('User Agreement'); ?></h3>
                    <div class="registrationAgreement">
                        <div class="agreementContent">
                            <?php Registration::show_agreement(); ?>
                        </div>

                        <div class="agreementCheckbox">
                            <input type='checkbox' name='accept_agreement' /> <?php echo T_('I Accept'); ?>
                            <?php AmpError::display('user_agreement'); ?>
                        </div>
                    </div>
                    <?php 
                    } // end if user_agreement ?>
                    <h3><?php echo T_('User Information'); ?></h3>
                    <div class="registerfield require">
                        <label for="username"><?php echo T_('Username'); ?>:</label>
                        <input type='text' name='username' id='username' value='<?php echo scrub_out($username); ?>' />
                        <?php AmpError::display('username'); ?>
                        <?php AmpError::display('duplicate_user'); ?>
                    </div>
                    <?php if (in_array('fullname', $display_fields)) {
    ?>
                        <div class="registerfield <?php if (in_array('fullname', $mandatory_fields)) {
    echo 'require';
} ?>">
                            <label for="fullname"><?php echo T_('Full Name'); ?>:</label>
                            <input type='text' name='fullname' id='fullname' value='<?php echo scrub_out($fullname); ?>' />
                            <?php AmpError::display('fullname'); ?>
                        </div>
                    <?php 
} ?>

                    <div class="registerfield require">
                        <label for="email"><?php echo T_('E-mail'); ?>:</label>
                        <input type='text' name='email' id='email' value='<?php echo scrub_out($email); ?>' />
                        <?php AmpError::display('email'); ?>
                    </div>
                    <?php if (in_array('website', $display_fields)) {
    ?>
                        <div class="registerfield <?php if (in_array('website', $mandatory_fields)) {
    echo 'require';
} ?>">
                            <label for="website"><?php echo T_('Website'); ?>:</label>
                            <input type='text' name='website' id='website' value='<?php echo scrub_out($website); ?>' />
                            <?php AmpError::display('website'); ?>
                        </div>
                    <?php 
} ?>
                    <?php if (in_array('state', $display_fields)) {
    ?>
                        <div class="registerfield <?php if (in_array('state', $mandatory_fields)) {
    echo 'require';
} ?>">
                            <label for="state"><?php echo T_('State'); ?>:</label>
                            <input type='text' name='state' id='state' value='<?php echo scrub_out($state); ?>' />
                            <?php AmpError::display('state'); ?>
                        </div>
                    <?php 
} ?>
                    <?php if (in_array('city', $display_fields)) {
    ?>
                        <div class="registerfield <?php if (in_array('city', $mandatory_fields)) {
    echo 'require';
} ?>">
                            <label for="city"><?php echo T_('City'); ?>:</label>
                            <input type='text' name='city' id='city' value='<?php echo scrub_out($city); ?>' />
                            <?php AmpError::display('city'); ?>
                        </div>
                    <?php 
} ?>

                    <div class="registerfield require">
                        <label for="password"><?php echo T_('Password'); ?>:</label>
                        <input type='password' name='password_1' id='password_1' />
                        <?php AmpError::display('password'); ?>
                    </div>

                    <div class="registerfield require">
                        <label for="confirm_passord"><?php echo T_('Confirm Password'); ?>:</label>
                        <input type='password' name='password_2' id='password_2' />
                    </div>

                    <br />
                    <div class="registerInformation">
                        <span><?php echo T_('* Required fields'); ?></span>
                    </div>

                    <?php
                    if (AmpConfig::get('captcha_public_reg')) {
                        echo captcha::form("&rarr;&nbsp;");
                        AmpError::display('captcha');
                    }
                    ?>

                    <div class="registerButtons">
                        <input type="hidden" name="action" value="add_user" />
                        <input type='submit' name='submit_registration' id='submit_registration' value='<?php echo T_('Register User'); ?>' />
                    </div>
                </form>
<?php
UI::show_footer();
?>
