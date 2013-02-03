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
?>
<?php if (!defined('INSTALL')) { exit; } ?>

<table border="0" cellspacing="0" cellpadding="3">
<tr>
    <td><font size="+1"><?php echo T_('CHECK'); ?></font></td>
    <td>
        <font size="+1"><?php echo T_('STATUS'); ?></font>
    </td>
    <td><font size="+1"><?php echo T_('DESCRIPTION'); ?></font></td>
</tr>
<?php require $prefix . '/templates/show_test_table.inc.php'; ?>
<tr>
    <td><?php echo sprintf(T_('%s is readable'), 'ampache.cfg.php.dist'); ?></td>
    <td>
    <?php echo debug_result(is_readable($prefix . '/config/ampache.cfg.php.dist')); ?>
    </td>
    <td><?php echo T_('This tests whether the configuration template can be read.'); ?></td>
</tr>
<tr>
    <td><?php echo sprintf(T_('%s is readable'), 'ampache.sql'); ?></td>
    <td>
    <?php echo debug_result(is_readable($prefix . '/sql/ampache.sql')); ?>
    </td>
    <td><?php echo T_('This tests whether the file needed to initialise the database structure is available.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('ampache.cfg.php is writable'); ?></td>
    <td>
    <?php echo debug_result(check_config_writable()); ?>
    </td>
    <td><?php echo T_('This tests whether PHP can write to config/. This is not strictly necessary, but will help streamline the installation process.'); ?></td>
</tr>
</table>
