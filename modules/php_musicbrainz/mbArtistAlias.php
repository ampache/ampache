<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
/*
 Copyright 2009, 2010 Timothy John Wood, Paul Arthur MacIain

 This file is part of php_musicbrainz
 
 php_musicbrainz is free software: you can redistribute it and/or modify
 it under the terms of the GNU Lesser General Public License as published by
 the Free Software Foundation, either version 2.1 of the License, or
 (at your option) any later version.
 
 php_musicbrainz is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Lesser General Public License for more details.
 
 You should have received a copy of the GNU Lesser General Public License
 along with php_musicbrainz.  If not, see <http://www.gnu.org/licenses/>.
*/
class mbArtistAlias {
    private $value;
    private $type;
    private $script;

    public function __construct($value='', $type='', $script='') {
        $this->value = $value;
        $this->type = $type;
        $this->script = $script;
    }

    public function getType() { return $this->type; }
    public function setType($type) { $this->type = $type; }
    public function getValue() { return $this->value; }
    public function setValue($value) { $this->value = $value; }
    public function getScript() { return $this->script; }
    public function setScript($script) { $this->script = $script; }
}
?>
