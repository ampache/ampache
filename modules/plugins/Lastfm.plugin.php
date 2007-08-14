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

class AmpacheLastfm { 

	public $name		='Last.FM'; 
	public $description	='Records your played songs to your Last.FM Account'; 
	public $url		='';
	public $version		='000003';
	public $min_ampache	='340007';
	public $max_ampache	='340008';

	/**
	 * Constructor
	 * This function does nothing...
	 */
	public function __construct() { 

		return true; 

	} // PluginLastfm

	/**
	 * install
	 * This is a required plugin function it inserts the required preferences
	 * into Ampache
	 */
	public function install() { 

		Preference::insert('lastfm_user','Last.FM Username',' ','25','string','options'); 
		Preference::insert('lastfm_pass','Last.FM Password',' ','25','string','options'); 
		Preference::insert('lastfm_port','Last.FM Submit Port',' ','25','string','internal'); 
		Preference::insert('lastfm_host','Last.FM Submit Host',' ','25','string','internal'); 
		Preference::insert('lastfm_url','Last.FM Submit URL',' ','25','string','internal'); 
		Preference::insert('lastfm_challenge','Last.FM Submit Challenge',' ','25','string','internal'); 

	} // install

	/**
	 * uninstall
	 * This is a required plugin function it removes the required preferences from
	 * the database returning it to its origional form
	 */
	function uninstall() { 

		/* We need to remove the preivously added preferences */
		$sql = "DELETE FROM `preference` WHERE `name`='lastfm_pass' OR `name`='lastfm_user' " . 
			"OR `name`='lastfm_url' OR `name`='lastfm_host' OR `name`='lastfm_port' OR `name`='lastfm_challenge'"; 
		$db_results = Dba::query($sql);

	} // uninstall

} // end AmpacheLastfm
?>
