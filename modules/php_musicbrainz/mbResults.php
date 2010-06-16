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
class mbResult {
    private $score;
    private $count;
    private $offset;

    public function __construct($score) {
        $this->score = $score;
    }

    public function getScore(      ) { return $this->score;   }
    public function setScore($score) { $this->score = $score; }
    public function getCount(      ) { return $this->count;   }
    public function setCount($count) { $this->count = $count; }
    public function getOffset(       ) { return $this->offset;    }
    public function setOffset($offset) { $this->offset = $offset; }
}

class mbArtistResult extends mbResult {
    private $artist;

    public function __construct(Artist $artist, $score) {
        parent::__construct($score);
        $this->artist = $artist;
    }

    public function setArtist(mbArtist $artist) {
        $this->artist = $artist;
    }

    public function getArtist() {
        return $this->artist;
    }
}

class mbReleaseResult extends mbResult {
    private $release;

    public function __construct(mbRelease $release, $score) {
        parent::__construct($score);
        $this->release = $release;
    }

    public function setRelease(Release $release) {
        $this->release = $release;
    }

    public function getRelease() {
        return $this->release;
    }
}

class mbTrackResult extends mbResult {
    private $track;

    public function __construct(mbTrack $track, $score) {
        parent::__construct($score);
        $this->track = $track;
    }

    public function setTrack(mbTrack $track) {
        $this->track = $track;
    }

    public function getTrack() {
        return $this->track;
    }
}
?>
