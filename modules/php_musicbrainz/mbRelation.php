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

    public function __construct($relationType = '',
							$targetType = '',
							$targetId = '',
							$direction = self::DIR_BOTH,
							array $attributes = array(),
							$beginDate = '',
							$endDate = '',
							MusicBrainzEntity $target = null) {
        $this->type = $relationType;
        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->direction = $direction;
        $this->attributes = $attributes;
        $this->beginDate = $beginDate;
        $this->endDate = $endDate;
        $this->target = $target;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getType() {
        return $this->type;
    }

    public function setTargetType($ttype) {
        $this->targetType = $ttype;
    }

    public function getTargetType() {
        return $this->targetType;
    }

    public function setTargetId($tid) {
        $this->targetId = $tid;
    }

    public function getTargetId() {
        return $this->targetId;
    }

    public function setBeginDate($bdate) {
        $this->beginDate = $bdate;
    }

    public function getBeginDate() {
        return $this->beginDate;
    }

    public function setEndDate($edate) {
        $this->endDate = $edate;
    }

    public function getEndDate() {
        return $this->endDate;
    }

    public function getDirection() {
        return $this->direction;
    }

    public function setDirection($dir) {
        $this->direction = $dir;
    }

    public function getTarget() {
        return $this->target;
    }

    public function setTarget(MusicBrainzEntity $entity=null) {
        $this->target = $entity;
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function addAttribute($value) {
        $this->attributes[] = $value;
    }

    public function getNumAttributes() {
        return count($this->attributes);
    }

    public function getAttribute($i) {
        return $this->attributes($i);
    }
}
?>
