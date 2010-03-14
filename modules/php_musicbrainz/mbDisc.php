<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
	class mbDiscError extends Exception { }

	class mbDisc {
		private $id;
		private $sectors = 0;
		private $firstTrackNum = 0;
		private $lastTrackNum = 0;
		private $tracks;

		function mbDisc( $id = '' ) {
			$this->id = $id;
			$this->tracks = array();
		}

		function setId		   ( $id	) { $this->id = $id;			   }
		function getId		   (		) { return $this->id;			  }
		function setSectors	  ( $sectr ) { $this->sectors = $sectr;	   }
		function getSectors	  (		) { return $this->sectors;		 }
		function setLastTrackNum ( $track ) { $this->lastTrackNum  = $track; }
		function getLastTrackNum (		) { return $this->lastTrackNum;	}
		function setFirstTrackNum( $track ) { $this->firstTrackNum = $track; }
		function getFirstTrackNum(		) { return $this->firstTrackNum;   }

		function &getTracks() {
			return $this->tracks;
		}

		function addTrack( array $track ) {
			$this->tracks[] = $track;
		}

		function readDisc( $deviceName = '' ) {
			throw new mbDiscError( "Cannot readDisc()", 1 );
		}

		function getSubmissionUrl( Disc $disc, $host='mm.musicbrainz.org', $port=80 ) {
			if ( $port == 80 )
			  $netloc = $host;
			else
			  $netloc = $host . ':' . $port;

			$toc = $disc->getFirstTrackNum() . '+' . $disc->getLastTrackNum() . '+' . $disc->getSectors();

			foreach ( $disc->getTracks() as $track )
			  $toc .= '+' . $track[0];

			return "http://" . $netloc . "/bare/cdlookup.html?id=" . $disc->getId() . "&toc=" . $toc .
				   "&tracks=" . $disc->getLastTrackNum();
		}
	}
?>
