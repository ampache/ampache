<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
	class mbUser {
		private $name;
		private $showNag = false;
		private $types = array();
		
		function mbUser() {
		}
		
		function setName( $name ) { $this->name = $name; }
		function getName() { return $this->name; }
		
		function getShowNag() {
			return $this->showNag;
		}
		
		function setShowNag( $value ) {
			$this->setShowNag = $value;
		}
		
		function addType( $type ) {
			$this->types[] = $type;
		}
		
		function getNumTypes() {
			return count($this->types);
		}
		
		function getType( $i ) {
			return $this->types[$i];
		}
	}
?>
