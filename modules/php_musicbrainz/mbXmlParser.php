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
class mbParseError extends Exception { }

class mbXmlParser {
    private $xml_parser;
    private $factory;

    public function __construct() {
        $this->xml_parser = new xmlParser();
        $this->factory    = new mbDefaultFactory();
    }

    private function parseList(XMLNode $node, array $list, $func) {
        for ($i = 0; $i < $node->nChildNodes(); $i++) {
            $cnode = $node->getChildNode($i);
            $list[] = call_user_func(array($this, $func), $cnode);
        }
    }

    private function parseResults(XMLNode $node, array $list, $func, $type) {
        for ($i = 0; $i < $node->nChildNodes(); $i++) {
            $cnode = $node->getChildNode($i);
            $object = call_user_func(array($this, $func), $cnode);
            $score = $cnode->getAttribute("ext:score");
            eval('$to_add = new ' . $type . 'Result($object,$score);');
            $to_add->setCount($node->getAttribute("count"));
            $to_add->setOffset($node->getAttribute("offset"));
            $list[] = $to_add;
        }
    }

    private function parseRelations(XMLNode $node, MusicBrainzEntity $entity) {
        $targetType = $node->getAttribute("target-type");
        if ($targetType == '')
          return;

        for ($i = 0; $i < $node->nChildNodes(); $i++) {
            $cnode = $node->getChildNode($i);
            if (strtolower($cnode->getName()) == "relation") {
                $relation = $this->createRelation($cnode, $targetType);
                if ($relation)
                  $entity->addRelation($relation);
            }
        }
    }

    private function parseUserResults(XMLNode $node, array $userList) {
        for ($i = 0; $i < $node->nChildNodes(); $i++) {
            $cnode = $node->getChildNode($i);
            $userList[] = $this->createUser($cnode);
        }
    }

    private function createUser(XMLNode $node) {
        $user = $this->factory->newUser();
        for ($i = 0; $i < $node->nChildNodes(); $i++) {
            $cNode = $node->getChildNode($i);
            $name = $cNode->getName();
            switch ($name) {
                case "name":
                 $user->setName($cNode->getText());
                 break;
                case "ext:nag":
                 $user->setShowNag($node->getAttribute("show") == 'true' ? true : false);
                 break;
            }
        }
        return $user;
    }

    private function createArtistAlias(XMLNode $node) {
        $aa = $this->factory->newArtistAlias();
        $aa->setType($node->getAttribute("type"));
        $aa->setScript($node->getAttribute("script"));
        $aa->setValue($node->getText());
        return $aa;

    }

    private function createTag(XMLNode $node) {
        $tag = $this->factory->newTag();
        $tag->setCount($node->getAttribute("count"));
        $tag->setName($node->getText());
        return $tag;
    }

    private function createLabelAlias(XML $node) {
        $la = $this->factory->newLabelAlias();
        $la->setType($node->getAttribute("type"));
        $la->setScript($node->getAttribute("script"));
        $la->setValue($node->getText());
        return $la;
    }

    private function createDisc(XMLNode $node) {
        $disc = $this->factory->newDisc();
        $disc->setId($node->getAttribute("id"));
        return $disc;
    }

    private function createReleaseEvent(XMLNode $node) {
        $relEvent = $this->factory->newReleaseEvent();
        $relEvent->setCountry($node->getAttribute("country"));
        $relEvent->setDate($node->getAttribute("date"));
        $relEvent->setCatalogNumber($node->getAttribute("catalog-number"));
        $relEvent->setBarcode($node->getAttribute("barcode"));
        for ($i = 0; $i < $node->nChildNodes(); $i++) {
            $cnode = $node->getChildNode($i);
            switch (strtolower($cnode->getName())) {
                case 'label':
                    $relEvent->setLabel($this->createLabel($cnode));
                break;
            }
        }
        return $relEvent;
    }

    private function createRating(XMLNode $node) {
        $rating = $this->factory->newRating();
        $rating->setRating($node->getText());
        return $rating;
    }

