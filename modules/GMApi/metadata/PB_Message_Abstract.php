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
abstract class PB_Message_Abstract extends PhpBuf_Message_Abstract {
	
	public function getValue($field, $throwException = true) {
		return parent::getValue($field, $throwException);
	}
	
	public function setValue($field, $value, $throwException = true) {
		parent::setValue($field, $value, $throwException);
	}
	
	public function ParseFromString($str) {
		$reader = new PhpBuf_IO_Reader($str);
		$this->read($reader, true);
	}
	
	public function SerializeToString() {
		$writer = new PhpBuf_IO_Writer();
		$this->write($writer);
		return $writer->getData();
	}
}

?>