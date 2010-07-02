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
class mbRelease extends MusicBrainzEntity {
    // Types
    const TYPE_NONE        = "http://musicbrainz.org/ns/mmd-1.0#None";

    const TYPE_ALBUM    = "http://musicbrainz.org/ns/mmd-1.0#Album";
    const TYPE_SINGLE    = "http://musicbrainz.org/ns/mmd-1.0#Single";
    const TYPE_EP        = "http://musicbrainz.org/ns/mmd-1.0#EP";
    const TYPE_COMPILATION    = "http://musicbrainz.org/ns/mmd-1.0#Compilation";
    const TYPE_SOUNDTRACK    = "http://musicbrainz.org/ns/mmd-1.0#Soundtrack";
    const TYPE_SPOKENWORD    = "http://musicbrainz.org/ns/mmd-1.0#Spokenword";
    const TYPE_INTERVIEW    = "http://musicbrainz.org/ns/mmd-1.0#Interview";
    const TYPE_AUDIOBOOK    = "http://musicbrainz.org/ns/mmd-1.0#Audiobook";
    const TYPE_LIVE        = "http://musicbrainz.org/ns/mmd-1.0#Live";
    const TYPE_REMIX    = "http://musicbrainz.org/ns/mmd-1.0#Remix";
    const TYPE_OTHER    = "http://musicbrainz.org/ns/mmd-1.0#Other";

    // Statuses
    const TYPE_OFFICIAL    = "http://musicbrainz.org/ns/mmd-1.0#Official";
    const TYPE_PROMOTION    = "http://musicbrainz.org/ns/mmd-1.0#Promotion";
    const TYPE_BOOTLEG    = "http://musicbrainz.org/ns/mmd-1.0#Bootleg";
    const TYPE_PSEUDO_RELEASE = "http://musicbrainz.org/ns/mmd-1.0#Pseudo-Release";

    private $title;
    private $textLanguage;
    private $textScript;
    private $asin;
    private $types = array();
    private $artist = null;
    private $tracks = array();
    private $tracksOffset = 0;
    private $tracksCount  = 0;
    private $discs = array();
    private $releaseEvents = array();

    public function __construct($id = '', $title = '') {
        parent::__construct($id);
        $this->title = $title;
    }

    public function getTitle() { return $this->title; }
    public function setTitle($title) { $this->title = $title; }
    public function getTextLanguage() { return $this->textLanguage; }
    public function setTextLanguage($tlang) { $this->textLanguage = $tlang; }
    public function getTextScript() { return $this->textScript; }
    public function setTextScript($tscript) { $this->textScript = $tscript; }
    public function getAsin() { return $this->asin; }
    public function setAsin($asin) { $this->asin = $asin; }

    public function getArtist() {
        return $this->artist;
    }

    public function setArtist(Artist $artist) {
        $this->artist = $artist;
    }

    public function getTracks() {
        return $this->tracks;
    }

    public function getTracksOffset() {
        return $this->tracksOffset;
    }

    public function setTracksOffset($value) {
        $this->tracksOffset = $value;
    }

    public function getTracksCount() {
        return $this->tracksCount;
    }

    public function setTracksCount($tracksCount) {
        $this->tracksCount = $tracksCount;
    }

    public function getDiscs() {
        return $this->discs;
    }

    public function getReleaseEvents() {
        return $this->releaseEvents;
    }

    public function getNumReleaseEvents() {
        return count($this->releaseEvents);
    }

    public function getReleaseEvent($i) {
        return $this->releaseEvents[$i];
    }

    public function getNumDiscs() {
        return count($this->discs);
    }

    public function getDisc($i) {
        return $this->discs[$i];
    }

    public function getNumTracks() {
        return count($this->tracks);
    }

    public function getTrack($i) {
        return $this->tracks[$i];
    }

    public function setTypes(array $types) {
        $this->types = $types;
    }

    public function getTypes() {
        return $this->types;
    }

    public function getNumTypes() {
        return count($this->types);
    }

    public function getType($i) {
        return $this->types[$i];
    }
}
?>