    private function createLabel(XMLNode $node) {
        $label = $this->factory->newLabel();
        $label->setId($node->getAttribute("id"));
        $label->setType($node->getAttribute("type"));
        for ($i = 0; $i < $node->nChildNodes(); $i++) {
            $cnode = $node->getChildNode($i);
            switch (strtolower($cnode->getName())) {
                case "name":
                case "sort-name":
                    $label->setSortName($cnode->getText());
                break;
                case "disambiguation":
                    $label->setDisambiguation($cnode->getText());
                break;
                case "life-span":
                    $begin = $cnode->getAttribute("begin");
                    $end   = $cnode->getAttribute("end");
                    if ($begin != "") {
                        $label->setBeginDate($begin);
                    }
                    if ($end != "") {
                        $label->setEndDate($end);
                    }
                break;
                case "alias-list":
                    $pAlias = $label->getAliases();
                    $this->parseList($cnode, $pAlias, 'createAlias');
                break;
                case "release-list":
                    $pRel = $label->getReleases();
                    $label->setReleasesCount($cnode->getAttribute("count"));
                    $label->setReleasesOffset($cnode->getAttribute("offset"));
                    $this->parseList($cnode, $pRel, 'createRelease');
                break;
                case "relation-list":
                    $this->parseRelations($cnode, $label);
                break;
                case "tag-list":
                    $pTag = $label->getTags();
                    $this->parseList($cnode, $pTag, 'createTag');
                break;
            }
        }

        return $label;
    }

    private function createArtist(XMLNode $node) {
        $artist = $this->factory->newArtist();
        $artist->setId($node->getAttribute("id"));
        $artist->setType($node->getAttribute("type"));
        for ($i = 0; $i < $node->nChildNodes(); $i++) {
            $cnode = $node->getChildNode($i);
            switch (strtolower($cnode->getName())) {
                case "name":
                    $artist->setName($cnode->getText());
                break;
                case "sort-name":
                    $artist->setSortName($cnode->getText());
                break;
                case "disambiguation":
                    $artist->setDisambiguation($cnode->getText());
                break;
                case "life-span":
                    $begin = $cnode->getAttribute("begin");
                    $end   = $cnode->getAttribute("end");
                    if ($begin != "") {
                        $artist->setBeginDate($begin);
                    }
                    if ($end != "") {
                        $artist->setEndDate($end);
                    }
                break;
                case "alias-list":
                    $pAli = $artist->getAliases();
                    $this->parseList($cnode, $pAli, 'createArtistAlias');
                break;
                case "release-list":
                    $pRel = $artist->getReleases();
                    $artist->setReleasesCount($cnode->getAttribute("count"));
                    $artist->setReleasesOffset($cnode->getAttribute("offset"));
                    $this->parseList($cnode, $pRel, 'createRelease');
                break;
                case "relation-list":
                    $this->parseRelations($cnode, $artist);
                break;
                case "tag-list":
                    $pTag = $artist->getTags();
                    $this->parseList($cnode, $pTag, 'createTag');
                break;
            }
        }

        return $artist;
    }

    private function createTrack(XMLNode $node) {
        $track = $this->factory->newTrack();
        $track->setId($node->getAttribute("id"));
        for ($i = 0; $i < $node->nChildNodes(); $i++) {
            $cnode = $node->getChildNode($i);
            switch(strtolower($cnode->getName())) {
                case "title":
                    $track->setTitle($cnode->getText());
                break;
                case "artist":
                    $track->setArtist($this->createArtist($cnode));
                break;
                case "duration":
                    $track->setDuration($cnode->getText());
                break;
                case "release-list":
                    $pRel = $track->getReleases();
                    $track->setReleasesOffset($cnode->getAttribute("offset"));
                    $track->setReleasesCount($cnode->getAttribute("count"));
                    $this->parseList($cnode, $pRel, 'createRelease');
                break;
                case "relation-list":
                    $this->parseRelations($cnode, $track);
                break;
                case "tag-list":
                    $pTag = $track->getTags();
                    $this->parseList($cnode, $pTag, 'createTag');
                break;
            }
        }

        return $track;
    }

