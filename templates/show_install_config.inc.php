<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

// Try to guess the web path
$web_path_guess = rtrim(dirname($_SERVER['PHP_SELF']), '\/');

require $prefix . '/templates/install_header.inc.php';
?>
    <div class="content">
        <?php echo T_('Step 1 - Create the Ampache database'); ?><br />
        <strong><?php echo T_('Step 2 - Create ampache.cfg.php'); ?></strong><br />
        <dl>
        <dd><?php printf(T_('This step takes the basic config values and generates the config file. If your config/ directory is writable, you can select "write" to have Ampache write the config file directly to the correct location. If you select "download" it will prompt you to download the config file, and you can then manually place the config file in %s'), $prefix . '/config'); ?></dd>
        </dl>
        <?php echo T_('Step 3 - Set up the initial account'); ?><br />
        <?php Error::display('general'); ?>
        <br />

<span class="header2"><?php echo T_('Generate Config File'); ?></span>
<?php Error::display('config'); ?>
<form method="post" action="<?php echo $web_path . "/install.php?action=create_config"; ?>" enctype="multipart/form-data" >
<table>
<tr>
    <td class="align"><?php echo T_('Web Path'); ?></td>
    <td class="align"><input type="text" name="web_path" value="<?php echo scrub_out($web_path_guess); ?>" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('Database Name'); ?></td>
    <td class="align"><input type="text" name="local_db" value="<?php echo scrub_out($_REQUEST['local_db']); ?>" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('MySQL Hostname'); ?></td>
    <td class="align"><input type="text" name="local_host" value="<?php echo scrub_out($_REQUEST['local_host']); ?>" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('MySQL port (optional)'); ?></td>
    <td><input type="text" name="local_port" value="<?php echo scrub_out($_REQUEST['local_port']);?>" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('MySQL Username'); ?></td>
    <td class="align"><input type="text" name="local_username" value="<?php echo scrub_out($_REQUEST['local_username']); ?>" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('MySQL Password'); ?></td>
    <td class="align"><input type="password" name="local_pass" value="" /></td>
</tr>
<tr>
    <td>&nbsp;</td>
    <td>
        <input type="submit" name="download" value="<?php echo T_('Download'); ?>" />
        <input type="submit" name="write" value="<?php echo T_('Write'); ?>" <?php if (!check_config_writable()) { echo "disabled "; } ?>/>
        <input type="hidden" name="htmllang" value="<?php echo $htmllang; ?>" />
        <input type="hidden" name="charset" value="<?php echo $charset; ?>" />
    </td>
</tr>
        </table>
        </form>
        <br />
        <table>
<tr>
        <td class="align"><?php echo T_('ampache.cfg.php exists?'); ?></td>
        <td>
        <?php echo debug_result(is_readable($configfile)); ?>
        </td>
</tr>
<tr>
        <td class="align">
                <?php echo T_('ampache.cfg.php configured?'); ?>
        </td>
        <td>
        <?php
                $results = @parse_ini_file($configfile);
                echo debug_result(check_config_values($results));
        ?>
        </td>
</tr>
<tr>
    <td>&nbsp;</td>
    <td>
    <?php $check_url = $web_path . "/install.php?action=show_create_config&amp;htmllang=$htmllang&amp;charset=$charset&amp;local_db=" . $_REQUEST['local_db'] . "&amp;local_host=" . $_REQUEST['local_host']; ?>
    <a href="<?php echo $check_url; ?>">[<?php echo T_('Recheck Config'); ?>]</a>
    </td>
        </tr>
        </table>
        <br />
        <form method="post" action="<?php echo $web_path . "/install.php?action=show_create_account&amp;htmllang=$htmllang&amp;charset=$charset"; ?>" enctype="multipart/form-data">
        <input type="submit" value="<?php echo T_('Continue to Step 3'); ?>" />
        </form>
    </div>
    <div id="bottom">
        <p><strong>Ampache Installation.</strong><br />
        For the love of Music.</p>
    </div>
</div>

</body>
</html>

