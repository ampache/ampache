<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
	class XMLNode {
		private $name;
		private $attributes;
		private $childNodes = array();
		private $parentNode;

		function XMLNode( $name, array $attributes, $parent=null ) {
			$this->name = $name;
			$this->attributes = $attributes;
			$this->parentNode = $parent;
		}

		function getParentNode() {
			return $this->parentNode;
		}
		
		function nChildNodes() {
			return sizeof($this->childNodes);
		}

		function getChildNode( $i ) {
			return $this->childNodes[$i];
		}

		function setChildNode( XMLNode $node ) {
			$this->childNodes[] = $node;
		}

		function getName() {
			return $this->name;
		}

		function getAttribute( $name ) {
			$name = strtoupper($name);
			return isset($this->attributes[$name]) ? $this->attributes[$name] :
				   "";
		}

		function setText( $text ) {
			$this->attributes['TEXT'] = $text;
		}

		function getText() {
			return $this->attributes['TEXT'];
		}
	}
	
	class xmlParser {
		private $_parser;
		private $_root_node = null;
		private $_curr_node = null;

		public function xmlParser() {
			$this->_parser = xml_parser_create();

			xml_set_object($this->_parser, $this);
			xml_set_default_handler( $this->_parser, 'text_handler' );
			xml_set_element_handler( $this->_parser,
									 'start_element_handler',
									 'end_element_handler' );
		}

		public function destroy() {
			xml_parser_free($this->_parser);
		}

		public function lastError() {
			return $this->_last_error;
		}
		
		public function parse( $data ) {
			if ( !xml_parse( $this->_parser, $data ) ) {
				$this->_last_error = xml_get_current_line_number($this->_parser) . ": ". xml_error_string($this->_parser);
				return false;
			}
			return $this->_root_node;
		}
		
		private function start_element_handler( $parser, $name, $attribs ) {
			if ( !$this->_root_node ) {
				$new_node = new XMLNode( $name, $attribs );
				$this->_root_node = $new_node;
				$this->_curr_node = $new_node;
			}
			else {
				$old_node = $this->_curr_node;
				$new_node = new XMLNode( $name, $attribs, $old_node );
				$old_node->setChildNode( $new_node );
				$this->_curr_node = $new_node;
			}
			
		}

		private function text_handler( $parser, $text ) {
			if ( $this->_curr_node )
				$this->_curr_node->setText( $text );
		}
		
		private function end_element_handler( $parser, $name ) {
			if ( $this->_root_node ) {
				$this->_curr_node = $this->_curr_node->getParentNode();
			}
		}
	}
?>
