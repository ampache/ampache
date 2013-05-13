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
        <strong><?php echo T_('Step 1 - Create the Ampache database'); ?></strong><br />
        <dl>
            <dd><?php echo T_('This step creates and inserts the Ampache database, so please provide a MySQL account with database creation rights. This step may take some time on slower computers.'); ?></dd>
        </dl>
        <?php echo T_('Step 2 - Create ampache.cfg.php'); ?><br />
        <?php echo T_('Step 3 - Set up the initial account'); ?><br />
        <br />
        <?php Error::display('general'); ?>
        <br />
        <span class="header2"><?php echo T_('Insert Ampache Database'); ?></span>
        <form method="post" action="<?php echo $web_path . "/install.php?action=create_db&amp;htmllang=$htmllang&amp;charset=$charset"; ?>" enctype="multipart/form-data" >
<table>
<tr>
    <td class="align"><?php echo T_('Desired Database Name'); ?></td>
    <td><input type="text" name="local_db" value="ampache" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('MySQL Hostname'); ?></td>
    <td><input type="text" name="local_host" value="localhost" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('MySQL port (optional)'); ?></td>
    <td><input type="text" name="local_port" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('MySQL Administrative Username'); ?></td>
    <td><input type="text" name="local_username" value="root" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('MySQL Administrative Password'); ?></td>
    <td><input type="password" name="local_pass" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('Create Database User for New Database?'); ?></td>
    <td><input type="checkbox" value="create_db_user" name="db_user" onclick="flipField('db_username');flipField('db_password');" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('Ampache Database Username'); ?></td>
    <td><input type="text" id="db_username" name="db_username" value="ampache" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('Ampache Database User Password'); ?></td>
    <td><input type="password" id="db_password" name="db_password" value="" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('Overwrite Existing'); ?></td>
    <td><input type="checkbox" name="overwrite_db" value="1" /></td>
</tr>
<tr>
    <td class="align"><?php echo T_('Use Existing Database'); ?></td>
    <td><input type="checkbox" name="existing_db" value="1" /></td>
</tr>
<tr>
    <td>&nbsp;</td>
    <td><input type="submit" value="<?php echo T_('Insert Database'); ?>" /></td>
</tr>
</table>
</form>
<script type="text/javascript">flipField('db_username');flipField('db_password');</script>
    </div>
    <div id="bottom">
        <p><strong>Ampache Installation.</strong><br />
        Pour l'Amour de la Musique</p>
   </div>
</div>

</body>
</html>
