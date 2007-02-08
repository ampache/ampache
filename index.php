<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

/*!
	@header Index of Ampache
	@discussion Do most of the dirty work of displaying the mp3 catalog

*/
require_once('lib/init.php');
show_template('header');

$action = scrub_in($_REQUEST['action']);

/**
 * Check for the refresh mojo, if it's there then require the
 * refresh_javascript include. Must be greater then 5, I'm not
 * going to let them break their servers
 */
if (conf('refresh_limit') > 5) { 
	$ajax_url = conf('ajax_url') . '?action=reloadnp' . conf('ajax_info');
	/* Can't have the &amp; stuff in the Javascript */
	$ajax_url = str_replace("&amp;","&",$ajax_url);
	require_once(conf('prefix') . '/templates/javascript_refresh.inc.php');
}

require_once(conf('prefix') . '/templates/show_index.inc.php');

show_footer(); 

?>
