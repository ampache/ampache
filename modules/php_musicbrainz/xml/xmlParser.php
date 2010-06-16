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
class XMLNode {
    private $name;
    private $attributes;
    private $childNodes = array();
    private $parentNode;

    public function __construct($name, array $attributes, $parent=null) {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->parentNode = $parent;
    }

    public function getParentNode() {
        return $this->parentNode;
    }

    public function nChildNodes() {
        return sizeof($this->childNodes);
    }

    public function getChildNode($i) {
        return $this->childNodes[$i];
    }

    public function setChildNode(XMLNode $node) {
        $this->childNodes[] = $node;
    }

    public function getName() {
        return $this->name;
    }

    public function getAttribute($name) {
        $name = strtoupper($name);
        return isset($this->attributes[$name]) ? $this->attributes[$name] : "";
    }

    public function setText($text) {
        $this->attributes['TEXT'] = $text;
    }

    public function getText() {
        return $this->attributes['TEXT'];
    }
}

class xmlParser {
    private $_parser;
    private $_root_node = null;
    private $_curr_node = null;

    public function __construct() {
        $this->_parser = xml_parser_create();

        xml_set_object($this->_parser, $this);
        xml_set_default_handler($this->_parser, 'text_handler');
        xml_set_element_handler($this->_parser,
                                 'start_element_handler',
                                 'end_element_handler');
    }

    public function destroy() {
        xml_parser_free($this->_parser);
    }

    public function lastError() {
        return $this->_last_error;
    }

    public function parse($data) {
        if (!xml_parse($this->_parser, $data)) {
            $this->_last_error = xml_get_current_line_number($this->_parser) . ": ". xml_error_string($this->_parser);
            return false;
        }
        return $this->_root_node;
    }

    private function start_element_handler($parser, $name, $attribs) {
        if (!$this->_root_node) {
            $new_node = new XMLNode($name, $attribs);
            $this->_root_node = $new_node;
            $this->_curr_node = $new_node;
        }
        else {
            $old_node = $this->_curr_node;
            $new_node = new XMLNode($name, $attribs, $old_node);
            $old_node->setChildNode($new_node);
            $this->_curr_node = $new_node;
        }

    }

    private function text_handler($parser, $text) {
        if ($this->_curr_node)
            $this->_curr_node->setText($text);
    }

    private function end_element_handler($parser, $name) {
        if ($this->_root_node) {
            $this->_curr_node = $this->_curr_node->getParentNode();
        }
    }
}
?>
