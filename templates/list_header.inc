<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 * List Header
 * The default pager widget for moving through a list of many items.
 * This relies heavily on the View object to get pieces about how
 * to layout this page.
 */

/* We must have Some Items, if none passed use sesison information */
if (!$total_items) { $total_items = $_SESSION['view_total_items']; }


/* Calculate the Next/Prev Pages */

// Next
$next_offset = $GLOBALS['view']->offset + $GLOBALS['view']->offset_limit;
if ($next_offset > $total_items) { $next_offset = $GLOBALS['view']->offset; }

// Prev
$prev_offset = $GLOBALS['view']->offset - $GLOBALS['view']->offset_limit; 
if ($prev_offset < 0) { $prev_offset = '0'; } 

/* Calculate how many pages total exist */
if (!$GLOBALS['view']->offset_limit) { $GLOBALS['view']->offset_limit = '50'; } 
$pages  = ceil($total_items/$GLOBALS['view']->offset_limit);

/* Calculate current page and how many we have on each side */
$page_data = array('up'=>array(),'down'=>array());

// Can't Divide by 0
if ($GLOBALS['view']->offset > 0) { 
	$current_page = floor($GLOBALS['view']->offset/$GLOBALS['view']->offset_limit);
}
else { 
	$current_page = 0;
}

/* Create 10 pages in either direction */
// Down First
$page = $current_page;
$i = 0;
/* While we have pages left */
while ($page > 0) { 
	if ($i == '15') { $page_data['down'][1] = '...'; $page_data['down'][0] = '0'; break; } 
	$i++;
	$page = $page - 1;
	$page_data['down'][$page] = $page * $GLOBALS['view']->offset_limit;
} // while page > 0

// Up Next
$page = $current_page+1; 
$i = 0;
/* While we have pages left */
while ($page <= $pages) { 
	if ($page * $GLOBALS['view']->offset_limit > $total_items) { break; }
	if ($i == '15') { 
		$key = $pages - 1;
		if (!$page_data['up'][$key]) { $page_data['up'][$key] = '...'; }
		$page_data['up'][$pages] = ($pages-1) * $GLOBALS['view']->offset_limit;
		break;
	}
	$i++;
	$page = $page + 1;
	$page_data['up'][$page] = ($page-1) * $GLOBALS['view']->offset_limit;
} // end while

// Sort These Arrays of Hotness
ksort($page_data['up']);
ksort($page_data['down']);

/* Detect the current script, this take a little work because we have to 
 * account for FastCGI 
 */
preg_match("/.*\/(.+\.php)$/", $_SERVER['SCRIPT_NAME'], $matches);
// Must be running Fast CGI or something similar
if (!isset($matches['1'])) { 
	// Try PHP_SELF
	preg_match("/.*\/(.+\.php)$/",$_SERVER['PHP_SELF'],$matches); 
}

$action = "action=" . scrub_in($_REQUEST['action']);
$script = conf('web_path') . "/" . $admin_menu . $matches[1];

// are there enough items to even need this view?
if (($pages > 1) && ($_SESSION['view_script'])) {
?>
<table class="list-header" cellpadding="1" cellspacing="0" width="100%">
<tr>
	<td valign="top">
	<a class="list-header" href="<?php echo $script; ?>?<?php echo $action; ?>&amp;offset=<?php echo $prev_offset; ?>&amp;keep_view=true">[<?php echo _('Prev'); ?>]</a>&nbsp;
	</td>
	<td align="center">
	<?php 
		/* Echo Everything below us */
		foreach ($page_data['down'] as $page => $offset) { 
			if ($offset === '...') { echo '...&nbsp;'; } 
			else { 
			// Hack Alert
			$page++;
	?>
	<a class="list-header" href="<?php echo $script; ?>?<?php echo $action; ?>&amp;sort_type=<?php echo $GLOBALS['view']->sort_type;  ?>&amp;offset=<?php echo $offset; ?>&amp;keep_view=true"><?php echo $page; ?></a>&nbsp;
	<?php	
			}
		} // end foreach down

		/* Echo Out current Page */
		$current_page = $current_page +1;
	?>
	<span class="list-header"><strong><?php echo $current_page; ?></strong></span>&nbsp;
	<?php
		
		/* Echo Out Everything Above Us */
		foreach ($page_data['up'] as $page=>$offset) { 
			if ($offset === '...') { echo '...&nbsp;'; } 
			else { 
	?>
	<a class="list-header" href="<?php echo $script; ?>?<?php echo $action; ?>&amp;sort_type=<?php echo $GLOBALS['view']->sort_type;  ?>&amp;offset=<?php echo $offset; ?>&amp;keep_view=true"><?php echo $page; ?></a>&nbsp;
	<?php
			} // end else
		} // end foreach up
		/*
		$counter = 1;
		$offset_pages = 0;
		while ($counter <= $pages) {
		if ($GLOBALS['view']->offset == $offset_pages) { ?>
		<?php } else { ?>
		<?php 	
		} // end if ($GLOBALS['view']->offset == $offset_pages) and else
			$offset_pages += $GLOBALS['view']->offset_limit;
			$counter++;
		} // end while ($counter <= $pages) 
		*/
	?>
	</td>
	<td valign="top">
		<a class="list-header" href="<?php echo $script; ?>?<?php echo $action; ?>&amp;offset=<?php echo $next_offset; ?>&amp;keep_view=true">[<?php echo _('Next'); ?>]</a>&nbsp;
	</td>
</tr>
</table>
<?php
} // if (($pages > 1) && ($_SESSION['view_script']))
?>
