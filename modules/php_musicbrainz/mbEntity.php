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
abstract class MusicBrainzEntity {
    private $id;
    private $relations;
    private $tags;

    public function __construct($id) {
        $this->id = $id;
        $this->relations = array();
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getRelations($target_type='', $relation_type='') {
        if ($target_type == '' && $relation_type == '')
            return $this->relations;

        $result = array();

        if (empty($target_type)) {
            foreach ($this->relations as $relation) {
                if ($relation->getType() == $relation_type) {
                    $result[] = $relation;
                }
            }
        }
        else if (empty($relation_type)) {
            foreach ($this->relations as $relation) {
                if ($relation->getTargetType() == $target_type) {
                    $result[] = $relation;
                }
            }
        }
        else {
            foreach ($this->relations as $relation) {
                if ($relation->getTargetType() == $target_type
                &&   $relation->getType() == $relation_type) {
                    $result[] = $relation;
                }
            }
        }

        return $result;
    }

    public function addRelation(mbRelation $relation) {
        $this->relations[] = $relation;
    }

    public function getNumRelations() {
        return count($this->relations);
    }

    public function getRelation($i) {
        return $this->relations[$i];
    }

    public function getTags() {
        return $this->tags;
    }

    public function getNumTags() {
        return count($this->tags);
    }

    public function getTag($i) {
        return $this->tags[$i];
    }
}
?>
