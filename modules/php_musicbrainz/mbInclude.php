<?php
    interface MusicBrainzInclude {
        function createIncludeTags();
    }

    class mbArtistIncludes implements MusicBrainzInclude {
        private $includes = array();

        function createIncludeTags() {
            return $this->includes;
        }

        function releases( $type ) {
            $this->includes[] = "sa-" . extractFragment( $type );
            return $this;
        }

        function vaReleases( $type ) {
            $this->includes[] = "va-" . extractFragment( $type );
            return $this;
        }

        function aliases() {
            $this->includes[] = 'aliases';
            return $this;
        }

        function artistRelations() {
            $this->includes[] = 'artist-rels';
            return $this;
        }

        function releaseRelations() {
            $this->includes[] = 'release-rels';
            return $this;
        }

        function trackRelations() {
            $this->includes[] = 'track-rels';
            return $this;
        }

        function urlRelations() {
            $this->includes[] = 'url-rels';
            return $this;
        }

        function releaseEvents() {
            $this->includes[] = 'release-events';
            return $this;
        }
    }

    class mbReleaseIncludes implements MusicBrainzInclude {
        private $includes;

        function createIncludeTags() {
            return $this->includes;
        }

        function artist() {
            $this->includes[] = 'artist';
            return $this;
        }

        function counts() {
            $this->includes[] = 'counts';
            return $this;
        }

        function releaseEvents() {
            $this->includes[] = 'release-events';
            return $this;
        }

        function discs() {
            $this->includes[] = 'discs';
            return $this;
        }

        function tracks() {
            $this->includes[] = 'tracks';
            return $this;
        }

        function artistRelations() {
            $this->includes[] = 'artist-rels';
            return $this;
        }

        function releaseRelations() {
            $this->includes[] = 'release-rels';
            return $this;
        }

        function trackRelations() {
            $this->includes[] = 'track-rels';
            return $this;
        }

        function urlRelations() {
            $this->includes[] = 'url-rels';
            return $this;
        }
    }

    class mbTrackIncludes implements MusicBrainzInclude {
        private $includes;

        function createIncludeTags() {
            return $this->includes;
        }

        function artist() {
            $this->includes[] = 'artist';
            return $this;
        }

        function releases() {
            $this->includes[] = 'releases';
            return $this;
        }

        function puids() {
            $this->includes[] = 'puids';
            return $this;
        }

        function artistRelations() {
            $this->includes[] = 'artist-rels';
            return $this;
        }

        function releaseRelations() {
            $this->includes[] = 'release-rels';
            return $this;
        }

        function trackRelations() {
            $this->includes[] = 'track-rels';
            return $this;
        }

        function urlRelations() {
            $this->includes[] = 'url-rels';
            return $this;
        }
    }
?>
