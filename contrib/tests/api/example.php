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


// Require the API lib
require_once 'AmpacheApi.lib.php';

$username = ''; 
$password = ''; 

$ampache = new AmpacheApi(array('username'=>$username,'password'=>$password,'server'=>'localhost')); 
$ampache->parse_response($ampache->send_command('artists',array('filter'=>'e'))); 
print_r($ampache->get_response()); 
?>
