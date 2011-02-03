<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * AmpacheMusicBrainz Class
 *
 * PHP version 5
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category	AmpacheMusicBrainz
 * @package	Plugins
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
 */

/**
 * AmpacheMusicBrainz Class
 *
 * Description here...
 *
 * @category	AmpacheMusicBrainz
 * @package	Plugins
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	Release:
 * @link	http://www.ampache.org/
 * @since	Class available since Release 1.0
 */
class AmpacheMusicBrainz {

	public $name		='MusicBrainz';
	public $description	='MusicBrainz metadata integration';
	public $version		='000001';
	public $min_ampache	='360003';
	public $max_ampache	='999999';

	/**
	 * Constructor
	 * This function does nothing
	 */
	public function __construct() {
		return true;
	}

	/**
	 * install
	 * This is a required plugin function
	 */
	public function install() {
		return true;
	} // install

	/**
	 * uninstall
	 * This is a required plugin function
	 */
	public function uninstall() {
		return true;
	} // uninstall

	/**
	 * load
	 * This is a required plugin function; here it populates the prefs we 
	 * need for this object.
	 */
	public function load() {
		return true;
	} // load

	/**
	 * get_metadata
	 * Returns song metadata for what we're passed in.
	 */
	public function get_metadata($song_info) {
		if (!$mbid = $song_info['mb_trackid']) {
			return null;
		}

		$mbquery = new MusicBrainzQuery();
		$includes = new mbTrackIncludes();
		$includes = $includes->artist()->releases();
		try {
			$track = $mbquery->getTrackById($mbid, $includes);
		}
		catch (Exception $e) {
			return null;
		}

		$results = array();

		$results['mb_artistid'] = $track->getArtist()->getId();
		$results['artist'] = $track->getArtist()->getName();
		$results['title'] = $track->getTitle();
		if ($track->getNumReleases() == 1) {
			$release = $track->getReleases();
			$release = $release[0];
			$results['album'] = $release->getTitle();
		}

		return $results;
	} // get_metadata

} // end AmpacheMusicBrainz
?>
