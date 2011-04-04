<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * jamendoSearch Class
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2006 Ampache.org All Rights Reserved
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
 * @deprecated	(maybe) deprecated since 2006.
 * @category	jamendoSearch
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

/**
 * jamendoSearch Class
 *
 * This class does XML lookups against the jamendo website
 * and returns information
 *
 * @deprecated	(maybe) deprecated since 2006.
 * @category	jamendoSearch
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */
class jamendoSearch {

	/* Constructed */
	var $_client;

	/**
	 * Constructor
	 * This function inits the searcher
	 */
	function jamendoSearch() {

		/* Load the XMLRPC client */
		$this->_client = new XML_RPC_Client('/xmlrpc/','www.jamendo.com',80);

	} // jamendoSearch

	/**
	 * query
	 * This runs a XMLRPC query and returns decoded data
	 */
	function query($command,$options) {

		$encoded_command = new XML_RPC_Value($command);
		$encoded_options = new XML_RPC_Value($options,'struct');
		$message 	= new XML_RPC_Message('jamendo.get',array($encoded_command,$encoded_options));
		$response 	= $this->_client->send($message,15);
		$value 		= $response->value();

		return XML_RPC_Decode($value);

	} // query

} // jamendoSearch

?>
