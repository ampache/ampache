<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>

<?php show_box_top(_('Send E-mail to Users')); ?>
<form name="mail" method="post" action="<?php echo Config::get('web_path'); ?>/admin/mail.php?action=send_mail" enctype="multipart/form-data">
<table>
  <tr>
    <td><?php echo _('Mail to'); ?>:</td>
    <td>
        <select name="to">
                <option value="all" title="Mail Everyone"><?php echo _('All'); ?></option>
                <option value="users" title="Mail Users"><?php echo _('User'); ?></option>
                <option value="admins" title="Mail Admins"><?php echo _('Admin'); ?></option>
		<option value="inactive" title="Mail Inactive Users"><?php echo _('Inactive Users'); ?>&nbsp;</option>
        </select>
    </td>
  </tr>
<!--
  <tr>
    <td colspan="2">	
 	 <table>
 		 <tr>
		    <td><?php echo _('Catalog Statistics'); ?>:</td>
		    <td>
			<input type="checkbox" name="cat_stats" value="yes" />
		    </td>
		    <td><?php echo _('Most Popular Albums'); ?>:</td>
		    <td>
		        <input type="checkbox" name="pop_albums" value="yes" />
		    </td>
		  </tr>

		  <tr>
		    <td><?php echo _('Latest Artist Additions'); ?>:</td>
		    <td>
		        <input type="checkbox" name="new_artists" value="yes" />
		    </td>
		    <td><?php echo _('Most Popular Artists'); ?>:</td>
		    <td>
		        <input type="checkbox" name="pop_artists" value="yes" />
		    </td>
		  </tr>
		
		  <tr>
		    <td><?php echo _('Latest Album Additions'); ?>:</td>
		    <td>
		        <input type="checkbox" name="new_albums" value="yes" />
		    </td>
		    <td><?php echo _('Most Popular Songs'); ?>:</td>
		    <td>
		        <input type="checkbox" name="pop_songs" value="yes" />
		    </td>
		</tr>
		  <tr>
		    <td><?php echo _('Flagged Songs'); ?>:</td>
		    <td>
		        <input type="checkbox" name="flagged" value="yes" />
		    </td>
		    <td><?php echo _('Disabled Songs'); ?>:</td>
		    <td>
		        <input type="checkbox" name="disabled" value="yes" />
		    </td>

		</tr>

		  <tr>
		    <td colspan = "2"><?php echo _('Most Popular Threshold in days'); ?>:</td>
		    <td>
		        <input type="text" name="threshold" size="3" value="<?php echo Config::get('popular_threshold'); ?>" />
		    </td>
		</tr>

	</table>
    </td>
  </tr>
-->
  <tr>
    <td><?php echo _('Subject'); ?>:</td>
    <td colspan="3">
        <input name="subject" value="<?php echo scrub_out($_POST['subject']); ?>" size="50"></input>
    </td>
  </tr>

  <tr>
    <td valign="top"><?php echo _('Message'); ?>:</td>
    <td>
        <textarea class="input" name="message" rows="10" cols="70"></textarea>
    </td>
  </tr>

</table>
<div class="formValidation">
        <input class="button" type="submit" value="<?php echo _('Send Mail'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>

