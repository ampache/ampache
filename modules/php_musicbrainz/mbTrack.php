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
class mbTrack extends MusicBrainzEntity {
    private $title;
    private $artist = null;
    private $duration = 0;
    private $releases;
    private $releasesCount = 0;
    private $releasesOffset = 0;

    public function __construct($id = '', $title = '') {
        parent::__construct($id);
        $this->title = $title;
    }

    public function getTitle   (         ) { return $this->title;         }
    public function setTitle   ($title   ) { $this->title = $title;       }
    public function getDuration(         ) { return $this->duration;      }
    public function setDuration($duration) { $this->duration = $duration; }

    public function getArtist() {
        return $this->artist;
    }

    public function setArtist(mbArtist $artist) {
        $this->artist = $artist;
    }

    public function getReleases() {
        return $this->releases;
    }

    public function addRelease(mbRelease $release) {
        $this->releases[] = $release;
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
}
?>
