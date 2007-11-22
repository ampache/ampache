<?php
/*

 Copyright (c) 2001 - 2007 ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

require_once 'lib/init.php';

// This is a little bit of a special file, it takes the
// content of $_SESSION['iframe']['target'] and does a header
// redirect to that spot!
if (isset($_SESSION['iframe']['target'])) { 
	$target = $_SESSION['iframe']['target']; 
	unset($_SESSION['iframe']['target']); 
	//header("Location: " . $target); 
} 
?>
