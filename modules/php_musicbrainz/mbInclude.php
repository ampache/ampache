<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
interface MusicBrainzInclude {
    public function createIncludeTags();
}

class mbArtistIncludes implements MusicBrainzInclude {
    private $includes = array();

    public function createIncludeTags() {
        return $this->includes;
    }

    public function releases( $type ) {
        $this->includes[] = "sa-" . extractFragment( $type );
        return $this;
    }

    public function vaReleases( $type ) {
        $this->includes[] = "va-" . extractFragment( $type );
        return $this;
    }

    public function aliases() {
        $this->includes[] = 'aliases';
        return $this;
    }

    public function artistRelations() {
        $this->includes[] = 'artist-rels';
        return $this;
    }

    public function releaseRelations() {
        $this->includes[] = 'release-rels';
        return $this;
    }

    public function trackRelations() {
        $this->includes[] = 'track-rels';
        return $this;
    }

    public function urlRelations() {
        $this->includes[] = 'url-rels';
        return $this;
    }

    public function releaseEvents() {
        $this->includes[] = 'release-events';
        return $this;
    }
}

class mbReleaseIncludes implements MusicBrainzInclude {
    private $includes;

    public function createIncludeTags() {
        return $this->includes;
    }

    public function artist() {
        $this->includes[] = 'artist';
        return $this;
    }

    public function counts() {
        $this->includes[] = 'counts';
        return $this;
    }

    public function releaseEvents() {
        $this->includes[] = 'release-events';
        return $this;
    }

    public function discs() {
        $this->includes[] = 'discs';
        return $this;
    }

    public function tracks() {
        $this->includes[] = 'tracks';
        return $this;
    }

    public function artistRelations() {
        $this->includes[] = 'artist-rels';
        return $this;
    }

    public function releaseRelations() {
        $this->includes[] = 'release-rels';
        return $this;
    }

    public function trackRelations() {
        $this->includes[] = 'track-rels';
        return $this;
    }

    public function urlRelations() {
        $this->includes[] = 'url-rels';
        return $this;
    }
}

class mbTrackIncludes implements MusicBrainzInclude {
    private $includes;

    public function createIncludeTags() {
        return $this->includes;
    }

    public function artist() {
        $this->includes[] = 'artist';
        return $this;
    }

    public function releases() {
        $this->includes[] = 'releases';
        return $this;
    }

    public function puids() {
        $this->includes[] = 'puids';
        return $this;
    }

    public function artistRelations() {
        $this->includes[] = 'artist-rels';
        return $this;
    }

    public function releaseRelations() {
        $this->includes[] = 'release-rels';
        return $this;
    }

    public function trackRelations() {
        $this->includes[] = 'track-rels';
        return $this;
    }

    public function urlRelations() {
        $this->includes[] = 'url-rels';
        return $this;
    }
}
?>
