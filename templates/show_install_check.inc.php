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
require $prefix . '/templates/install_header.inc.php';
?>
<?php if (!defined('INSTALL')) { exit; } ?>
        <div class="page-header requirements">
            <h1><?php echo T_('Requirements'); ?></h1>
        </div>
        <div class="well">
            <p>
                <?php echo T_('This page handles the installation of the Ampache database and the creation of the ampache.cfg.php file. Before you continue please make sure that you have the following prerequisites:'); ?>
            </p>
            <ul>
                <li><?php echo T_('A MySQL server with a username and password that can create/modify databases'); ?></li>
                <li><?php echo sprintf(T_('Your webserver has read access to the files %s and %s'),$prefix . '/sql/ampache.sql',$prefix . '/config/ampache.cfg.php.dist'); ?></li>
            </ul>
            <p>
                <?php echo sprintf(T_("Once you have ensured that the above requirements are met please fill out the information below. You will only be asked for the required config values. If you would like to make changes to your Ampache install at a later date simply edit %s"), $prefix . '/config/ampache.cfg.php'); ?>
            </p>
        </div>
<table class="table" cellspacing="0" cellpadding="0">
    <tr>
        <th><?php echo T_('CHECK'); ?></th>
        <th><?php echo T_('STATUS'); ?></th>
        <th><?php echo T_('DESCRIPTION'); ?></th>
    </tr>
    <?php require $prefix . '/templates/show_test_table.inc.php'; ?>
    <tr>
        <td><?php echo sprintf(T_('%s is readable'), 'ampache.cfg.php.dist'); ?></td>
        <td><?php echo debug_result(is_readable($prefix . '/config/ampache.cfg.php.dist')); ?></td>
        <td><?php echo T_('This tests whether the configuration template can be read.'); ?></td>
    </tr>
    <tr>
        <td><?php echo sprintf(T_('%s is readable'), 'ampache.sql'); ?></td>
        <td><?php echo debug_result(is_readable($prefix . '/sql/ampache.sql')); ?></td>
        <td><?php echo T_('This tests whether the file needed to initialise the database structure is available.'); ?></td>
    </tr>
    <tr>
        <td><?php echo T_('ampache.cfg.php is writable'); ?></td>
        <td><?php echo debug_result(check_config_writable()); ?></td>
        <td><?php echo T_('This tests whether PHP can write to config/. This is not strictly necessary, but will help streamline the installation process.'); ?></td>
    </tr>
</table>
<form role="form" method="post" action="<?php echo $web_path . "/install.php?action=init"; ?>" enctype="multipart/form-data" >
    <input type="hidden" name="htmllang" value="<?php echo $htmllang ?>"/>
    <button type="submit" class="btn btn-warning"><?php echo T_('Continue'); ?></button>
</form>
