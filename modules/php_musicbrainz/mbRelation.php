<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
    class mbRelation {
        const DIR_BOTH     = 0;
        const DIR_FORWARD  = 1;
        const DIR_BACKWARD = 2;
        
        const TO_ARTIST  = "http://musicbrainz.org/ns/mmd-1.0#Artist";
        const TO_TRACK   = "http://musicbrainz.org/ns/mmd-1.0#Track";
        const TO_RELEASE = "http://musicbrainz.org/ns/mmd-1.0#Release";
        const TO_URL     = "http://musicbrainz.org/ns/mmd-1.0#Url";

        private $type;
        private $targetType;
        private $targetId;
        private $direction;
        private $attributes;
        private $beginDate;
        private $endDate;
        private $target;

        function mbRelation( $relationType = '',
                           $targetType = '',
                           $targetId = '',
                           $direction = DIR_BOTH,
                           array $attributes = array(),
                           $beginDate = '',
                           $endDate = '',
                           MusicBrainzEntity $target = null ) {
            $this->type = $relationType;
            $this->targetType = $targetType;
            $this->targetId = $targetId;
            $this->direction = $direction;
            $this->attributes = $attributes;
            $this->beginDate = $beginDate;
            $this->endDate = $endDate;
            $this->target = $target;
        }

        function setType( $type ) {
            $this->type = $type;
        }

        function getType() {
            return $this->type;
        }

        function setTargetType( $ttype ) {
            $this->targetType = $ttype;
        }

        function getTargetType() {
            return $this->targetType;
        }

        function setTargetId( $tid ) {
            $this->targetId = $tid;
        }

        function getTargetId() {
            return $this->targetId;
        }

        function setBeginDate( $bdate ) {
            $this->beginDate = $bdate;
        }

        function getBeginDate() {
            return $this->beginDate;
        }

        function setEndDate( $edate ) {
            $this->endDate = $edate;
        }

        function getEndDate() {
            return $this->endDate;
        }
        
        function getDirection() {
            return $this->direction;
        }

        function setDirection( $dir ) {
            $this->direction = $dir;
        }

        function getTarget() {
            return $this->target;
        }

        function setTarget( MusicBrainzEntity $entity=null ) {
            $this->target = $entity;
        }

        function &getAttributes() {
            return $this->attributes;
        }

        function addAttribute( $value ) {
            $this->attributes[] = $value;
        }

        function getNumAttributes() {
            return count($this->attributes);
        }

        function getAttribute( $i ) {
            return $this->attributes( $i );
        }
    }
?>
