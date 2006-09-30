<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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

$web_path = conf('web_path');
?>
<?php show_box_top(_('Find Duplicates')); ?>
<form name="songs" action="<?php echo conf('web_path'); ?>/admin/duplicates.php" method="post" enctype="multipart/form-data" >
<table cellspacing="0" cellpadding="3" border="0" width="450">
        <tr>
                <td valign="top"><?php echo _('Search Type'); ?>:</td>
                <td>
                        <?php

                        if ($search_type=="title")
                                $checked = "checked=\"checked\"";
                        else
                                $checked = "";
                        echo "<input type=\"radio\" name=\"search_type\" value=\"title\" ".$checked." />" . _("Title") . "<br />";

                        if ($search_type=="artist_title")
                                                $checked = "checked=\"checked\"";
                        else
                                $checked = "";
                        echo "<input type=\"radio\" name=\"search_type\" value=\"artist_title\" ".$checked." />" . _("Artist and Title") . "<br />";
                        if ($search_type=="artist_album_title"OR $search_type=="")
                                                $checked = "checked=\"checked\"";
                        else
                                $checked = "";
                        echo "<input type=\"radio\" name=\"search_type\" value=\"artist_album_title\"".$checked." />" . _("Artist, Album and Title") . "<br />";
                        ?>
                </td>
        </tr>
        <tr>
                <td></td>
                <td>
                        <input type="hidden" name="action" value="search" />
                        <input type="submit" value="<?php echo _('Search'); ?>" />
                </td>
        </tr>
</table>
</form>
<?php show_box_bottom(); ?>
