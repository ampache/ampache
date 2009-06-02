<?php
    define ( 'NS_MMD_1', "http://musicbrainz.org/ns/mmd-1.0#" );
    
    require_once( 'xml/xmlParser.php' );
    
    require_once( 'mbUtil.php' );
    require_once( 'mbRelation.php' );
    require_once( 'mbEntity.php' );
    require_once( 'mbArtistAlias.php' );
    require_once( 'mbArtist.php' );
    require_once( 'mbReleaseEvent.php' );
    require_once( 'mbRelease.php' );
    require_once( 'mbTrack.php' );
    require_once( 'mbDisc.php' );
    require_once( 'mbLabel.php' );
    require_once( 'mbLabelAlias.php' );
    require_once( 'mbTag.php' );
    require_once( 'mbResults.php' );
    require_once( 'mbMetadata.php' );
    require_once( 'mbFilter.php' );
    require_once( 'mbInclude.php' );
    require_once( 'mbWebService.php' );
    require_once( 'mbXmlParser.php' );
    require_once( 'mbFactory.php' );

    class mbRequestError extends Exception { }
    class mbResponseError extends Exception { }

    class MusicBrainzQuery {
        private $ws;
        private $ownWs = false;
        private $clientId;

        function MusicBrainzQuery( IWebService $ws=null, $clientId = '' ) {
            if ( $ws != null )
              $this->ws = $ws;
            else {
              $this->ws = new mbWebService();
              $this->ownWs = true;
            }
            
            $this->clientId = $clientId;
        }

        function getUserByName( $name ) {
            $metadata = $this->getFromWebService( "user", "", null, mbUserFilter().name($name) );
            $list = $metadata->getUserList(true);
            
            if ( count($list) > 0 ) {
                return $list[0];
            }
            
            throw mbResponseError("response didn't contain user data");
        }
        
        function getArtists( mbArtistFilter $artist_filters ) {
            $metadata = $this->getFromWebService( "artist", "", null, $artist_filters );
            return $metadata->getArtistResults2(true);
        }

        function getReleases( mbReleaseFilter $release_filters ) {
            $metadata = $this->getFromWebService( "release", "", null, $release_filters );
            return $metadata->getReleaseResults2(true);
        }
        
        function getTracks( mbTrackFilter $track_filters ) {
            $metadata = $this->getFromWebService( "track", "", null, $track_filters );
            return $metadata->getTrackResults2(true);
        }
        
        function getArtistById( $aID, mbArtistIncludes $artist_includes ) {
            try {
                $id = extractUuid($aID);
            } catch ( mbValueError $e ) {
                throw new mbRequestError($e->getMessage(),$e->getCode());
            }
            $metadata = $this->getFromWebService( "artist", $id, $artist_includes );
            $artist = $metadata->getArtist(true);
            return $artist;
        }

        function getReleaseById( $rID, mbReleaseIncludes $release_includes ) {
            try {
                $id = extractUuid($rID);
            } catch ( mbValueError $e ) {
                throw new mbRequestError($e->getMessage(),$e->getCode());
            }
            $metadata = $this->getFromWebService( "release", $id, $release_includes );
            $release = $metadata->getRelease(true);
            return $release;
        }

        function getTrackById( $tID, mbTrackIncludes $track_includes ) {
            try {
                $id = extractUuid($tID);
            } catch ( mbValueError $e ) {
                throw new mbRequestError($e->getMessage(),$e->getCode());
            }
            $metadata = $this->getFromWebService( "track", $id, $track_includes );
            $track = $metadata->getTrack(true);
            return $track;
        }

        protected function getFromWebService( $entity, $id, $includes=null, $filters=null ) {
            $includeList = $includes ? $includes->createIncludeTags() : null;
            $filterList  = $filters  ? $filters->createParameters()  : null;
            $content = $this->ws->get( $entity, $id, $includeList, $filterList );
            
            try {
                $parser = new mbXmlParser();
                $parsed_content = $parser->parse($content);
                return $parsed_content;
            } catch ( mbParseError $e ) {
                throw new mbResponseError( $e->getMessage(), $e->getCode() );
            }
        }

        function submitPuids( array $tracks2puids ) {
            if ( empty($this->clientId) ) {
                throw WebServiceError("Please supply a client ID");
            }
            $params = array(
                array( 'client', $this->clientId )
            );
            foreach ( $tracks2puids as $puid => $track ) {
                $params[] = array( 'puid', extractUuid($puid).' '.$track );
            }
            $this->ws->post("track", "", urlencode($params) );
        }
    }
?>
