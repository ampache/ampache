<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
    class mbMetadata {
        private $artist = null;
        private $track = null;
        private $release = null;
        private $label = null;
        private $artistList;
        private $trackList;
        private $releaseList;
        private $userList;

        function mbMetadata() {
            $this->artistList  = array();
            $this->trackList   = array();
            $this->releaseList = array();
            $this->userList    = array();
        }

        function setArtist ( mbArtist $artist   ) { $this->artist  = $artist;  }
        function setTrack  ( mbTrack $track     ) { $this->track   = $track;   }
        function setRelease( mbRelease $release ) { $this->release = $release; }
        function setLabel  ( mbLabel $label     ) { $this->label   = $label;   }

        function getArtist( $remove=false ) {
            $a = $this->artist;
            if ( $remove )
              $this->artist = null;
            return $a;
        }

        function getTrack( $remove=false ) {
            $t = $this->track;
            if ( $remove )
              $this->track = null;
            return $t;
        }

        function getRelease( $remove=false ) {
            $r = $this->release;
            if ( $remove )
              $this->release = null;
            return $r;
        }

        function getLabel( $remove=false ) {
            $l = $this->label;
            if ( $remove )
              $this->label = null;
            return $l;
        }

        function &getUserList      () { return $this->userList;    }
        function &getArtistResults () { return $this->artistList;  }
        function &getTrackResults  () { return $this->trackList;   }
        function &getReleaseResults() { return $this->releaseList; }
        
        function getUserList2( $remove ) {
            $ul = $this->userList;
            $this->userList = array();
            return $ul;
        }
        
        function getArtistResults2( $remove ) {
            $al = $this->artistList;
            $this->artistList = array();
            return $al;
        }

        function getTrackResults2( $remove ) {
            $tl = $this->trackList;
            $this->trackList = array();
            return $tl;
        }

        function getReleaseResults2( $remove ) {
            $rl = $this->releaseList;
            $this->releaseList = array();
            return $rl;
        }
    }
?>
