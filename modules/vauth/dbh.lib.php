<?php
/*

 Copyright (c) 2006 Karl Vollmer
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
 * Database Handler File 
 * This file contains functions for handling the database connection
 * Yup!
 */


/**
 * vauth_dbh
 * Init's the dbh yea 
 */
function vauth_dbh($handle='vauth_dbh') { 

	$dbh = vauth_conf($handle);

        /* If we don't have a db connection yet */
        if (!is_resource($dbh)) { 
        
                $hostname       = vauth_conf('mysql_hostname');
                $username       = vauth_conf('mysql_username');
                $password       = vauth_conf('mysql_password');
		$database	= vauth_conf('mysql_db');

                $dbh            = mysql_pconnect($hostname, $username, $password);
                $select_db      = mysql_select_db($database, $dbh);

		/* If either one of these fails */
                if (!is_resource($dbh) || !$select_db) { 
			vauth_error('Database Connection Failed' . mysql_error());
			return false;
                }

                vauth_conf(array($handle => $dbh),1);
                
        } // if no db connection 
 
        return $dbh;

} // vauth_dbh


?>
