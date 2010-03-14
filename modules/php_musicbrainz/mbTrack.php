<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
	class mbTrack extends MusicBrainzEntity {
		private $title;
		private $artist = null;
		private $duration = 0;
		private $releases;
		private $releasesCount = 0;
		private $releasesOffset = 0;

		function mbTrack( $id = '', $title = '' ) {
			parent::MusicBrainzEntity($id);
			$this->title = $title;
		}

		function getTitle   (		   ) { return $this->title;		 }
		function setTitle   ( $title	) { $this->title = $title;	   }
		function getDuration(		   ) { return $this->duration;	  }
		function setDuration( $duration ) { $this->duration = $duration; }

		function getArtist() {
			return $this->artist;
		}

		function setArtist( mbArtist $artist ) {
			$this->artist = $artist;
		}

		function &getReleases() {
			return $this->releases;
		}

		function addRelease( mbRelease $release ) {
			$this->releases[] = $release;
		}

		function getNumReleases() {
			return count($this->releases);
		}

		function getRelease( $i ) {
			return $this->releases[$i];
		}

		function getReleasesOffset() {
			return $this->releasesOffset;
		}

		function setReleasesOffset( $relOffset ) {
			$this->releasesOffset = $relOffset;
		}

		function getReleasesCount() {
			return $this->releasesCount;
		}

		function setReleasesCount( $relCount ) {
			$this->releasesCount = $relCount;
		}
	}
?>
