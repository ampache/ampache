<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
    class mbRelease extends MusicBrainzEntity {
        // Types
        const TYPE_NONE          = "http://musicbrainz.org/ns/mmd-1.0#None";
        
        const TYPE_ALBUM         = "http://musicbrainz.org/ns/mmd-1.0#Album";
        const TYPE_SINGLE        = "http://musicbrainz.org/ns/mmd-1.0#Single";
        const TYPE_EP            = "http://musicbrainz.org/ns/mmd-1.0#EP";
        const TYPE_COMPILATION   = "http://musicbrainz.org/ns/mmd-1.0#Compilation";
        const TYPE_SOUNDTRACK    = "http://musicbrainz.org/ns/mmd-1.0#Soundtrack";
        const TYPE_SPOKENWORD    = "http://musicbrainz.org/ns/mmd-1.0#Spokenword";
        const TYPE_INTERVIEW     = "http://musicbrainz.org/ns/mmd-1.0#Interview";
        const TYPE_AUDIOBOOK     = "http://musicbrainz.org/ns/mmd-1.0#Audiobook";
        const TYPE_LIVE          = "http://musicbrainz.org/ns/mmd-1.0#Live";
        const TYPE_REMIX         = "http://musicbrainz.org/ns/mmd-1.0#Remix";
        const TYPE_OTHER         = "http://musicbrainz.org/ns/mmd-1.0#Other";

        // Statuses
        const TYPE_OFFICIAL       = "http://musicbrainz.org/ns/mmd-1.0#Official";
        const TYPE_PROMOTION      = "http://musicbrainz.org/ns/mmd-1.0#Promotion";
        const TYPE_BOOTLEG        = "http://musicbrainz.org/ns/mmd-1.0#Bootleg";
        const TYPE_PSEUDO_RELEASE = "http://musicbrainz.org/ns/mmd-1.0#Pseudo-Release";
        
        private $title;
        private $textLanguage;
        private $textScript;
        private $asin;
        private $types = array();
        private $artist = null;
        private $tracks = array();
        private $tracksOffset = 0;
        private $tracksCount  = 0;
        private $discs = array();
        private $releaseEvents = array();

        function mbRelease( $id = '', $title = '' ) {
            parent::MusicBrainzEntity($id);
            $this->title = $title;
        }

        function getTitle() { return $this->title; }
        function setTitle( $title ) { $this->title = $title; }
        function getTextLanguage() { return $this->textLanguage; }
        function setTextLanguage( $tlang ) { $this->textLanguage = $tlang; }
        function getTextScript() { return $this->textScript; }
        function setTextScript( $tscript ) { $this->textScript = $tscript; }
        function getAsin() { return $this->asin; }
        function setAsin( $asin ) { $this->asin = $asin; }

        function getArtist() {
            return $this->artist;
        }

        function setArtist( Artist $artist ) {
            $this->artist = $artist;
        }

        function &getTracks() {
            return $this->tracks;
        }

        function getTracksOffset() {
            return $this->tracksOffset;
        }

        function setTracksOffset( $value ) {
            $this->tracksOffset = $value;
        }

        function getTracksCount() {
            return $this->tracksCount;
        }

        function setTracksCount( $tracksCount ) {
            $this->tracksCount = $tracksCount;
        }

        function &getDiscs() {
            return $this->discs;
        }

        function &getReleaseEvents() {
            return $this->releaseEvents;
        }

        function getNumReleaseEvents() {
            return count($this->releaseEvents);
        }

        function getReleaseEvent( $i ) {
            return $this->releaseEvents[$i];
        }

        function getNumDiscs() {
            return count($this->discs);
        }

        function getDisc( $i ) {
            return $this->discs[$i];
        }

        function getNumTracks() {
            return count($this->tracks);
        }

        function getTrack( $i ) {
            return $this->tracks[$i];
        }

        function setTypes( array $types ) {
            $this->types = $types;
        }

        function &getTypes() {
            return $this->types;
        }

        function getNumTypes() {
            return count($this->types);
        }

        function getType( $i ) {
            return $this->types[$i];
        }
    }
?>
