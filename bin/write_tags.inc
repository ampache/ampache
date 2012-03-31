<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Write Tags
 *
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
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

/*
 * Use with caution, this hasn't been heavily tested!!!
 * write_tags.php.inc - This file was written in order to give the ability
 * to write tags changed through the interface back out to the file. This can
 * be especially important when trying to keep a clean file structure.
 */

define('NO_SESSION','1');
$path = dirname(__FILE__);
$prefix = realpath($path . '/../');
require_once $prefix . '/lib/init.php';

// This is all broken currently so just jump ship
echo T_("Writting of Tags to files is not currently supported by Getid3() use at your own risk.");
echo "\n";
flush();

require_once Config::get('prefix') . '/modules/getid3/write.vorbis.php';
require_once Config::get('prefix') . '/modules/getid3/write.apetag.php';
require_once Config::get('prefix') . '/modules/getid3/write.flac.php';

$flag = new Flag();
$flagged_ids = Flag::get_approved();

if (!count($flagged_ids)) {
	echo T_("No Flagged Songs Found, exiting...");
	echo "\n";
	exit;
}

// Loop through every song that has an approved flag
foreach($flagged_ids as $flagged) {

	$flag = new Flag($flagged);
	$song = new Song($flag->object_id);

	// Decide on what type of tag format to use, base on song type(a better way maybe?)
	switch($song->type) {
		case 'mp3':
		case 'mp2':
		case 'mp1':
			$tagWriter = new getid3_write_apetag($song->file);
			Flag::fill_tags( &$tagWriter, &$song, 'comment' );
			break;
		case 'ogg':
			$tagWriter = new getid3_write_vorbis($song->file);
			Flag::fill_tags( &$tagWriter, &$song, 'comment' );
			break;
		case 'flac':
			$tagWriter = new getid3_write_flac($song->file);
			Flag::fill_tags( &$tagWriter, &$song, 'comment' );
			break;
		default:
			echo T_('No Tag');
	} // end switch 
	
	if ( isset($tagWriter) ) {

		// Write out the tag
		try {
			$tagWriter->write();
			// HINT: %1$s: title, %2$s: artist_name
			printf (T_('Updated song %1$s by %2$s'), $song->title, $song->get_artist_name());
			echo "\n";
			flush();
			$flag->delete();
		}
		catch ( Exception $e ) {
			print_r( $e->message() );
		}
	}

	// Unset objects between iterations. getID3 seems to need this
	unset($tagWriter,$song,$flag);

}

?>
