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
