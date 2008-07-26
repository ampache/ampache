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

require_once 'lib/init.php';

show_header(); 

$action = scrub_in($_REQUEST['action']);

/**
 * Check for the refresh mojo, if it's there then require the
 * refresh_javascript include. Must be greater then 5, I'm not
 * going to let them break their servers
 */
if (Config::get('refresh_limit') > 5) { 
	$refresh_limit = Config::get('refresh_limit'); 
	$ajax_url = Config::get('ajax_url') . '?page=index&action=reloadnp';
	require_once Config::get('prefix') . '/templates/javascript_refresh.inc.php';
}

require_once Config::get('prefix') . '/templates/show_index.inc.php';

show_footer(); 

?>
