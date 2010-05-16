<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
class mbUser {
    private $name;
    private $showNag = false;
    private $types = array();

    public function __construct() {
    }

    public function setName($name) { $this->name = $name; }
    public function getName() { return $this->name; }

    public function getShowNag() {
        return $this->showNag;
    }

    public function setShowNag($value) {
        $this->setShowNag = $value;
    }

    public function addType($type) {
        $this->types[] = $type;
    }

    public function getNumTypes() {
        return count($this->types);
    }

    public function getType($i) {
        return $this->types[$i];
    }
}
?>
