<?php
/*      

 Copyright (c) 2001 - 2005 Ampache.org
 All rights reserved.

 *Created by Lamar*

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
?>

<form action="<?php echo conf('web_path'); ?>/upload.php?" method="post" name="upload_form" enctype="multipart/form-data" id="upload_form">
<table class="text-box" cellspacing="0" cellpadding="0" border="0">
<tr>
	<td>
		<h3><?php echo _("Uploading Music to Ampache"); ?><br /></h3>
		<p>
		<b><?php echo _("The following Audio file formats are supported"); ?></b>
		</p>
		<ul>
			<li>OGG</li>
			<li>Mp3</li>
			<li>Mpc</li>
			<li>Flac</li>
			<li>WMA</li>
			<li>Mp4/M4a/AAC</li>
		</ul>
		<?php $GLOBALS['error']->print_error('general'); ?>
	</td>
</tr>
<tr>
	<td>
               <input size=40 type="file" name="ul_path1" id="ul_path1" />
	       <?php $GLOBALS['error']->print_error('ul_path1'); ?>
        </td>
</tr>  
<tr>
	<td>
               <input size=40 type="file" name="ul_path2" id="ul_path2" />
		<?php $GLOBALS['error']->print_error('ul_path2'); ?>
        </td>
</tr>  
<tr>
        <td>
	        <input size=40 type="file" name="ul_path3" id="ul_path3" />
		<?php $GLOBALS['error']->print_error('ul_path3'); ?>
	</td>
</tr>  
<tr>
        <td>
	        <input size=40 type="file" name="ul_path4" id="ul_path4" />
		<?php $GLOBALS['error']->print_error('ul_path3'); ?>
        </td>
</tr>  
<tr>
        <td>	
		<input type="hidden" name="action" value="upload" />
  		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo _("max_upload_size"); ?>" />
               	<input class='button' type="submit" value="<?php echo _("Upload"); ?>" />
          </td>
</tr>  
</table>
</form>

