<?php
/*
Copyright (C) 2012 raydan

http://code.google.com/p/unofficial-google-music-api-php/

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if(!defined('IN_GMAPI')) { die('...'); }

class PB_MetadataResponse extends PB_Message_Abstract {
	
	public function __construct() {
		$this->setField("u0", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 1);
		$this->setField("response", PhpBuf_Type::MESSAGE, PhpBuf_Rule::OPTIONAL, 2, "PB_TrackResponse");
		$this->setField("status", PhpBuf_Type::MESSAGE, PhpBuf_Rule::OPTIONAL, 6, "PB_Status");
	}
	
	public static function name(){
		return __CLASS__;
	}

}
?>