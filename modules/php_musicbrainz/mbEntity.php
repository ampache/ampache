<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
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
            foreach ($this->relation_tracks as $relation) {
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
