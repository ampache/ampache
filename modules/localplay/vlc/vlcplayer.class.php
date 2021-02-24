<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * VlcPlayer Class
 *
 * This player controls an instance of VLC webinterface
 * which in turn controls VLC. All functions
 * return null on failure.
 *
 */
class VlcPlayer
{
    public $host;
    public $port;
    public $password;

    /**
     * VlcPlayer
     * This is the constructor, it defaults to localhost
     * with port 8080
     * i would change this to another value then standard 8080, it gets used by more things
     * @param string $host
     * @param string $password
     * @param integer $port
     */
    public function __construct($host = 'localhost', $password = '', $port = 8080)
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->password = $password;
    } // VlcPlayer

    /**
     * add
     * append a song to the playlist
     * $name    Name to be shown in the playlist
     * $url        URL of the song
     * @param string $name
     * @param $url
     * @return boolean
     */
    public function add($name, $url)
    {
        $aurl = urlencode($url);
        $aurl .= "&";
        $aurl .= urlencode($name);

        $args    = array('command' => 'in_enqueue', '&input' => $aurl);
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return false;
        }

        return true;
    } // add

    /**
     * version
     * No version returned in the standard xml file, just need to check for xml returned
     */
    public function version()
    {
        $args    = array();
        $results = $this->sendCommand('status.xml', $args);
        if ($results === null) {
            return null;
        }

        return true;
    } // version

    /**
     * clear
     * clear the playlist
     * Every command returns status.xml no other way
     */
    public function clear()
    {
        $args    = array('command' => 'pl_empty');
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return null;
        }

        return true;
    } // clear

    /**
     * next
     * go to next song
     */
    public function next()
    {
        $args    = array('command' => 'pl_next');
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return null;
        }

        return true;
    } // next

    /**
     * prev
     * go to previous song
     */
    public function prev()
    {
        $args    = array('command' => 'pl_previous');
        $results = $this->sendCommand("status.xml?", $args);
        if ($results === null) {
            return null;
        }

        return true;
    } // prev

    /**
     * skip
     * This skips to POS in the playlist
     * @param $pos
     * @return boolean|null
     */
    public function skip($pos)
    {
        $args    = array('command' => 'pl_play', '&id' => $pos);
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return null;
        }

        // Works but if user clicks next afterwards player goes to first song our last song played before

        return true;
    } // skip

    /**
     * play
     * play the current song
     */
    public function play()
    {
        $args    = array('command' => 'pl_play');
        $results = $this->sendCommand("status.xml?", $args);
        if ($results === null) {
            return null;
        }

        return true;
    } // play

    /**
     * pause
     * toggle pause mode on current song
     */
    public function pause()
    {
        $args    = array('command' => 'pl_pause');
        $results = $this->sendCommand("status.xml?", $args);
        if ($results === null) {
            return null;
        }

        return true;
    } // pause

    /**
     * stop
     * stops the current song amazing!
     */
    public function stop()
    {
        $args    = array('command' => 'pl_stop');
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return null;
        }

        return true;
    } // stop

    /**
     * repeat
     * This toggles the repeat state of VLC
     * @param $value
     * @return boolean|null
     */
    public function repeat($value)
    {
        $args    = array('command' => 'pl_repeat');
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return null;
        }

        return true;
    } // repeat

    /**
     * random
     * this toggles the random state of VLC
     * @param $value
     * @return boolean
     */
    public function random($value)
    {
        $args    = array('command' => 'pl_random');
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return false;
        }

        return true;
    } // random

    /**
     * delete_pos
     * This deletes a specific track
     * @param $track
     * @return boolean
     */
    public function delete_pos($track)
    {
        $args    = array('command' => 'pl_delete', '&id' => $track);
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return false;
        }

        return true;
    } // delete_pos

    /**
     * state
     * This returns the current state of the VLC player
     */
    public function state()
    {
        $args = array();

        $state       = 'unknown';
        $results     = $this->sendCommand('status.xml', $args);
        $currentstat = $results['root']['state']['value'];

        if ($currentstat == 'playing') {
            $state = 'play';
        }
        if ($currentstat == 'stop') {
            $state = 'stop';
        }
        if ($currentstat == 'paused') {
            $state = 'pause';
        }

        return $state;
    } // state

    /**
     * extract the full state from the xml file and send to status in vlccontroller for further parsing.
     *
     */
    public function fullstate()
    {
        $args = array();

        $results = $this->sendCommand('status.xml', $args);
        if ($results === null) {
            return false;
        }

        return $results;
    } // fullstate


    /**
     * volume_up
     * This increases the volume of VLC , set to +20 can be changed to your preference
     */
    public function volume_up()
    {
        $args    = array('command' => 'volume', '&val' => '%2B20');
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return false;
        }

        return true;
    } // volume_up

    /**
     * volume_down
     * This decreases the volume of VLC, can be set to your preference
     */
    public function volume_down()
    {
        $args    = array('command' => 'volume', '&val' => '-20');
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return false;
        }

        return true;
    } // volume_down

    /**
     * set_volume
     * This sets the volume as best it can, i think it's from 0 to 400, need more testing'
     * @param $value
     * @return boolean
     */
    public function set_volume($value)
    {

        // Convert it to base 400
        $value   = $value * 4;
        $args    = array('command' => 'volume', '&val' => $value);
        $results = $this->sendCommand('status.xml?', $args);
        if ($results === null) {
            return false;
        }

        return true;
    } // set_volume

    /**
     * clear_playlist
     * this flushes the playlist cache (I hope this means clear)
     */
    public function clear_playlist()
    {
        $args    = array('command' => 'pl_empty');
        $results = $this->sendcommand('status.xml?', $args);
        if ($results === null) {
            return false;
        }

        return true;
    } // clear_playlist

    /**
    * get_tracks
    * This returns a delimiated string of all of the filenames
    * current in your playlist, only url's at the moment,normal files put in the playlist with VLC wil not show'
    */
    public function get_tracks()
    {

        // Gets complete playlist + medialib in VLC's case, needs to be looked at
        $args = array();

        $results = $this->sendCommand('playlist.xml', $args);
        if ($results === null) {
            return false;
        }

        return $results;
    } // get_tracks

    /**
     * sendCommand
     * This is the core of this library it takes care of sending the HTTP
     * request to the VLC server and getting the response
     * @param $cmd
     * @param $args
     * @return array|void|null
     */
    private function sendCommand($cmd, $args)
    {
        $fsock = fsockopen($this->host, (int) $this->port, $errno, $errstr);

        if (!$fsock) {
            debug_event(self::class, "VLCPlayer: $errstr ($errno)", 1);

            return null;
        }

        // Define the base message
        $msg = "GET /requests/$cmd";

        // Foreach our arguments
        foreach ($args as $key => $val) {
            $msg .= "$key=$val";
        }

        $msg .= " HTTP/1.0\r\n";

        // Basic authentication
        if (!empty($this->password)) {
            $b64pwd = base64_encode(':' . $this->password);
            $msg .= "Authorization: Basic " . $b64pwd . "\r\n";
        }

        $msg .= "\r\n";

        fputs($fsock, $msg);
        $data   = '';
        $header = '';
        // here the header is split from the xml to avoid problems
        do {
            // loop until the end of the header

            $header .= fgets($fsock);
        } while (strpos($header, "\r\n\r\n") === false);

        // now put the body in variable $data
        while (! feof($fsock)) {
            $data .= fgets($fsock);
        }

        fclose($fsock);

        // send to xml parser and make an array
        return $this->xmltoarray($data);
    } // sendCommand

    /**
     * xmltoarray
     * this function parses the xml page into an array thx to bin-co
     * warning VLC returns it's complete media lib if asked for playlist
     * @param $contents
     * @param integer $get_attributes
     * @param string $priority
     * @return array|void
     */
    private function xmltoarray($contents, $get_attributes = 1, $priority = 'attribute')
    {
        if (!$contents) {
            return array();
        }

        if (!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }

        // Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);

        if (!$xml_values) {
            return;
        } // Hmm...

        // Initializations
        $bigxml_array = array();

        $current = &$bigxml_array; // Reference

        // Go through the tags.
        // Multiple tags with same name will be turned into an array
        $repeated_tag_index = array();
        foreach ($xml_values as $data) {
            // Remove existing values, or there will be trouble
            unset($attributes, $value);

            // This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data); // We could use the array by itself, but this cooler.

            $result          = array();
            $attributes_data = array();

            if (isset($value)) {
                if ($priority == 'tag') {
                    $result = $value;
                } else {
                    $result['value'] = $value;
                } // Put the value in a assoc array if we are in the 'Attribute' mode
            }

            // Set the attributes too.
            if (isset($attributes) && $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag') {
                        $attributes_data[$attr] = $val;
                    } else {
                        $result['attr'][$attr] = $val;
                    } // Set all the attributes in a array called 'attr'
                }
            }

            // See tag status and do the needed.
            if ($type == "open") {
                // The starting of the tag '<tag>'
                $parent[$level - 1] = &$current;
                // Insert New tag
                if (!is_array($current) || (!in_array($tag, array_keys($current)))) {
                    $current[$tag] = $result;
                    if ($attributes_data) {
                        $current[$tag . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level] = 1;

                    $current = &$current[$tag];
                } else {
                    // There was another element with the same tag name
                    if (isset($current[$tag][0])) {
                        // If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level]++;
                    } else {
                        // This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag]                           = array($current[$tag], $result); // This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag . '_' . $level] = 2;

                        if (isset($current[$tag . '_attr'])) {
                            // The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current         = &$current[$tag][$last_item_index];
                }
            } elseif ($type == "complete") {
                // Tags that ends in 1 line '<tag />'
                // See if the key is already taken.
                if (!isset($current[$tag])) {
                    //New Key
                    $current[$tag]                           = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' && $attributes_data) {
                        $current[$tag . '_attr'] = $attributes_data;
                    }
                } else {
                    // If taken, put all things inside a list(array)
                    if (isset($current[$tag][0]) && is_array($current[$tag])) {
                        // If it is already an array push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;

                        if ($priority == 'tag' && $get_attributes && $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level]++;
                    } else {
                        // If it is not an array...
                        $current[$tag]                           = array($current[$tag], $result); // ...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' && $get_attributes) {
                            if (isset($current[$tag . '_attr'])) {
                                // The attribute of the last(0th) tag must be moved as well
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }

                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level]++; // 0 and 1 index is already taken
                    }
                }
            } elseif ($type == 'close') {
                // End of tag '</tag>'
                $current = &$parent[$level - 1];
            }
        }

        return($bigxml_array);
    }   // xml parser
} // end vlcplayer.class
