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

require $prefix . '/templates/install_header.inc.php';
?>
    <div class="content">
    <?php echo T_('Step 1 - Create the Ampache database'); ?><br />
    <?php echo T_('Step 2 - Create ampache.cfg.php'); ?><br />
    <strong><?php echo T_('Step 3 - Set up the initial account'); ?></strong><br />
    <dl>
    <dd><?php echo T_('This step creates your initial Ampache admin account. Once your admin account has been created you will be redirected to the login page.'); ?></dd>
    </dl>
    <?php Error::display('general'); ?>
    <br />
    <span class="header2"><?php echo T_('Create Admin Account'); ?></span>
    <form method="post" action="<?php echo $web_path . "/install.php?action=create_account&amp;htmllang=$htmllang&amp;charset=$charset"; ?>" enctype="multipart/form-data" >
<table>
<tr>
    <td class="align"><?php echo T_('Username'); ?></td>
    <td><input type="text" name="local_username" value="admin" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('Password'); ?></td>
    <td><input type="password" name="local_pass" value="" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('Confirm Password'); ?></td>
    <td><input type="password" name="local_pass2" value="" /></td>
</tr>
<tr>
    <td>&nbsp;</td>
    <td><input type="submit" value="<?php echo T_('Create Account'); ?>" /></td>
</tr>
    </table>
    </form>
    </div>
    <div id="bottom">
        <p><strong>Ampache Installation.</strong><br />
        For the love of Music.</p>
   </div>

</div>
</body>
</html>

