<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
	class mbResult {
		private $score;
		private $count;
		private $offset;

		function mbResult( $score ) {
			$this->score = $score;
		}

		function getScore(		) { return $this->score;   }
		function setScore( $score ) { $this->score = $score; }
		function getCount(		) { return $this->count;   }
		function setCount( $count ) { $this->count = $count; }
		function getOffset(		 ) { return $this->offset;	}
		function setOffset( $offset ) { $this->offset = $offset; }
	}

	class mbArtistResult extends mbResult {
		private $artist;

		function mbArtistResult( Artist $artist, $score ) {
			parent::mbResult($score);
			$this->artist = $artist;
		}

		function setArtist( mbArtist $artist ) {
			$this->artist = $artist;
		}

		function getArtist() {
			return $this->artist;
		}
	}

	class mbReleaseResult extends mbResult {
		private $release;

		function mbReleaseResult( mbRelease $release, $score ) {
			parent::mbResult($score);
			$this->release = $release;
		}

		function setRelease( Release $release ) {
			$this->release = $release;
		}

		function getRelease() {
			return $this->release;
		}
	}

	class mbTrackResult extends mbResult {
		private $track;

		function mbTrackResult( mbTrack $track, $score ) {
			parent::mbResult($score);
			$this->track = $track;
		}

		function setTrack( mbTrack $track ) {
			$this->track = $track;
		}

		function getTrack() {
			return $this->track;
		}
	}
?>
