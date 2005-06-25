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
<center>
  <h3>
      <?php echo _("Please Ensure All Files Are Tagged Correctly"); ?>!<br />
  </h3>
  <p>
      <?php echo _("Ampache relies on id3 tags to sort data.  If your file is not tagged it may be deleted."); ?>
  </p>

<form action="<?php echo conf('web_path'); ?>/upload.php?" method="post" name="upload_form" enctype="multipart/form-data" id="upload_form">
  <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo _("max_upload_size"); ?>" />
  <table class="tabledata" cellspacing="0" cellpadding="0" border="0" width="90%" align="center">
     <tr align="center">
          <td>
               <input size=40 type="file" name="ul_path1" id="ul_path1" />
          </td>
     </tr>  
     <tr align="center">
          <td>
               <input size=40 type="file" name="ul_path2" id="ul_path2" />
          </td>
     </tr>  
     <tr align="center">
          <td>
               <input size=40 type="file" name="ul_path3" id="ul_path3" />
          </td>
     </tr>  
     <tr align="center">
          <td>
               <input size=40 type="file" name="ul_path4" id="ul_path4" />
          </td>
     </tr>  
     <tr align="center">
          <td>	
		<input type="hidden" name="action" value="upload_now" />
               <input type="submit" value="<?php echo _("Upload"); ?>" />
          </td>
     </tr>  
  </table>
</form>

<table align="center" border = "0">
  <tr>
   <td>
   Acceptable formats include:
   <ul>
     <li>.mp3</li>
     <li>.ogg</li>
     <li>.wma</li>
     <li>.flac</li>
   </ul>
   </td>
  </tr>
</table>
<!-- 
<p> 
  <?php echo _("Minimum Accepted Bitrate is"); ?> <?php echo $site->prefs['upload_bitrate'] ?> kbps
</p>
-->
</center>

