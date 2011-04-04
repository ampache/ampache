<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * media Interface
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

/**
 * media Interface
 *
 * This defines how the media file classes should
 * work, this lists all required functions and the expected
 * input
 *
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 * @see	Video
 * @see	Radio
 * @see	Random
 * @see	Song
 */
interface media {

	/**
	 * format
	 * 
	 * @return
	 */
	public function format();

	/**
	 * native_stream
	 *
	 * @return	mixed
	 */
	public function native_stream();

	/**
	 * play_url
	 *
	 * @param	int $oid	ID
	 * @return	mixed
	 */
	public static function play_url($oid);

	/**
	 * stream_cmd
	 *
	 * @return	mixed
	 */
	public function stream_cmd();

	/**
	 * has_flag
	 *
	 * @return	mixed
	 */
	public function has_flag();

} // end interface
?>
