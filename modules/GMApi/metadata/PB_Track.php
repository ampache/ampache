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

class PB_Track extends PB_Message_Abstract {
	
	public function __construct() {
		$this->setField("id", PhpBuf_Type::STRING, PhpBuf_Rule::OPTIONAL, 2);
		$this->setField("creation", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 3);
		$this->setField("lastPlayed", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 4);
		$this->setField("title", PhpBuf_Type::STRING, PhpBuf_Rule::OPTIONAL, 6);
		$this->setField("artist", PhpBuf_Type::STRING, PhpBuf_Rule::OPTIONAL, 7);
		$this->setField("composer", PhpBuf_Type::STRING, PhpBuf_Rule::OPTIONAL, 8);
		$this->setField("album", PhpBuf_Type::STRING, PhpBuf_Rule::OPTIONAL, 9);
		$this->setField("albumArtist", PhpBuf_Type::STRING, PhpBuf_Rule::OPTIONAL, 10);
		$this->setField("year", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 11);
		$this->setField("comment", PhpBuf_Type::STRING, PhpBuf_Rule::OPTIONAL, 12);
		$this->setField("track", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 13);
		$this->setField("genre", PhpBuf_Type::STRING, PhpBuf_Rule::OPTIONAL, 14);
		$this->setField("duration", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 15);
		$this->setField("beatsPerMinute", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 16);
		$this->setField("playCount", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 20);
		$this->setField("totalTracks", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 26);
		$this->setField("disc", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 27);
		$this->setField("totalDiscs", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 28);
		$this->setField("rating", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 31);
		$this->setField("fileSize", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 32);
		$this->setField("u13", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 37);
		$this->setField("u14", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 38);
		$this->setField("bitrate", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 44);
		$this->setField("u15", PhpBuf_Type::STRING, PhpBuf_Rule::OPTIONAL, 53);
		$this->setField("u16", PhpBuf_Type::INT, PhpBuf_Rule::OPTIONAL, 61);

	}
	
	public static function name(){
		return __CLASS__;
	}

}
?>