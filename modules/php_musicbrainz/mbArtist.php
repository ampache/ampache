<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
	class mbArtist extends MusicBrainzEntity {
		const TYPE_GROUP  = "http://musicbrainz.org/ns/mmd-1.0#Group";
		const TYPE_PERSON = "http://musicbrainz.org/ns/mmd-1.0#Person";

		private $type;
		private $name;
		private $sortName;
		private $disambiguation;
		private $beginDate;
		private $endDate;
		private $releases;
		private $releasesCount  = 0;
		private $releasesOffset = 0;
		private $aliases = array();

		function mbArtist( $id = '', $type = '', $name = '', $sortName = '' ) {
			parent::MusicBrainzEntity($id);
			$this->type = $type;
			$this->name = $name;
			$this->sortName = $sortName;
			$this->releases = array();
		}

		function getType() { return $this->type; }
		function setType( $type ) { $this->type = $type; }
		function getName() { return $this->name; }
		function setName( $name ) { $this->name = $name; }
		function getSortName() { return $this->sortName; }
		function setSortName( $sortName ) { $this->sortName = $sortName; }
		function getDisambiguation() { return $this->disambiguation; }
		function setDisambiguation( $disambiguation ) { $this->disambiguation = $disambiguation; }
		function getBeginDate() { return $this->beginDate; }
		function setBeginDate( $beginDate ) { $this->beginDate = $beginDate; }
		function getEndDate() { return $this->endDate; }
		function setEndDate( $endDate ) { $this->endDate = $endDate; }

		function getUniqueName() {
			return empty($this->disambiguation) ? $this->name :
				   $this->name . ' (' . $this->disambiguation . ')';
		}

		function &getReleases() {
			return $this->releases;
		}

		function addRelease( Release $release ) {
			$this->releases[] = $release;
		}

		function &getAliases() {
			return $this->aliases;
		}

		function addAlias( AristAlias $alias ) {
			$this->aliases[] = $alias;
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

		function getNumAliases() {
			return count($this->aliases);
		}

		function getAlias( $i ) {
			return $this->aliases[$i];
		}

		function imageURL( MusicBrainzQuery $q ) {
			$rf = new ReleaseFilter();
			$rf->artistId( $this->getId() )->limit(5)->asin('')->releaseType(Release::TYPE_ALBUM);

			try {
			  $rresults = $q->getReleases( $rf );
			} catch ( ResponseError $e ) {
				echo $e->getMessage() . " ";
				return "";
			}

			if ( empty( $rresults ) )
			  return "";

			$keys = array();
			foreach ( $rresults as $key => $rr ) {
				$rr = $rr->getRelease();
				if ( $rr->getAsin() && $rr->getAsin() != "" )
				   $keys[] = $key;
			}

			if ( sizeof($keys) > 0 ) {
			  $rand = rand(0,sizeof($keys)-1);
			  return "http://images.amazon.com/images/P/" . $rresults[$keys[$rand]]->getRelease()->getAsin() .
					 ".01._SCLZZZZZZZ_PU_PU-5_.jpg!,.-''-,.!" . $rresults[$keys[$rand]]->getRelease()->getTitle();
			}

			return "";
		}
	}
?>
