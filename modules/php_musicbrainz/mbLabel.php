<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
	class mbLabel extends MusicBrainzEntity {
		const TYPE_GROUP  = "http://musicbrainz.org/ns/mmd-1.0#Group";
		const TYPE_PERSON = "http://musicbrainz.org/ns/mmd-1.0#Person";

		private $type;
		private $name;
		private $sortName;
		private $disambiguation;
		private $beginDate;
		private $endDate;
		private $releases;
		private $releasesCount;
		private $releasesOffset;
		private $aliases;

		function mbLabel( $id='', $type='', $name='', $sortName='' ) {
			parent::MusicBrainzEntity($id);
			$this->type = $type;
			$this->name = $name;
			$this->sortName = $sortName;
		}

		function setType( $type ) { $this->type = $type; }
		function getType() { return $this->type; }
		function setName( $name ) { $this->name = $name; }
		function getName() { return $this->name; }
		function setSortName( $sortName ) { $this->sortName = $sortName; }
		function getSortName() { return $this->sortName; }
		function setDisambiguation( $disambiguation ) { $this->disambiguation = $disambiguation; }
		function getDisambiguation() { return $this->disambiguation; }
		function setBeginDate( $beginDate ) { $this->beginDate = $beginDate; }
		function getBeginDate() { return $this->beginDate; }
		function setEndDate( $endDate ) { $this->endDate = $endDate; }
		function getEndDate() { return $this->endDate; }

		function getUniqueName() {
			return empty($this->disambiguation) ? $this->name :
				   $this->name . ' (' . $this->disambiguation . ')';
		}

		function &getReleases() {
			return $this->releases;
		}

		function addRelease( mbRelease $release ) {
			$this->releases[] = $release;
		}

		function &getAliases() {
			return $this->aliases;
		}

		function addAlias( mbLabelAlias $alias ) {
			$this->aliases[] = $alias;
		}

		function getNumReleases() {
			return count($this->releases);
		}

		function &getRelease( $i ) {
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

		function &getAlias( $i ) {
			return $this->aliases[$i];
		}
	}
?>
