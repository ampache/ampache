<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
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
