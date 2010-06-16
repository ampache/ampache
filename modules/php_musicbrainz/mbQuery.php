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
define ('NS_MMD_1', "http://musicbrainz.org/ns/mmd-1.0#");

require_once('xml/xmlParser.php');

require_once('mbUtil.php');
require_once('mbRelation.php');
require_once('mbEntity.php');
require_once('mbArtistAlias.php');
require_once('mbArtist.php');
require_once('mbReleaseEvent.php');
require_once('mbRelease.php');
require_once('mbTrack.php');
require_once('mbDisc.php');
require_once('mbLabel.php');
require_once('mbLabelAlias.php');
require_once('mbTag.php');
require_once('mbRating.php');
require_once('mbResults.php');
require_once('mbMetadata.php');
require_once('mbFilter.php');
require_once('mbInclude.php');
require_once('mbWebService.php');
require_once('mbXmlParser.php');
require_once('mbFactory.php');

class mbRequestError extends Exception { }
class mbResponseError extends Exception { }

class MusicBrainzQuery {
    private $ws;
    private $ownWs = false;
    private $clientId;

    public function __construct(IWebService $ws=null, $clientId = '') {
        if ($ws != null)
            $this->ws = $ws;
        else {
            $this->ws = new mbWebService();
            $this->ownWs = true;
        }

        $this->clientId = $clientId;
    }

    public function getUserByName($name) {
        $metadata = $this->getFromWebService("user", "", null, mbUserFilter().name($name));
        $list = $metadata->getUserList(true);

        if (count($list) > 0) {
            return $list[0];
        }

        throw mbResponseError("response didn't contain user data");
    }

    public function getArtists(mbArtistFilter $artist_filters) {
        $metadata = $this->getFromWebService("artist", "", null, $artist_filters);
        return $metadata->getArtistResults2(true);
    }

    public function getReleases(mbReleaseFilter $release_filters) {
        $metadata = $this->getFromWebService("release", "", null, $release_filters);
        return $metadata->getReleaseResults2(true);
    }

    public function getTracks(mbTrackFilter $track_filters) {
        $metadata = $this->getFromWebService("track", "", null, $track_filters);
        return $metadata->getTrackResults2(true);
    }

    public function getArtistById($aID, mbArtistIncludes $artist_includes) {
        try {
            $id = extractUuid($aID);
        } catch (mbValueError $e) {
            throw new mbRequestError($e->getMessage(),$e->getCode());
        }
        $metadata = $this->getFromWebService("artist", $id, $artist_includes);
        $artist = $metadata->getArtist(true);
        return $artist;
    }

    public function getReleaseById($rID, mbReleaseIncludes $release_includes) {
        try {
            $id = extractUuid($rID);
        } catch (mbValueError $e) {
            throw new mbRequestError($e->getMessage(),$e->getCode());
        }
        $metadata = $this->getFromWebService("release", $id, $release_includes);
        $release = $metadata->getRelease(true);
        return $release;
    }

    public function getTrackById($tID, mbTrackIncludes $track_includes) {
        try {
            $id = extractUuid($tID);
        } catch (mbValueError $e) {
            throw new mbRequestError($e->getMessage(),$e->getCode());
        }
        $metadata = $this->getFromWebService("track", $id, $track_includes);
        $track = $metadata->getTrack(true);
        return $track;
    }

    public function getRating(mbRatingFilter $rating_filter) {
        $metadata = $this->getFromWebService('rating', '', null, $rating_filter);
		$rating = $metadata->getRating(true);
		return $rating;
    }

    protected function getFromWebService($entity, $id, $includes=null, $filters=null) {
        $includeList = $includes ? $includes->createIncludeTags() : null;
        $filterList  = $filters  ? $filters->createParameters()  : null;
        $content = $this->ws->get($entity, $id, $includeList, $filterList);

        try {
            $parser = new mbXmlParser();
            $parsed_content = $parser->parse($content);
            return $parsed_content;
        } catch (mbParseError $e) {
            throw new mbResponseError($e->getMessage(), $e->getCode());
        }
    }

	/**
	 * submitUserRating
	 */
	public function submitUserRating($entityURI, $rating) {
		$mbid = extractUuid($entityURI);
		$entity = extractEntityType($entityURI);

		$params['id'] = $mbid;
		$params['entity'] = $entity;
		$params['rating'] = $rating;

		$this->ws->post('rating', '', $params);
	}

	/**
	 * submitUserTags
	 */
	public function submitUserTags($entityURI, $tags) {
		$mbid = extractUuid($entityURI);
		$entity = extractEntityType($entityURI);

		$params['id'] = $mbid;
		$params['entity'] = $entity;
		$params['tags'] = implode(',', $tags);

		$this->ws->post('tag', '', $params);
	}

    public function submitPuids(array $tracks2puids) {
        if (empty($this->clientId)) {
            throw WebServiceError("Please supply a client ID");
        }
        $params = array(
            array('client', $this->clientId)
       );
        foreach ($tracks2puids as $puid => $track) {
            $params[] = array('puid', extractUuid($puid).' '.$track);
        }
        $this->ws->post("track", "", $params);
    }
}
?>
