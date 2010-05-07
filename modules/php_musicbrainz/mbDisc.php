<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
class mbDiscError extends Exception { }

class mbDisc {
    private $id;
    private $sectors = 0;
    private $firstTrackNum = 0;
    private $lastTrackNum = 0;
    private $tracks;

    public function mbDisc($id = '') {
        $this->id = $id;
        $this->tracks = array();
    }

    public function setId           ($id   ) { $this->id = $id;               }
    public function getId           (      ) { return $this->id;              }
    public function setSectors      ($sectr) { $this->sectors = $sectr;       }
    public function getSectors      (      ) { return $this->sectors;         }
    public function setLastTrackNum ($track) { $this->lastTrackNum  = $track; }
    public function getLastTrackNum (      ) { return $this->lastTrackNum;    }
    public function setFirstTrackNum($track) { $this->firstTrackNum = $track; }
    public function getFirstTrackNum(      ) { return $this->firstTrackNum;   }

    public function getTracks() {
        return $this->tracks;
    }

    public function addTrack(array $track) {
        $this->tracks[] = $track;
    }

    public function readDisc($deviceName = '') {
        throw new mbDiscError("Cannot readDisc()", 1);
    }

    public function getSubmissionUrl(Disc $disc, $host='mm.musicbrainz.org', $port=80) {
        if ($port == 80)
          $netloc = $host;
        else
          $netloc = $host . ':' . $port;

        $toc = $disc->getFirstTrackNum() . '+' . $disc->getLastTrackNum() . '+' . $disc->getSectors();

        foreach ($disc->getTracks() as $track)
          $toc .= '+' . $track[0];

        return "http://" . $netloc . "/bare/cdlookup.html?id=" . $disc->getId() . "&toc=" . $toc .
               "&tracks=" . $disc->getLastTrackNum();
    }
}
?>
