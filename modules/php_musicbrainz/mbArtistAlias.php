<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
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
