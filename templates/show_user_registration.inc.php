<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$htmllang = str_replace("_","-",AmpConfig::get('lang'));
$web_path = AmpConfig::get('web_path');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
        <title><?php echo AmpConfig::get('site_title'); ?> - <?php echo T_('Registration'); ?></title>
        <link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?>/templates/print.css" type="text/css" media="print" />
        <link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?><?php echo AmpConfig::get('theme_path'); ?>/templates/default.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?><?php echo AmpConfig::get('theme_path'); ?>/templates/dark.css" type="text/css" media="screen" />
        <link rel="shortcut icon" href="<?php echo AmpConfig::get('web_path'); ?>/favicon.ico" />
    </head>
    <body id="registerPage">
        <script src="<?php echo $web_path; ?>/modules/jquery/jquery.min.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
        <script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>

        <div id="maincontainer">
            <div id="header">
                <h1 id="headerlogo">
                    <a href="<?php echo AmpConfig::get('web_path'); ?>">
                        <img src="<?php echo AmpConfig::get('web_path'); ?><?php echo AmpConfig::get('theme_path'); ?>/images/ampache.png" title="<?php echo AmpConfig::get('site_title'); ?>" alt="<?php echo AmpConfig::get('site_title'); ?>" />
                    </a>
                </h1>
                <span><?php echo T_('Registration'); ?>...</span>
            </div>
            <?php
            $action = scrub_in($_REQUEST['action']);
            $fullname = scrub_in($_REQUEST['fullname']);
            $username = scrub_in($_REQUEST['username']);
            $email = scrub_in($_REQUEST['email']);
            $website = scrub_in($_REQUEST['website']);
            ?>
            <div id="registerbox">
                <form name="update_user" method="post" action="<?php echo $web_path; ?>/register.php" enctype="multipart/form-data">
                    <?php
                    /*  If we should show the user agreement */
                    if (AmpConfig::get('user_agreement')) { ?>
                    <h3><?php echo T_('User Agreement'); ?></h3>
                    <div class="registrationAgreement">
                        <div class="agreementContent">
                            <?php Registration::show_agreement(); ?>
                        </div>

                        <div class="agreementCheckbox">
                            <input type='checkbox' name='accept_agreement' /> <?php echo T_('I Accept'); ?>
                            <?php Error::display('user_agreement'); ?>
                        </div>
                    </div>
                    <?php } // end if user_agreement ?>
                    <h3><?php echo T_('User Information'); ?></h3>
                    <div class="registerfield require">
                        <label for="username"><?php echo T_('Username'); ?>: <span class="asterix">*</span></label>
                        <input type='text' name='username' id='username' value='<?php echo scrub_out($username); ?>' />
                        <?php Error::display('username'); ?>
                        <?php Error::display('duplicate_user'); ?>
                    </div>
                    <div class="registerfield require">
                        <label for="fullname"><?php echo T_('Full Name'); ?>: <span class="asterix">*</span></label>
                        <input type='text' name='fullname' id='fullname' value='<?php echo scrub_out($fullname); ?>' />
                        <?php Error::display('fullname'); ?>
                    </div>

                    <div class="registerfield require">
                        <label for="email"><?php echo T_('E-mail'); ?>: <span class="asterix">*</span></label>
                        <input type='text' name='email' id='email' value='<?php echo scrub_out($email); ?>' />
                        <?php Error::display('email'); ?>
                    </div>
                    <div class="registerfield require">
                        <label for="email"><?php echo T_('Website'); ?>:</label>
                        <input type='text' name='website' id='website' value='<?php echo scrub_out($website); ?>' />
                        <?php Error::display('website'); ?>
                    </div>

                    <div class="registerfield require">
                        <label for="password"><?php echo T_('Password'); ?>: <span class="asterix">*</span></label>
                        <input type='password' name='password_1' id='password_1' />
                        <?php Error::display('password'); ?>
                    </div>

                    <div class="registerfield require">
                        <label for="confirm_passord"><?php echo T_('Confirm Password'); ?>: <span class="asterix">*</span></label>
                        <input type='password' name='password_2' id='password_2' />
                    </div>

                    <br />
                    <div class="registerInformation">
                        <span><?php echo T_('* Required fields'); ?></span>
                    </div>

                    <?php if (AmpConfig::get('captcha_public_reg')) { ?>
                                <?php  echo captcha::form("&rarr;&nbsp;"); ?>
                                <?php Error::display('captcha'); ?>
                    <?php } ?>

                    <div class="registerButtons">
                        <input type="hidden" name="action" value="add_user" />
                        <input type='submit' name='submit_registration' id='submit_registration' value='<?php echo T_('Register User'); ?>' />
                    </div>
                </form>
<?php
UI::show_footer();
?>
