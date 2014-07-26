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

/* Check and see if their remember me is the same or lower then local
 * if so disable the checkbox
 */
if (AmpConfig::get('session_length') >= AmpConfig::get('remember_length')) {
    $remember_disabled = 'disabled="disabled"';
}
$htmllang = str_replace("_","-",AmpConfig::get('lang'));
is_rtl(AmpConfig::get('lang')) ? $dir = 'rtl' : $dir = 'ltr';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo $dir; ?>">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
        <link rel="shortcut icon" href="<?php echo AmpConfig::get('web_path'); ?>/favicon.ico" />
        <link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?>/templates/print.css" type="text/css" media="print" />
        <link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?><?php echo AmpConfig::get('theme_path'); ?>/templates/default.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?><?php echo AmpConfig::get('theme_path'); ?>/templates/dark.css" type="text/css" media="screen" />
        <title><?php echo scrub_out(AmpConfig::get('site_title')); ?></title>
        <script type="text/javascript" language="javascript">
            function focus()
            {
                document.login.email.focus();
            }
        </script>
    </head>
    <body id="loginPage" onload="focus();">
        <div id="maincontainer">
            <div id="header"><!-- This is the header -->
                <h1 id="headerlogo">
                    <a href="<?php echo AmpConfig::get('web_path'); ?>/login.php">
                        <img src="<?php echo AmpConfig::get('web_path'); ?><?php echo AmpConfig::get('theme_path'); ?>/images/ampache.png" title="<?php echo AmpConfig::get('site_title'); ?>" alt="<?php echo AmpConfig::get('site_title'); ?>" />
                    </a>
                </h1>
            </div>
            <div id="loginbox">
                <h2><?php echo scrub_out(AmpConfig::get('site_title')); ?></h2>
                <form name="login" method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/lostpassword.php">
                    <div class="loginfield" id="emailfield">
                        <label for="email"><?php echo  T_('Email'); ?>:</label>
                        <input type="hidden" id="action" name="action" value="send" />
                        <input class="text_input" type="text" id="email" name="email" />
                    </div>
                    <input class="button" id="lostpasswordbutton" type="submit" value="<?php echo T_('Submit'); ?>" />
                </form>
            </div>
        </div>
        <div id="footer">
            <a href="http://www.ampache.org/index.php">Ampache v.<?php echo AmpConfig::get('version'); ?></a><br />
            Copyright (c) 2001 - 2014 Ampache.org <?php echo T_('Queries:'); ?><?php echo Dba::$stats['query']; ?>
            <?php echo T_('Cache Hits:'); ?><?php echo database_object::$cache_hit; ?>
        </div>
    </body>
</html>
