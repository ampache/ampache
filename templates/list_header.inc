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

/*!
	@header
 The default pager widget for moving through a list of many items.

 This relies heavily on the View object to get pieces about how
   to layout this page.

*/

if (!$total_items) { $total_items = $_SESSION['view_total_items']; }
// do some math here
if ($GLOBALS['view']->offset >= $GLOBALS['view']->offset_limit) {
	$offset2 = $GLOBALS['view']->offset - 25;
}
else {
	$offset2 = $GLOBALS['view']->offset;
	if (!$GLOBALS['view']->offset) { $offset2 = "0"; }
}
// Get the prev page offset
$offset1 = $GLOBALS['view']->offset - $GLOBALS['view']->offset_limit;
if ($offset1 < 1) { $offset1 = 0; }

// since we have an array of objects, let's build a purdy thingie
$pages  = ceil($total_items/$GLOBALS['view']->offset_limit);
$offset4 = ($pages - 1);
$offset4 = ($offset4 * $GLOBALS['view']->offset_limit);

//We do this one last to make sure we don't go beyond offset4
$offset3 = $GLOBALS['view']->offset + $GLOBALS['view']->offset_limit;
if ($offset3 >= $offset4) { $offset3 = $offset4; } 


//setup the next action
preg_match("/.*\/(.+\.php)$/", $_SERVER['SCRIPT_NAME'], $matches);
$action = "action=" . scrub_in($_REQUEST['action']);
$script = conf('web_path') . "/" . $admin_menu . $matches[1];

// are there enough items to even need this view?
if (($pages > 1) && ($_SESSION['view_script'])) {
?>
	<table border="0" cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td align="center" valign="top">
			<a href="<?php echo $script; ?>?<?php echo $action; ?>&amp;offset=<?php echo $offset1; ?>&amp;keep_view=true">[&nbsp;<?php echo  _("Prev"); ?>&nbsp;]</a>&nbsp;
		</td>
		<td align="center">
		<?php 
			$page_limit = '10';
			$counter = 1;
			$offset_pages = 0;
			while ($counter <= $pages) {
				if ($GLOBALS['view']->offset == $offset_pages) {	
		?>			<a href="<?php echo $script; ?>?<?php echo  $action ; ?>&amp;sort_type=<?php echo  $GLOBALS['view']->sort_type ; ?>&amp;offset=<?php echo  $offset_pages ; ?>&amp;keep_view=true"><b><?php echo $counter; ?></b></a>&nbsp;
				<?php 
				} else { ?>
					<a href="<?php echo $script; ?>?<?php echo $action; ?>&amp;sort_type=<?php echo $GLOBALS['view']->sort_type;  ?>&amp;offset=<?php echo $offset_pages; ?>&amp;keep_view=true"><?php echo $counter; ?></a>&nbsp;
				<?php 
				} // end if ($GLOBALS['view']->offset == $offset_pages) and else
				$offset_pages += $GLOBALS['view']->offset_limit;
				if ($counter == $page_limit AND $pages > ($page_limit*2)) { break; }
				$counter++;
			} // end while ($counter <= $pages) 

			if ($counter < $pages) { 
				echo "[.....]"; 
				$counter = $pages - $page_limit;
	                        while ($counter <= $pages) {
	                                if ($GLOBALS['view']->offset == $offset_pages) { ?>
			                      <a href="<?php echo $script; ?>?<?php echo  $action ; ?>&amp;sort_type=<?php echo  $GLOBALS['view']->sort_type ; ?>&amp;offset=<?php echo  $offset_pages ; ?>&amp;keep_view=true"><b><?php echo $counter; ?></b></a>&nbsp;
	                                <?php } else { ?>
	                                        <a href="<?php echo $script; ?>?<?php echo $action; ?>&amp;sort_type=<?php echo $GLOBALS['view']->sort_type;  ?>&amp;offset=<?php echo $offset_pages; ?>&amp;keep_view=true"><?php echo $counter; ?></a>&nbsp;
	                                <?php
	                                } // end if ($GLOBALS['view']->offset == $offset_pages) and else
                        	        $offset_pages += $GLOBALS['view']->offset_limit;
                	                $counter++;
	                        } // end while ($counter <= $pages)
			} // end while if we had to do a second half
		?>
		</td>
		<td align="center" valign="top">
			<a href="<?php echo $script; ?>?<?php echo $action; ?>&amp;offset=<?php echo $offset3; ?>&amp;keep_view=true">[&nbsp;<?php echo _("Next"); ?>&nbsp;]</a>&nbsp;
		</td>
	</tr>
	</table>
<?php
} // if (($pages > 1) && ($_SESSION['view_script']))
?>
