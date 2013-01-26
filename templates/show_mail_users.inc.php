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

<?php UI::show_box_top(T_('Send E-mail to Users'), 'box box_mail_users'); ?>
<form name="mail" method="post" action="<?php echo Config::get('web_path'); ?>/admin/mail.php?action=send_mail" enctype="multipart/form-data">
<table>
  <tr>
    <td><?php echo T_('Mail to'); ?>:</td>
    <td>
        <select name="to">
                <option value="all" title="Mail Everyone"><?php echo T_('All'); ?></option>
                <option value="users" title="Mail Users"><?php echo T_('User'); ?></option>
                <option value="admins" title="Mail Admins"><?php echo T_('Admin'); ?></option>
        <option value="inactive" title="Mail Inactive Users"><?php echo T_('Inactive Users'); ?>&nbsp;</option>
        </select>
    </td>
  </tr>
<!--
  <tr>
    <td colspan="2">
      <table>
          <tr>
            <td><?php echo T_('Catalog Statistics'); ?>:</td>
            <td>
            <input type="checkbox" name="cat_stats" value="yes" />
            </td>
            <td><?php echo T_('Most Popular Albums'); ?>:</td>
            <td>
                <input type="checkbox" name="pop_albums" value="yes" />
            </td>
          </tr>

          <tr>
            <td><?php echo T_('Latest Artist Additions'); ?>:</td>
            <td>
                <input type="checkbox" name="new_artists" value="yes" />
            </td>
            <td><?php echo T_('Most Popular Artists'); ?>:</td>
            <td>
                <input type="checkbox" name="pop_artists" value="yes" />
            </td>
          </tr>

          <tr>
            <td><?php echo T_('Latest Album Additions'); ?>:</td>
            <td>
                <input type="checkbox" name="new_albums" value="yes" />
            </td>
            <td><?php echo T_('Most Popular Songs'); ?>:</td>
            <td>
                <input type="checkbox" name="pop_songs" value="yes" />
            </td>
        </tr>
          <tr>
            <td><?php echo T_('Flagged Songs'); ?>:</td>
            <td>
                <input type="checkbox" name="flagged" value="yes" />
            </td>
            <td><?php echo T_('Disabled Songs'); ?>:</td>
            <td>
                <input type="checkbox" name="disabled" value="yes" />
            </td>

        </tr>

          <tr>
            <td colspan = "2"><?php echo T_('Most Popular Threshold in days'); ?>:</td>
            <td>
                <input type="text" name="threshold" size="3" value="<?php echo Config::get('popular_threshold'); ?>" />
            </td>
        </tr>

    </table>
    </td>
  </tr>
-->
  <tr>
    <td><?php echo T_('From'); ?>:</td>
    <td>
      <select name="from">
        <option value="self" title="Self"><?php echo T_('Yourself'); ?></option>
    <option value="system" title="System"><?php echo T_('Ampache'); ?></option>
      </select>
    </td>
  </tr>
  <tr>
    <td><?php echo T_('Subject'); ?>:</td>
    <td colspan="3">
        <input name="subject" value="<?php echo scrub_out($_POST['subject']); ?>" size="50"></input>
    </td>
  </tr>

  <tr>
    <td valign="top"><?php echo T_('Message'); ?>:</td>
    <td>
        <textarea class="input" name="message" rows="10" cols="70"></textarea>
    </td>
  </tr>

</table>
<div class="formValidation">
        <input class="button" type="submit" value="<?php echo T_('Send Mail'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>

