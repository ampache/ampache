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

define('NO_SESSION','1');
require 'lib/init.php';

/* Check Perms */
if (!Config::get('use_rss') || Config::get('demo_mode')) {
        access_denied();
	exit;
}

// Add in our base hearder defining the content type
header("Content-Type: application/xml; charset=" . Config::get('site_charset')); 

$rss = new AmpacheRSS($_REQUEST['type']); 
echo $rss->get_xml(); 

?>
