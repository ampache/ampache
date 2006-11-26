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

class Plugin {

	/* Base Variables */
	var $name;

	/* constructed objects */
	var $_plugin; 

	/**
	 * Constructor
	 * This constructor loads the Plugin config file which defines how to 
	 * install/uninstall the plugin from Ampache's database
	 */
	function Plugin($name) { 

		/* Load the plugin */
		if (!$this->_get_info($name)) { 
			return false; 
		}

		return true; 

	} // Plugin


	/**
	 * _get_info
	 * This actually loads the config file for the plugin the name of the
	 * class contained within the config file must be Plugin[NAME OF FILE]
	 */
	function _get_info($name) { 

		/* Require the file we want */
		require_once(conf('prefix') . '/modules/plugins/' . $name . '.plugin.php');

		$plugin_name = "Ampache$name";

		$this->_plugin = new $plugin_name(); 

		if (!$this->is_valid()) { 
			return false; 
		}

		return true; 		

	} // _get_info

	/**
	 * is_valid
	 * This checks to make sure the plugin has the required functions and
	 * settings, Ampache requires Name/Description/Version (Int) and a
	 * install & uninstall method and Ampache must be within the min/max
	 * version specifications
	 */
	function is_valid() { 

		/* Check the plugin to make sure it's got the needed vars */ 
		if (!strlen($this->_plugin->name)) { 
			return false; 
		}
		if (!strlen($this->_plugin->description)) { 
			return false; 
		}
		if (!strlen($this->_plugin->version)) { 
			return false; 
		} 

		/* Make sure we've got the required methods */
		if (!method_exists($this->_plugin,'install')) { 
			return false; 
		}

		if (!method_exists($this->_plugin,'uninstall')) { 
			return false; 
		} 

		/* Make sure it's within the version confines */
		$db_version = $this->get_ampache_db_version(); 

		if ($db_version < $this->_plugin->min_ampache) { 
			return false; 
		} 

		if ($db_version > $this->_plugin->max_ampache) { 
			return false; 
		} 

		/* We've passed all of the tests its good */
		return true; 

	} // is_valid

	/**
	 * is_installed
	 * This checks to see if the current plugin is currently installed in the
	 * database, it doesn't check the files for integrity
	 */
	function is_installed() { 

		/* All we do is check the version */ 
		return $this->get_plugin_version(); 

	} // is_installed

	/**
	 * install
	 * This runs the install function of the plugin (must be called install) 
	 * at the end it inserts a row into the update_info table to indicate
	 * That it's installed
	 */
	function install() { 

		$this->_plugin->install(); 

		$this->set_plugin_version($this->_plugin->version); 

	} // install

	/** 
	 * uninstall
	 * This runs the uninstall function of the plugin (must be called uninstall) 
	 * at the end it removes the row from the update_info table to indicate
	 * that it isn't installed
	 */
	function uninstall() { 

		$this->_plugin->uninstall(); 

		$this->remove_plugin_version(); 

	} // uninstall

	/**
	 * get_plugin_version
	 * This returns the version of the currently installed plugin
	 */
	function get_plugin_version() { 

		$name = sql_escape('Plugin_' . $this->_plugin->name); 

		$sql = "SELECT * FROM update_info WHERE `key`='$name'"; 
		$db_results = mysql_query($sql,dbh()); 

		$results = mysql_fetch_assoc($db_results); 
		
		return $results['value'];

	} // get_plugin_version

	/**
	 * get_ampache_db_version
	 * This function returns the Ampache database version
	 */
	function get_ampache_db_version() { 

		$sql = "SELECT * FROM update_info WHERE `key`='db_version'"; 
		$db_results = mysql_query($sql,dbh()); 

		$results = mysql_fetch_assoc($db_results); 

		return $results['value'];

	} // get_ampache_db_version

	/**
	 * set_plugin_version
	 * This sets the plugin version in the update_info table
	 */
	function set_plugin_version($version) { 

		$name 		= sql_escape('Plugin_' . $this->_plugin->name);
		$version	= sql_escape($version);

		$sql = "INSERT INTO update_info SET `key`='$name', value='$version'";
		$db_results = mysql_query($sql,dbh()); 

		return true; 

	} // set_plugin_version

	/**
 	 * remove_plugin_version
	 * This removes the version row from the db done on uninstall
	 */
	function remove_plugin_version() { 
	
		$name	= sql_escape('Plugin_' . $this->_plugin->name);
	
		$sql = "DELETE FROM update_info WHERE `key`='$name'"; 
		$db_results = mysql_query($sql,dbh()); 

		return true; 

	} // remove_plugin_version

} //end plugin class
?>
