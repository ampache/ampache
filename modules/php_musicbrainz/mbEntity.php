<?php
    class MusicBrainzEntity {
        private $id;
        private $relations;
        private $tags;

        protected function MusicBrainzEntity( $id ) {
            $this->id = $id;
            $this->relations = array();
        }

        function getId() {
            return $this->id;
        }

        function setId( $id ) {
            $this->id = $id;
        }

        function &getRelations( $target_type='', $relation_type='' ) {
            if ( $target_type == '' && $relation_type == '' )
                return $this->relations;
                
            $result = array();

            if ( empty($target_type) ) {
                foreach ( $this->relations as $relation ) {
                    if ( $relation->getType() == $relation_type ) {
                        $result[] = $relation;
                    }
                }
            }
            else if ( empty($relation_type) ) {
                foreach ( $this->relation_tracks as $relation ) {
                    if ( $relation->getTargetType() == $target_type ) {
                        $result[] = $relation;
                    }
                }
            }
            else {
                foreach ( $this->relations as $relation ) {
                    if ( $relation->getTargetType() == $target_type
                    &&   $relation->getType() == $relation_type ) {
                        $result[] = $relation;
                    }
                }
            }

            return $result;
        }

        function addRelation( mbRelation $relation ) {
            $this->relations[] = $relation;
        }

        function getNumRelations() {
            return count($this->relations);
        }

        function &getRelation( $i ) {
            return $this->relations[$i];
        }

        function &getTags() {
            return $this->tags;
        }

        function getNumTags() {
            return count($this->tags);
        }

        function &getTag( $i ) {
            return $this->tags[$i];
        }
    }
?>
