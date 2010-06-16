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
class mbLabel extends MusicBrainzEntity {
    const TYPE_GROUP  = "http://musicbrainz.org/ns/mmd-1.0#Group";
    const TYPE_PERSON = "http://musicbrainz.org/ns/mmd-1.0#Person";

    private $type;
    private $name;
    private $sortName;
    private $disambiguation;
    private $beginDate;
    private $endDate;
    private $releases;
    private $releasesCount;
    private $releasesOffset;
    private $aliases;

    public function __construct($id='', $type='', $name='', $sortName='') {
        parent::__construct($id);
        $this->type = $type;
        $this->name = $name;
        $this->sortName = $sortName;
    }

    public function setType($type) { $this->type = $type; }
    public function getType() { return $this->type; }
    public function setName($name) { $this->name = $name; }
    public function getName() { return $this->name; }
    public function setSortName($sortName) { $this->sortName = $sortName; }
    public function getSortName() { return $this->sortName; }
    public function setDisambiguation($disambiguation) { $this->disambiguation = $disambiguation; }
    public function getDisambiguation() { return $this->disambiguation; }
    public function setBeginDate($beginDate) { $this->beginDate = $beginDate; }
    public function getBeginDate() { return $this->beginDate; }
    public function setEndDate($endDate) { $this->endDate = $endDate; }
    public function getEndDate() { return $this->endDate; }

    public function getUniqueName() {
        return empty($this->disambiguation) ? $this->name :
               $this->name . ' (' . $this->disambiguation . ')';
    }

    public function getReleases() {
        return $this->releases;
    }

    public function addRelease(mbRelease $release) {
        $this->releases[] = $release;
    }

    public function getAliases() {
        return $this->aliases;
    }

    public function addAlias(mbLabelAlias $alias) {
        $this->aliases[] = $alias;
    }

    public function getNumReleases() {
        return count($this->releases);
    }

    public function getRelease($i) {
        return $this->releases[$i];
    }

    public function getReleasesOffset() {
        return $this->releasesOffset;
    }

    public function setReleasesOffset($relOffset) {
        $this->releasesOffset = $relOffset;
    }

    public function getReleasesCount() {
        return $this->releasesCount;
    }

    public function setReleasesCount($relCount) {
        $this->releasesCount = $relCount;
    }

    public function getNumAliases() {
        return count($this->aliases);
    }

    public function getAlias($i) {
        return $this->aliases[$i];
    }
}
?>
