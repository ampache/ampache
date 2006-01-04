<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
 * Catalog Display
 * This template displays all of the catalogs... Currently it's a little cluttered
 * It would be great if someone cleaned this up
 */
?>
<br />
<?php  if (!function_exists('iconv')) { ?>
        <div class="fatalerror"><?php echo  _("Error: ICONV not found, ID3V2 Tags will not import correctly. See <a href=\"http://php.oregonstate.edu/iconv\">Iconv</a> for information on getting ICONV"); ?></div>
<?php  } ?>
<table cellpadding="5" border="0" cellspacing="0">
<tr>
	<td valign="top"><?php show_local_catalog_info(); ?></td>
        <td valign="top">
        	<form name="catalog" method="post" action="<?php echo  conf('web_path'); ?>/admin/catalog.php" enctype="multipart/form-data">
                <table class="border" cellspacing="1" cellpadding="3">
                <tr class="table-header" align="center">
                	<td colspan="4"><?php echo  _("Update Catalogs"); ?></td>
                </tr>
                <?php
		$catalogs = $catalog->get_catalogs();

			if ( $catalogs ) {
                        	foreach ($catalogs as $catalog) {
		                        print("<tr class=\"even\"><td>".
                                        "<input type=\"checkbox\" name=\"catalogs[]\" value=\"$catalog->id\"></input></td>".
                                        "<td>".
                                        "<a href=\"". conf('web_path') ."/admin/catalog.php?action=show_customize_catalog&amp;catalog_id=$catalog->id\">".
                                        "$catalog->path".
                                        "</a>".
                                        "</td>".
                                        "<td>".
                                        date("H:i - m/d/y",$catalog->last_update).
                                        "</td>".
                                        "<td>".
                                        "<a href=\"" . conf('web_path') . "/admin/catalog.php?action=show_delete_catalog&amp;catalog_id=$catalog->id\">" . _("Delete") . "</a>".
                                        "</td></tr>\n");
				} // end foreach
		?>				
                <tr>
			<td class="even" colspan="4">
				<input class="button" type="submit" name="action" value="<?php echo  _("Add to Catalog(s)"); ; ?>" />&nbsp;&nbsp;
                                <input class="button" type="submit" name="action" value="<?php echo  _("Add to all Catalogs"); ; ?>" /><br />
				<?php echo  _("Fast Add"); ; ?>:<input type="checkbox" name="update_type" value="fast_add" />
			</td>
		</tr>
		<tr>
			<td class="even" colspan="4">
				<input class="button" type="submit" name="action" value="<?php echo  _("Update Catalog(s)"); ; ?>" />&nbsp;&nbsp;&nbsp;
				<input class="button" type="submit" name="action" value="<?php echo  _("Update All Catalogs"); ; ?>" /><br />
				<?php echo  _("Fast Update"); ; ?>:<input type="checkbox" name="update_type" value="fast_update" /><br />
			</td>
		</tr>
		<tr>
			<td class="even" colspan="4">
				<input class="button" type="submit" name="action" value="<?php echo  _("Clean Catalog(s)"); ; ?>" />&nbsp;&nbsp;&nbsp;
				<input class="button" type="submit" name="action" value="<?php echo  _("Clean All Catalogs"); ; ?>" /><br />
			</td>
		</tr>
		<?php 
                                                } // end if catalogs
                                                else {
                                                        print("<tr class=\"even\"><td colspan=\"4\" >".
                                                                _("You don't have any catalogs.") .
                                                                "</td></tr>");
                                                }
                                        ?>
                        </table>
                        </form>
                </td>
                <td valign="top">
                        <?php 
                                $tools = array(_("Add a catalog") => conf('web_path') . "/admin/catalog.php?action=show_add_catalog",
                                                _("Access Lists") => conf('web_path') . "/admin/access.php",
						_("Show Duplicate Songs") => conf('web_path') . "/admin/duplicates.php",
						_("Show Disabled Songs") => conf('web_path') . "/admin/catalog.php?action=show_disabled",
                                                _("Clear Catalog Stats") => conf('web_path') . "/admin/catalog.php?action=clear_stats",
						_("Clear Now Playing")   => conf('web_path') . "/admin/catalog.php?action=clear_now_playing",
                                                _("Dump Album Art")   => conf('web_path') . "/admin/catalog.php?action=dump_album_art", 
						_("Gather Album Art") => conf('web_path') . "/admin/catalog.php?action=gather_album_art",
						_("View flagged songs") => conf('web_path') . "/admin/flags.php");
                                show_tool_box(_("Catalog Tools"), $tools);
                        ?>
                </td>
        </tr>
</table>
