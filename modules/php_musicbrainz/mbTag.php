<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
class mbTag {
    private $name;
    private $count;

    public function __construct($name='', $count=0) {
        $this->name = $name;
        $this->count = $count;
    }

    public function setName($name)		{ $this->name = $name; }
    public function getName()			{ return $this->name; }
    public function setCount($count)	{ $this->count = $count; }
    public function getCount()			{ return $this->count; }
}
?>
