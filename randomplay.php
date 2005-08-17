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

/*!
	@header Index of Ampache
	@discussion Do most of the dirty work of displaying the mp3 catalog

*/
require_once("modules/init.php");
show_template('header');
init_mpd();
show_menu_items('Home');
show_clear();
$action = scrub_in($_REQUEST['action']);

if (conf('refresh_interval')) { 
	show_template('javascript_refresh');
	}
?>

<!-- Big Daddy Table -->
<table style="padding-left:5px;padding-right:5px;padding-top:5px;padding-bottom:5px;" >
<tr><td colspan="2">&nbsp;</td></tr>
<tr>
	<td valign="top">

<?php
/*
 * show_random_play()
 * 

function show_random_play() {
 */
        $web_path = conf('web_path');

        print '
        <form name="random" method="post" enctype="multipart/form-data" action="' . $web_path . '/song.php">
        <input type="hidden" name="action" value="m3u" />
        <table class="border" border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr class="table-header">
                <td colspan="4">' . _("Play Random Selection from Multiple Genres") . '</td>

        </tr>
        <tr class="even">
        <td>
        <table border="0">
                <tr class="even">
                <td>' . _("Item count") .'</td>
                <td>
                        <select name="random">
                        <option value="-1">' . _("All") . '</option>
                        <option value="1">1</option>
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                        <option value="1000">1000</option>
                        </select></td>
                <td rowspan="3" valign="top"> ' . _("From genre") . '</td>
                <td rowspan="4">
';
        show_genre_pulldown( -1, 0, "'33' multiple='multiple'" );

        print '
                </td></tr>
        <tr class="even">
                <td>
                        ' . _("Favor Unplayed") . ' <br />
                        ' . _("Favor Full Albums") . ' <br />
                        ' . _("Favor Full Artist") . ' <br />
                </td>
                <td>
                        <input type="checkbox" id="unplayed" name="unplayed" value="1" onclick="flipField(\'album\');flipField(\'artist\')" /><br />
                        <input type="checkbox" id="album" name="full_album" value="1" onclick="flipField(\'unplayed\');flipField(\'artist\')" /><br />
                        <input type="checkbox" id="artist" name="full_artist" value="1" onclick="flipField(\'unplayed\');flipField(\'album\')" /><br />
                </td>
                </tr>
                <tr class="even">
                <td nowrap=\'nowrap\'> ' . _("from catalog") . '</td>
                <td>
';

        show_catalog_pulldown( -1, 0);

        print '
        </td></tr>
        <tr>
                <td colspan="4">
                        <input type="hidden" name="aaction" value="Play!" />
                        <input class="button" type="submit" name="aaction" value="' . _("Play Random Songs") . '" />
                </td>
        </tr>
        </table>
        </td></tr>
        </table>
        </form>
';

/* 
} // show_random_play()
 */
?>

</td></tr>
</table>
<?php show_page_footer ('Home', '', $user->prefs['display_menu']);?>
