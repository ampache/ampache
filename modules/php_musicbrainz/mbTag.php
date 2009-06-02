<?php
    class mbTag {
        private $name;
        private $count;

        function mbTag( $name='', $count=0 ) {
            $this->name = $name;
            $this->count = $count;
        }

        function setName ( $name  ) { $this->name = $name;   }
        function getName (        ) { return $this->name;    }
        function setCount( $count ) { $this->count = $count; }
        function getCount(        ) { return $this->count;   }
    }
?>
