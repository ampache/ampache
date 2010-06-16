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

interface MusicBrainzFilter {
    function createParameters();
}

class mbArtistFilter implements MusicBrainzFilter {
    private $parameters = array();

    function createParameters() {
        return $this->parameters;
    }

    function name( $name ) {
        $this->parameters['name'] = $name;
        return $this;
    }

    function limit( $limit ) {
        $this->parameters['limit'] = $limit;
        return $this;
    }

    function offset( $offset ) {
        $this->parameters['offset'] = $offset;
        return $this;
    }
}

class mbReleaseFilter implements MusicBrainzFilter {
    private $parameters = array();

    function createParameters() {
        return $this->parameters;
    }

    function title( $title ) {
        $this->parameters['title'] = $title;
        return $this;
    }

    function discId( $discid ) {
        $this->parameters['discid'] = $discid;
        return $this;
    }

    function releaseType( $rtype ) {
        $type = extractFragment($rtype);

        if ( isset( $this->parameters['releasetypes'] ) ) {
            $this->parameters['releasetypes'] .= ' ' . $type;
        }
        else {
            $this->parameters['releasetypes'] = $type;
        }

        return $this;
    }

    function artistName( $name ) {
        $this->parameters['artist'] = $name;
        return $this;
    }

    function artistId( $id ) {
        $this->parameters['artistid'] = $id;
        return $this;
    }

    function asin( $asin ) {
        $this->parameters['asin'] = $asin;
        return $this;
    }

    function limit( $limit ) {
        $this->parameters['limit'] = $limit;
        return $this;
    }

    function offset( $offset ) {
        $this->parameters['offset'] = $offset;
        return $this;
    }
}

class mbTrackFilter implements MusicBrainzFilter {
    private $parameters = array();

    function createParameters() {
        return $this->parameters;
    }

    function title( $title ) {
        $this->parameters['title'] = $title;
        return $this;
    }

    function artistName( $name ) {
        $this->parameters['artist'] = $name;
        return $this;
    }

    function artistId( $id ) {
        $this->parameters['artistid'] = $id;
        return $this;
    }

    function releaseTitle( $title ) {
        $this->parameters['release'] = $title;
        return $this;
    }

    function releaseId( $id ) {
        $this->parameters['releaseid'] = $id;
        return $this;
    }

    function duration( $duration ) {
        $this->parameters['duration'] = $duration;
        return $this;
    }

    function puid( $puid ) {
        $this->parameters['puid'] = $puid;
        return $this;
    }

    function limit( $limit ) {
        $this->parameters['limit'] = $limit;
        return $this;
    }

    function offset( $offset ) {
        $this->parameters['offset'] = $offset;
        return $this;
    }
}

class mbUserFilter implements MusicBrainzFilter {
    private $parameters = array();

    function createParameters() {
        return $this->parameters;
    }

    function name( $value ) {
        $this->parameters['name'] = $value;
    }
}

class mbRatingFilter implements MusicBrainzFilter {
    private $parameters = array();

    public function createParameters() {
        return $this->parameters;
    }

    public function entity($value) {
        $this->parameters['entity'] = $value;
    }

	public function id($value) {
		$this->parameters['id'] = $value;
	}
}
?>
