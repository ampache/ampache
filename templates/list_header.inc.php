<?php
/*

 Copyright (c) Ampache.org
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

// Pull these variables out to allow shorthand (easier for lazy programmers)
$limit	= Config::get('offset_limit') ? Config::get('offset_limit') : '25'; 
$start	= Browse::get_start(); 
$total	= Browse::$total_objects; 
$uid	  = Config::get('list_header_uid'); 
$sides  = 5;

// ++ the uid
Config::set('list_header_uid',$uid+1,1); 

// Next
$next_offset = $start + $limit;
if ($next_offset > $total) { $next_offset = $start; }

// Prev
$prev_offset = $start - $limit; 
if ($prev_offset < 0) { $prev_offset = '0'; } 

/* Calculate how many pages total exist */
$pages  = ceil($total/$limit);

/* Calculate current page and how many we have on each side */
$page_data = array('up'=>array(),'down'=>array());

// Can't Divide by 0
if ($start> 0) { 
	$current_page = floor($start/$limit);
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
	if ($i == $sides) { $page_data['down'][1] = '...'; $page_data['down'][0] = '0'; break; } 
	$i++;
	$page = $page - 1;
	$page_data['down'][$page] = $page * $limit;
} // while page > 0

// Up Next
$page = $current_page+1; 
$i = 0;
/* While we have pages left */
while ($page <= $pages) { 
	if ($page * $limit > $total) { break; }
	if ($i == $sides) { 
		$key = $pages - 1;
		if (!$page_data['up'][$key]) { $page_data['up'][$key] = '...'; }
		$page_data['up'][$pages] = ($pages-1) * $limit;
		break;
	}
	$i++;
	$page = $page + 1;
	$page_data['up'][$page] = ($page-1) * $limit;
} // end while

// Sort These Arrays of Hotness
ksort($page_data['up']);
ksort($page_data['down']);
$browse_type = Browse::get_type(); 

// are there enough items to even need this view?
if ($pages > 1) {
?>
<div class="list-header">

  <?php echo Ajax::text('?page=browse&action=page&type=' . $browse_type . '&start=' . $prev_offset,_('Prev'),'browse_' . $uid . 'prev','','prev'); ?>
	<?php echo Ajax::text('?page=browse&action=page&type=' . $browse_type . '&start=' . $next_offset,_('Next'),'browse_' . $uid . 'next','','next'); ?>
	<?php 
		/* Echo Everything below us */
		foreach ($page_data['down'] as $page => $offset) { 
			if ($offset === '...') { echo '...&nbsp;'; } 
			else { 
			// Hack Alert
			$page++;
				echo Ajax::text('?page=browse&action=page&type=' . $browse_type .'&start=' . $offset,$page,'browse_' . $uid . 'page_' . $page,'','page-nb'); 
			}
		} // end foreach down

		/* Echo Out current Page */
		$current_page = $current_page +1;
	?>
	<span class="page-nb selected"><?php echo $current_page; ?></span>
	<?php

		/* Echo Out Everything Above Us */
		foreach ($page_data['up'] as $page=>$offset) { 
			if ($offset === '...') { echo '...&nbsp;'; } 
			else { 
				echo Ajax::text('?page=browse&action=page&type=' . $browse_type . '&start=' . $offset,$page,'browse_' . $uid . 'page_' . $page,'','page-nb'); 
			} // end else
		} // end foreach up
	?>
</div>
<?php
} // if stuff
?>
