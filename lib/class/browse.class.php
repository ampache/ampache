<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*
 * Browse Class
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
 * @category	Browse
 * @package	Ampache
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
 */

/**
 * Browse Class
 *
 * This handles all of the sql/filtering
 * on the data before it's thrown out to the templates
 * it also handles pulling back the object_ids and then
 * calling the correct template for the object we are displaying
 *
 * @category	Browse
 * @package	Ampache
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	Release:
 * @link	http://www.ampache.org/
 * @since	Class available since Release 1.0
 */
class Browse extends Query {

	/**
	 * set_simple_browse
	 * This sets the current browse object to a 'simple' browse method
	 * which means use the base query provided and expand from there
	 */
	public function set_simple_browse($value) {

		$this->set_is_simple($value);

	} // set_simple_browse

	/**
	 * add_supplemental_object
	 * Legacy function, need to find a better way to do that
	 */
	public function add_supplemental_object($class, $uid) {

		$_SESSION['browse']['supplemental'][$this->id][$class] = intval($uid);

		return true;

	} // add_supplemental_object

	/**
	 * get_supplemental_objects
	 * This returns an array of 'class','id' for additional objects that
	 * need to be created before we start this whole browsing thing.
	 */
	public function get_supplemental_objects() {

		$objects = $_SESSION['browse']['supplemental'][$this->id];

		if (!is_array($objects)) { $objects = array(); }

		return $objects;

	} // get_supplemental_objects

	/**
	 * show_objects
	 * This takes an array of objects
	 * and requires the correct template based on the
	 * type that we are currently browsing
	 */
	public function show_objects($object_ids = null) {

		if ($this->is_simple() || ! is_array($object_ids)) {
			$object_ids = $this->get_saved();
		}
		else {
			$this->save_objects($object_ids);
		}

		// Limit is based on the user's preferences if this is not a 
		// simple browse because we've got too much here
		if ((count($object_ids) > $this->get_start()) && 
			! $this->is_simple() &&
			! $this->is_static_content()) {
			$object_ids = array_slice(
				$object_ids,
				$this->get_start(),
				$this->get_offset(), 
				true
			);
		}

		// Load any additional object we need for this
		$extra_objects = $this->get_supplemental_objects();
		$browse = $this;

		foreach ($extra_objects as $class_name => $id) {
			${$class_name} = new $class_name($id);
		}

		$match = '';
		// Format any matches we have so we can show them to the masses
		if ($filter_value = $this->get_filter('alpha_match')) {
			$match = ' (' . $filter_value . ')';
		}
		elseif ($filter_value = $this->get_filter('starts_with')) {
			$match = ' (' . $filter_value . ')';
		} elseif ($filter_value = $this->get_filter('catalog')) {
			$match = '(' . $filter_value . ')';
		}

		$type = $this->get_type();

		// Set the correct classes based on type
		$class = "box browse_" . $type;

		Ajax::start_container('browse_content');
		// Switch on the type of browsing we're doing
		switch ($type) {
			case 'song':
				show_box_top(_('Songs') . $match, $class);
				Song::build_cache($object_ids);
				require_once Config::get('prefix') . '/templates/show_songs.inc.php';
				show_box_bottom();
			break;
			case 'album':
				show_box_top(_('Albums') . $match, $class);
				Album::build_cache($object_ids,'extra');
				require_once Config::get('prefix') . '/templates/show_albums.inc.php';
				show_box_bottom();
			break;
			case 'user':
				show_box_top(_('Manage Users') . $match, $class);
				require_once Config::get('prefix') . '/templates/show_users.inc.php';
				show_box_bottom();
			break;
			case 'artist':
				show_box_top(_('Artists') . $match, $class);
				Artist::build_cache($object_ids,'extra');
				require_once Config::get('prefix') . '/templates/show_artists.inc.php';
				show_box_bottom();
			break;
			case 'live_stream':
				require_once Config::get('prefix') . '/templates/show_live_stream.inc.php';
				show_box_top(_('Radio Stations') . $match, $class);
				require_once Config::get('prefix') . '/templates/show_live_streams.inc.php';
				show_box_bottom();
			break;
			case 'playlist':
				Playlist::build_cache($object_ids);
				show_box_top(_('Playlists') . $match, $class);
				require_once Config::get('prefix') . '/templates/show_playlists.inc.php';
				show_box_bottom();
			break;
			case 'playlist_song':
				show_box_top(_('Playlist Songs') . $match,$class);
				require_once Config::get('prefix') . '/templates/show_playlist_songs.inc.php';
				show_box_bottom();
			break;
			case 'playlist_localplay':
				show_box_top(_('Current Playlist'));
				require_once Config::get('prefix') . '/templates/show_localplay_playlist.inc.php';
				show_box_bottom();
			break;
			case 'catalog':
				show_box_top(_('Catalogs'), $class);
				require_once Config::get('prefix') . '/templates/show_catalogs.inc.php';
				show_box_bottom();
			break;
			case 'shoutbox':
				show_box_top(_('Shoutbox Records'),$class);
				require_once Config::get('prefix') . '/templates/show_manage_shoutbox.inc.php';
				show_box_bottom();
			break;
			case 'flagged':
				show_box_top(_('Flagged Records'),$class);
				require_once Config::get('prefix') . '/templates/show_flagged.inc.php';
				show_box_bottom();
			break;
			case 'tag':
				Tag::build_cache($tags);
				show_box_top(_('Tag Cloud'),$class);
				require_once Config::get('prefix') . '/templates/show_tagcloud.inc.php';
				show_box_bottom();
			break;
			case 'video':
				Video::build_cache($object_ids);
				show_box_top(_('Videos'),$class);
				require_once Config::get('prefix') . '/templates/show_videos.inc.php';
				show_box_bottom();
			break;
			case 'democratic':
				show_box_top(_('Democratic Playlist'),$class);
				require_once Config::get('prefix') . '/templates/show_democratic_playlist.inc.php';
				show_box_bottom();
			default:
				// Rien a faire
			break;
		} // end switch on type
		echo '<script type="text/javascript">ajaxPut("' . Config::get('ajax_url') . '?page=browse&action=get_filters&browse_id=' . $this->id . '","");</script>';

		Ajax::end_container();

	} // show_object

	/**
 	 * set_filter_from_request
	 * //FIXME
	 */
	public function set_filter_from_request($request) {
		foreach($request as $key => $value) {
			//reinterpret v as a list of int
			$list = explode(',', $value);
			$ok = true;
			foreach($list as $item) {
				if (!is_numeric($item)) {
					$ok = false;
					break;
				}
			}
			if ($ok) {
				if (sizeof($list) == 1) {
					$this->set_filter($key, $list[0]);
				}
			}
			else {
				$this->set_filter($key, $list);
			}
		}
	} // set_filter_from_request

} // browse
