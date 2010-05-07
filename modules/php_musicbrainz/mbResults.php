<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
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
