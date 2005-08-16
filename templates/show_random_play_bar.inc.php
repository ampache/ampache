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
/**
 * random play bar
 * this is the simple random play bar, it is short sweet and to the point 
 */
?>
  <form name="random" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/song.php">
  <input type="hidden" name="action" value="m3u" />
  <table class="border" border="0" cellpadding="3" cellspacing="1" width="100%">
  <tr class="table-header">
<td colspan="6"><?php echo _("Play Random Selection"); ?></td>
  </tr>
  <tr class="even">
  <td>
  <table border="0">
<tr class="even">
<td>
   <select name="random">
   <option value="1">1</option>
   <option value="5">5</option>
   <option value="10">10</option>
   <option value="20">20</option>
   <option value="30">30</option>
   <option value="50">50</option>
   <option value="100">100</option>
   <option value="500">500</option>
   <option value="1000">1000</option>
   <option value="-1"><?php echo _("All"); ?></option>
   </select> &nbsp &nbsp
  <?php show_genre_pulldown( -1, 0, "1" ); ?>
   <select name="Quantifier">
   <option value="Songs"><?php echo _("Songs"); ?></option>
   <option value="Minutes"><?php echo _("Minutes"); ?></option>
   <option value="Full Artists"><?php echo _("Full Artists"); ?></option>
   <option value="Full Albums"><?php echo _("Full Albums"); ?></option>
   <option value="Less Played"><?php echo _("Less Played"); ?></option>
   </select>
<?php echo _("from"); ?>
  <?php show_catalog_pulldown( -1, 0); ?>
   <input type="hidden" name="aaction" value="Play!" />
   <input class="button" type="submit" name="aaction" value="<?php echo _("Enqueue"); ?>" />
</td>
<td><a href=<?php echo conf('web_path'); ?>/randomplay.php><?php echo _("Advanced"); ?></a></td>
  </tr>
  </table>
  </td></tr>
  </table>
  </form>

