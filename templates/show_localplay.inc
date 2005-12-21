<?php
/*

 Copyright (c) 2004 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/*!
	@header Show localplay form
*/
$web_path = conf('web_path'); 
?>
    <table class="border" cellspacing="1" cellpadding="3" width="100%" border="0">
      <tr class="table-header">
        <td colspan="2"><?php echo _("Local Play Control"); ?></td>
      </tr>
      <tr class="even"><td>
        <table border="0" cellpadding="0" cellspacing="0"><tr>
        <td>
		<?php echo _("Playback") . ":"; ?>
	</td>
	<td>
        	<form action="<?php echo $web_path; ?>/localplay.php" method="post" name="playcontrol" style="display:inline;">
		<input type="submit" title="<?php echo _("Prev"); ?>" name="submit" value="|&lt; " />
                <input type="submit" title="<?php echo _("Stop"); ?>" name="submit" value=" X " />
                <input type="submit" title="<?php echo _("Play"); ?>" name="submit" value=" &gt; " />
                <input type="submit" title="<?php echo _("Pause"); ?>" name="submit" value=" = " />
                <input type="submit" title="<?php echo _("Next"); ?>" name="submit" value=" &gt;|" />
		</form>
	</td>
	</tr>
	<tr class="even">
	<td>
		<?php echo _("Volume") . ":"; ?>
	</td>
	<td>
        	<form action="<?php echo $web_path; ?>/localplay.php" method="post" name="playcontrol" style="display:inline;">
		<input type="submit" title="<?php echo _("Increase Volume"); ?>" name="submit" value=" +1 " />
		<input type="submit" title="<?php echo _("Increase Volume"); ?>" name="submit" value=" +5 " />
		<input type="submit" title="<?php echo _("Decrease Volume"); ?>" name="submit" value=" -1 " /> 
		<input type="submit" title="<?php echo _("Decrease Volume"); ?>" name="submit" value=" -5 " />
                </form>
        </td>
	</tr>
	<tr class="even">
	<td colspan="2">
		<a href="<?php echo $web_path; ?>/localplay.php?submit=clear"><?php echo _("Clear queue"); ?></a><br />
		<?php  @system(conf('localplay_status')) ?>
	</td></tr>
	</table>
	</td>
      </tr>
    </table>