    private function createRelease(XMLNode $node) {
        $release = $this->factory->newRelease();
        $release->setId($node->getAttribute("id"));
        $array = array();
        $types = $node->getAttribute("type");
        $types = explode(' ', $types);
        foreach ($types as $one_type) {
            $array[] = $one_type;
        }
        $release->setTypes($array);

        for ($i = 0; $i < $node->nChildNodes(); $i++) {
            $cnode = $node->getChildNode($i);
            switch (strtolower($cnode->getName())) {
                case "title":
                    $release->setTitle($cnode->getText());
                break;
                case "text-representation":
                    $release->setTextLanguage($cnode->getAttribute("language"));
                    $release->setTextScript($cnode->getAttribute("script"));
                break;
                case "asin":
                    $release->setAsin($cnode->getText());
                break;
                case "artist":
                    $release->setArtist($this->createArtist($cnode));
                break;
                case "release-event-list":
                    $pRelEv = $release->getReleaseEvents();
                    $this->parseList($cnode, $pRelEv, 'createReleaseEvent');
                break;
                case "disc-list":
                    $pDisc = $release->getDiscs();
                    $this->parseList($cnode, $pDisc, 'createDisc');
                break;
                case "track-list":
                    $pTrack = $release->getTracks();
                    $release->setTracksOffset($cnode->getAttribute("offset"));
                    $release->setTracksCount($cnode->getAttribute("count"));
                    $this->parseList($cnode, $pTrack, 'createTrack');
                break;
                case "relation-list":
                    $this->parseRelations($cnode, $release);
                break;
                case "tag-list":
                    $pTag = $release->getTags();
                    $this->parseList($cnode, $pTag, 'createTag');
                break;
            }
        }

        return $release;
    }

    private function createRelation(XMLNode $node, $type) {
        $relation = $this->factory->newRelation();
        $relation->setType(extractFragment($node->getAttribute("type"))); // TODO: fixme
        $relation->setTargetType($type);
        $relation->setTargetId($node->getAttribute("target"));

        $dir = mbRelation::DIR_BOTH;
        switch(strtolower($node->getAttribute("direction"))) {
            case "forward":
                $dir = mbRelation::DIR_FORWARD;
            break;
            case "backward":
                $dir = mbRelation::DIR_BACKWARD;
            break;
        }
        $relation->setDirection($dir);

        $relation->setBeginDate($node->getAttribute("begin"));
        $relation->setEndDate($node->getAttribute("end"));

        // TODO: ns
        $attrs = $node->getAttribute("attributes");
        $attributes = explode(' ', $attrs);
        foreach ($attributes as $attr)
          $relation->addAttribute($attr);

        $target = null;
        if ($node->nChildNodes() > 0 ) {
            $cnode = $node->getChildNode(0);
            switch (strtolower($cnode->getName())) {
                case "artist":
                    $target = $this->createArtist($cnode);
                break;
                case "release":
                    $target = $this->createRelease($cnode);
                break;
                case "track":
                    $target = $this->createTrack($cnode);
                break;
            }
        }
        $relation->setTarget($target);

        return $relation;
    }

    public function parse($data) {
        $nodes = $this->xml_parser->parse($data);

        if ($nodes == false) {
            throw new mbParseError($this->xml_parser->lastError(), 1);
        }

        $md = new mbMetadata();

        for ($i = 0; $i < $nodes->nChildNodes(); $i++) {
            $node = $nodes->getChildNode($i);
            $name = strtolower($node->getName());

            switch ($name) {
                case "artist":
                    $md->setArtist($this->createArtist($node));
                break;
                case "track":
                    $md->setTrack($this->createTrack($node));
                break;
                case "release":
                    $md->setRelease($this->createRelease($node));
                break;
                case "label":
                    $md->setLabel($this->createLabel($node));
                break;
                case "user-rating":
                    $md->setRating($this->createRating($node));
                break;
                case "artist-list":
                    $pArt = $md->getArtistResults();
                    $this->parseResults($node, $pArt, 'createArtist', 'Artist');
                break;
                case "track-list":
                    $pTrack = $md->getTrackResults();
                    $this->parseResults($node, $pTrack, 'createTrack', 'Track');
                break;
                case "release-list":
                    $pRel = $md->getReleaseResults();
                    $this->parseResults($node, $pRel, 'createRelease', 'Release');
                break;
                case "ext:user-list":
                    $list = $md->getUserList();
                    $this->parseUserResults($node, $list);
                break;
            }
        }
        return $md;
    }
}
?>
