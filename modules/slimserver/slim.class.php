<?php
     
    /**
    *
    * Slimp3-Class
    * for querying the slimp3 and the squeezebox players
    *
    * feel free to modify and use it whereever you want.
    * would be nice if you could send me your changes,
    *
    * Homepage:
    * http://trendwhores.de/slimclass.php
    *
    * Tobias Schlottke <tschlottke chr(64) virtualminds chr(46) de>
    * http://www.trendwhores.de
    *
    * Modifications by Andreas <php  chr(64) simply  chr(46) nu>
    * + Added more options to slimp3()
    * + Added playlist() /w related options
    * + Modified display() to get strings with spaces instead of +'s provided by urlencode
    * + Modified _parse() to handle new options. Quick'n'dirty hack, could probably be prettier!
    *
    * License: GPL
    *
    */
     
    class slim {
         
        var $host = "localhost";
        var $port = 9090;
         
        var $_connection;
         
        var $playerindex;
        var $playercount;
         
         
        function slim($host = NULL, $port = 9090) {
             
            if ($host && $port) {
                $this->host = $host;
                $this->port = $port;
            }
             
            if (!$this->_connection = fsockopen($this->host, $this->port)) {
                return false;
            }
             
            $this->playercount = $this->_psend("player count ?");
            for($i = 0; $i < $this->playercount; $i++) {
                $this->playerindex[$i]['name'] = $this->_psend("player name $i ?");
                $this->playerindex[$i]['ip'] = $this->_psend("player ip $i ?");
                $this->playerindex[$i]['address'] = $this->_psend("player address $i ?");
                # Added some more options /andreas
                $this->playerindex[$i]['mode'] = $this->_psend($this->playerindex[$i]["address"] . " mode ?");
                $this->playerindex[$i]['power'] = $this->_psend($this->playerindex[$i]["address"] . " power ?");
                $this->playerindex[$i]['volume'] = $this->_psend($this->playerindex[$i]["address"] . " mixer volume ?");
                $this->playerindex[$i]['treble'] = $this->_psend($this->playerindex[$i]["address"] . " mixer treble ?");
                $this->playerindex[$i]['bass'] = $this->_psend($this->playerindex[$i]["address"] . " mixer bass ?");
                $this->playerindex[$i]['tracks'] = $this->_psend($this->playerindex[$i]["address"] . " info total songs ?");
                $this->playerindex[$i]['albums'] = $this->_psend($this->playerindex[$i]["address"] . " info total albums ?");
                $this->playerindex[$i]['artists'] = $this->_psend($this->playerindex[$i]["address"] . " info total artists ?");
                $this->playerindex[$i]['genres'] = $this->_psend($this->playerindex[$i]["address"] . " info total genres ?");
                 
            }
             
            return true;
        }
         
        function nowplaying($player = 0) {
            $song = array(
                "artist" => $this->_psend("artist $player ?"),
                "title" => $this->_psend("title $player ?"),
                "path" => $this->_psend("path $player ?"),
                "duration" => $this->_psend("duration $player ?"),
                "genre" => $this->_psend("genre $player ?"),
                "album" => $this->_psend("album $player ?")
            );
            return $song;
        }
         
        # Added playlist() for related options /andreas
        function playlist($player = 0) {
            #Information related to playlist!
            $index = $this->_psend("playlist index ?");
            $index++;
            $song = array(
                "index" => $index,
                "total" => $this->_psend("playlist tracks ?"),
                # Spaces added to the end of the two first below, quick'n'dirty fix for parsing error... ;)
                "nextartist" => $this->_psend("playlist artist " . $index . " "),
                "nexttitle" => $this->_psend("playlist title " . $index . " "),
                "shuffle" => $this->_psend("playlist shuffle ?"),
                "repeat" => $this->_psend("playlist repeat ?")
            );
            return $song;
        }
         
         
        function display($l1, $l2, $duration = 5, $player = 0) {
            #$this->_send("display ".urlencode($l1)." ".urlencode($l2)." ".$duration);
            # above code gave me urlencoded strings on my displat, ie "Hello%20World", below did not... /andreas (php  chr(64)  simply  chr(46) nu)
            $l1 = str_replace(" ", "%20", $l1);
            $l2 = str_replace(" ", "%20", $l2);
            $this->_send("display ".$l1." ".$l2." ".$duration);
        }
         
        function cdisplay() {
            return urldecode($this->_send("display ? ?"));
        }
         
        function close() {
            $this->_send("exit");
            return true;
        }
        # Modified by andreas (php  chr(64) simply  chr(46) nu)
        # Don't ask why I did stuff here, don't remember ;)
        function _parse($string, $cmd = NULL) {
             
            if (!$cmd);
            $cmd = $this->_lastcmd;
             
            $quoted = preg_quote(substr($cmd, 0, -1), "\\");
             
            if (preg_match("/^".$quoted."(.*)/i", $string, $matches)) {
                $dec = urldecode(trim($matches[1]));
                return $dec;
            } elseif(preg_match("/^".substr($quoted, 0, -2)."(.*)/i", $string, $matches)) {
                $dec = urldecode(trim($matches[1]));
                if (substr($dec, -1, 1) == '?')
                return substr($dec, 0, -1);
                else
                    return $dec;
                 
                # extra parsing for cmd's where MAC address is involved. Me not good at regexps so... ;)
            } elseif(preg_match("/^".$quoted."(.*)/i", urldecode(trim($string)), $matches)) {
                $dec = trim($matches[1]);
                if ($dec == "0")
                    return "play";
                else
                    return $dec;
            } else {
                return "unable to parse reply: ".$string."<br>(cmd was: $cmd)";
            }
        }
         
        function _send($string) {
             
            $this->_lastcmd = $string;
             
            if (fputs($this->_connection, $string."\n"))
            return fgets($this->_connection);
            else
                return false;
        }
         
        function _psend($string) {
            return $this->_parse($this->_send($string));
        }
         
    }
     
?>
