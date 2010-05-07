<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */

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
