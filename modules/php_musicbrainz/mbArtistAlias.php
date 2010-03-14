<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
    class mbArtistAlias {
        private $value;
        private $type;
        private $script;
        
        function mbAristAlias( $value='', $type='', $script='' ) {
            $this->value = $value;
            $this->type = $type;
            $this->script = $script;
        }

        function getType() { return $this->type; }
        function setType( $type ) { $this->type = $type; }
        function getValue() { return $this->value; }
        function setValue( $value ) { $this->value = $value; }
        function getScript() { return $this->script; }
        function setScript( $script ) { $this->script = $script; }
    }
?>
