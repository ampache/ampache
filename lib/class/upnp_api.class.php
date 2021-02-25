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
 * This class is a derived work from UMSP project (http://wiki.wdlxtv.com/UMSP).
 */

/**
 * UPnP Class
 *
 * This class wrap Ampache to UPnP API functions.
 * These are all static calls.
 *
 */
class Upnp_Api
{
    /**
     * UPnP classes:
     * object.item.audioItem
     * object.item.imageItem
     * object.item.videoItem
     * object.item.playlistItem
     * object.item.textItem
     * object.container
     */
    const SSDP_DEBUG = false;

    /**
     * constructor
     * This really isn't anything to do here, so it's private
     */
    private function __construct()
    {
    }

    /**
     * @return string
     */
    public static function get_uuidStr()
    {
        // Create uuid based on host
        $key     = 'ampache_' . AmpConfig::get('http_host');
        $hash    = hash('md5', $key);

        return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4) . '-' . substr($hash, 16, 4) . '-' . substr($hash, 20);
    }

    /* ================================== Begin SSDP functions ================================== */

    /**
     * @param string $buf
     * @param integer $delay
     * @param string $host
     * @param integer $port
     */
    private static function udpSend($buf, $delay = 15, $host = "239.255.255.250", $port = 1900)
    {
        usleep($delay * 1000); // we are supposed to delay before sending
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($host == "239.255.255.250") {  // when broadcast, set broadcast socket option
            socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        }
        socket_sendto($socket, $buf, strlen((string) $buf), 0, $host, $port);
        socket_close($socket);
    }

    /**
     * @param integer $delay
     * @param string $host
     * @param integer $port
     * @param string $prefix
     * @param boolean $alive
     */
    public static function sddpSend($delay = 15, $host = "239.255.255.250", $port = 1900, $prefix = "NT", $alive = true)
    {
        $strHeader  = 'NOTIFY * HTTP/1.1' . "\r\n";
        $strHeader .= 'HOST: ' . $host . ':' . $port . "\r\n";
        $strHeader .= 'LOCATION: http://' . gethostbyname(AmpConfig::get('http_host')) . ':' . AmpConfig::get('http_port') . AmpConfig::get('raw_web_path') . '/upnp/MediaServerServiceDesc.php' . "\r\n";
        $strHeader .= 'SERVER: DLNADOC/1.50 UPnP/1.0 Ampache/' . AmpConfig::get('version') . "\r\n";
        $strHeader .= 'CACHE-CONTROL: max-age=1800' . "\r\n";
        //$strHeader .= 'NTS: ssdp:alive' . "\r\n";
        if ($alive) {
            $strHeader .= 'NTS: ssdp:alive' . "\r\n";
        } else {
            $strHeader .= 'NTS: ssdp:byebye' . "\r\n";
            $delay = 2;
        }
        $uuidStr = self::get_uuidStr();

        $rootDevice = $prefix . ': upnp:rootdevice' . "\r\n";
        $rootDevice .= 'USN: uuid:' . $uuidStr . '::upnp:rootdevice' . "\r\n" . "\r\n";
        $buf = $strHeader . $rootDevice;
        self::udpSend($buf, $delay, $host, $port);

        $uuid = $prefix . ': uuid:' . $uuidStr . "\r\n";
        $uuid .= 'USN: uuid:' . $uuidStr . "\r\n" . "\r\n";
        $buf = $strHeader . $uuid;
        self::udpSend($buf, $delay, $host, $port);

        $deviceType = $prefix . ': urn:schemas-upnp-org:device:MediaServer:1' . "\r\n";
        $deviceType .= 'USN: uuid:' . $uuidStr . '::urn:schemas-upnp-org:device:MediaServer:1' . "\r\n" . "\r\n";
        $buf = $strHeader . $deviceType;
        self::udpSend($buf, $delay, $host, $port);

        $serviceCM = $prefix . ': urn:schemas-upnp-org:service:ConnectionManager:1' . "\r\n";
        $serviceCM .= 'USN: uuid:' . $uuidStr . '::urn:schemas-upnp-org:service:ConnectionManager:1' . "\r\n" . "\r\n";
        $buf = $strHeader . $serviceCM;
        self::udpSend($buf, $delay, $host, $port);

        $serviceCD = $prefix . ': urn:schemas-upnp-org:service:ContentDirectory:1' . "\r\n";
        $serviceCD .= 'USN: uuid:' . $uuidStr . '::urn:schemas-upnp-org:service:ContentDirectory:1' . "\r\n" . "\r\n";
        $buf = $strHeader . $serviceCD;
        self::udpSend($buf, $delay, $host, $port);
    }

    /**
     * @param $delaytime
     * @param $actst
     * @param $address
     * @throws Exception
     */
    public static function sendResponse($delaytime, $actst, $address)
    {
        $response  = 'HTTP/1.1 200 OK' . "\r\n";
        $response .= 'CACHE-CONTROL: max-age=1800' . "\r\n";
        $dt = new DateTime('UTC');
        $response .= 'DATE: ' . $dt->format('D, d M Y H:i:s \G\M\T') . "\r\n"; // RFC2616 date
        $response .= 'EXT:' . "\r\n";
        // Note that quite a few devices are unable to resolve a URL into an IP address. Therefore we have to use a
        // local IP address - resolve http_host into IP address
        $response .= 'LOCATION: http://' . gethostbyname(AmpConfig::get('http_host')) . ':' . AmpConfig::get('http_port') . AmpConfig::get('raw_web_path') . '/upnp/MediaServerServiceDesc.php' . "\r\n";
        $response .= 'SERVER: DLNADOC/1.50 UPnP/1.0 Ampache/' . AmpConfig::get('version') . "\r\n";
        $response .= 'ST: ' . $actst . "\r\n";
        $response .= 'USN: ' . 'uuid:' . self::get_uuidStr() . '::' . $actst . "\r\n";
        $response .= "\r\n";  // gupnp-universal-cp cannot see us without this line.

        if ($delaytime > 5) {
            $delaytime = 5;
        }
        $delay = random_int(15, $delaytime * 1000);   // Delay in ms

        $addr=explode(":", $address);
        if (self::SSDP_DEBUG) {
            debug_event(self::class, 'Sending response to: ' . $addr[0] . ':' . $addr[1] . PHP_EOL . $response, 5);
        }
        self::udpSend($response, $delay, $addr[0], (int) $addr[1]);
        if (self::SSDP_DEBUG) {
            debug_event(self::class, '(Sent)', 5);     // for timing
        }
    }

    /**
     * @param $unpacked
     * @param $remote
     */
    public static function notify_request($unpacked, $remote)
    {
        $headers = self::get_headers($unpacked);
        $str     = 'Notify ' . $remote . ' ' . $headers['nts'] . ' for ' . $headers['nt'];
        // We don't do anything with notifications except log them to check rx working
        if (self::SSDP_DEBUG) {
            debug_event(self::class, $str, 5);
        }
    }

    /**
     * @param $data
     * @return array
     */
    public static function get_headers($data)
    {
        $lines  = explode(PHP_EOL, $data);   // split into lines
        $keys   = array();
        $values = array();
        foreach ($lines as $line) {
            //$line = str_replace( ' ', '', $line );
            $line   = preg_replace('/[\x00-\x1F\x7F]/', '', $line);
            $tokens = explode(' ', $line);
            //echo 'BARELINE:'.$line.'&'.count($tokens).PHP_EOL;
            if (count($tokens) > 1) {
                $tokens[0] = str_replace(':', '', $tokens[0]); // remove ':' and convert to keys lowercase for match
                $tokens[0] = strtolower($tokens[0]);
                array_push($keys, $tokens[0]);
                $tokens[1] = str_replace("\"", '', $tokens[1]);
                array_push($values, $tokens[1]);
            }
        }

        return array_combine($keys, $values);
    }

    /**
     * @param $data
     * @param $address
     * @throws Exception
     */
    public static function discovery_request($data, $address)
    {
        // Process a discovery request.  The response must be sent to the address specified by $remote
        $headers = self::get_headers($data);
        if (self::SSDP_DEBUG) {
            debug_event(self::class, 'Discovery request from ' . $address, 5);
            debug_event(self::class, 'HEADERS:' . var_export($headers, true), 5);
        }

        $new_usn = 'uuid:' . self::get_uuidStr();
        $actst   = $headers['st'];
        //echo 'DELAYTIME: [' . $headers['mx'] . ']' . PHP_EOL;
        $delaytime = (int)($headers['mx']);
        if ($headers['man'] == 'ssdp:discover') {
            if ($headers['st'] == 'urn:schemas-upnp-org:device:MediaServer:1') {
                self::sendResponse($delaytime, $actst, $address);
            } elseif ($headers['st'] == 'urn:schemas-upnp-org:service:ConnectionManager:1') {
                self::sendResponse($delaytime, $actst, $address);
            } elseif ($headers['st'] == 'urn:schemas-upnp-org:service:ContentDirectory:1') {
                self::sendResponse($delaytime, $actst, $address);
            } elseif ($headers['st'] == 'upnp:rootdevice') {
                self::sendResponse($delaytime, $actst, $address);
            } elseif ($headers['st'] == $new_usn) {
                self::sendResponse($delaytime, $actst, $address);
            } elseif ($headers['st'] == 'ssdp:all') {
                #             echo 'discovery response for ssdp:all';
                self::sendResponse($delaytime, 'upnp:rootdevice', $address);
                self::sendResponse($delaytime, $new_usn, $address);
                self::sendResponse($delaytime, 'urn:schemas-upnp-org:device:MediaServer:1', $address);
                self::sendResponse($delaytime, 'urn:schemas-upnp-org:service:ConnectionManager:1', $address);
                self::sendResponse($delaytime, 'urn:schemas-upnp-org:service:ContentDirectory:1', $address);
                # And one that MiniDLNA advertises
                self::sendResponse($delaytime, 'urn:microsoft.com:service:X_MS_MediaReceiverRegistrar:1', $address);
            } else {
                if (self::SSDP_DEBUG) {
                    debug_event(self::class, 'ST header not for a service we provide [' . $actst . ']', 5);
                }
            }
        } else {
            if (self::SSDP_DEBUG) {
                debug_event(self::class, 'M-SEARCH MAN header not understood [' . $headers['man'] . ']', 5);
            }
        }
    }

    /* ================================== End SSDP functions ================================== */

    /**
     * @param $prmRequest
     * @return array
     */
    public static function parseUPnPRequest($prmRequest)
    {
        $retArr = array();
        $reader = new XMLReader();
        $result = XMLReader::XML($prmRequest);
        if (!$result) {
            debug_event(self::class, 'XML reader failed', 5);
        }

        while ($reader->read()) {
            debug_event(self::class, $reader->localName . ' ' . (string) $reader->nodeType . ' ' . (string) XMLReader::ELEMENT . ' ' . (string) $reader->isEmptyElement, 5);

            if (($reader->nodeType == XMLReader::ELEMENT)) {
                switch ($reader->localName) {
                    case 'Browse':
                        $retArr['action'] = 'browse';
                        break;
                    case 'Search':
                        $retArr['action'] = 'search';
                        break;
                    case 'GetSortCapabilities':
                        $retArr['action'] = 'sortcapabilities';
                        break;
                    case 'GetSearchCapabilities':
                        $retArr['action'] = 'searchcapabilities';
                        break;
                    case 'GetSystemUpdateID':
                        $retArr['action'] = 'systemupdateID';
                        break;
                    case 'ObjectID':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['objectid'] = $reader->value;
                        } // end if
                        break;
                    case 'BrowseFlag':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['browseflag'] = $reader->value;
                        } // end if
                        break;
                    case 'Filter':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['filter'] = $reader->value;
                        } // end if
                        break;
                    case 'StartingIndex':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['startingindex'] = $reader->value;
                        } // end if
                        break;
                    case 'RequestedCount':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['requestedcount'] = $reader->value;
                        } // end if
                        break;
                    case 'SearchCriteria':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['searchcriteria'] = $reader->value;
                        } // end if
                        break;
                    case 'SortCriteria':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['sortcriteria'] = $reader->value;
                        } // end if
                        break;
                } // end switch
            } // end if
        } // end while

        return $retArr;
    } // end function

    /**
     * @param $filterValue
     * @param $keyisRes
     * @param $keytoCheck
     * Checks whether key is in filter string, taking account of allowable filter wildcards and null strings
     * @return bool
     */
    public static function isinFilter($filterValue, $keyisRes, $keytoCheck)
    {
        if ($filterValue == null || $filterValue == '') {
            return true;
        }
        if ($filterValue == "*") {
            // genuine wildcard
            return true;
        }
        if ($keyisRes) {
            $testKey = 'res@' . $keytoCheck;
        } else {
            $testKey = $keytoCheck;
        }
        $filt = explode(',', $filterValue);      // do exact word match rather than partial, which is what strpos does.
        //debug_event(self::class, 'checking '.$testKey.' in '.var_export($filt, true), 5);
        return in_array($testKey, $filt, true);  // this is necessary, (rather than strpos) because "res" turns up in many keys, whose results may not be wanted
    }

    /**
     * @param $prmItems
     * @param $filterValue
     * @return DOMDocument
     */
    public static function createDIDL($prmItems, $filterValue)
    {
        $xmlDoc               = new DOMDocument('1.0' /*, 'utf-8'*/);
        $xmlDoc->formatOutput = true;    // Note: other players don't seem to do this
        // Create root element and add namespaces:
        $ndDIDL = $xmlDoc->createElementNS('urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/', 'DIDL-Lite');
        $ndDIDL->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $ndDIDL->setAttribute('xmlns:upnp', 'urn:schemas-upnp-org:metadata-1-0/upnp/');
        $xmlDoc->appendChild($ndDIDL);

        // Return empty DIDL if no items present:
        if ((!isset($prmItems)) || (!is_array($prmItems))) {
            return $xmlDoc;
        }

        // sometimes here comes only one single item, not an array. Convert it to array. (TODO - UGLY)
        if ((count($prmItems) > 0) && (!is_array($prmItems[0]))) {
            $prmItems = array($prmItems);
        }

        // Add each item in $prmItems array to $ndDIDL:
        foreach ($prmItems as $item) {
            if (!is_array($item)) {
                debug_event(self::class, 'item is not array', 2);
                debug_event(self::class, $item, 5);
                continue;
            }

            if ($item['upnp:class'] == 'object.container' ||
                $item['upnp:class'] == 'object.container.album.musicAlbum' ||
                $item['upnp:class'] == 'object.container.person.musicArtist' ||
                $item['upnp:class'] == 'object.container.storageFolder') {
                $ndItem = $xmlDoc->createElement('container');
            } else {
                $ndItem = $xmlDoc->createElement('item');
            }
            $useRes     = false;
            $ndRes      = $xmlDoc->createElement('res');
            $ndRes_text = $xmlDoc->createTextNode($item['res']);
            $ndRes->appendChild($ndRes_text);

            /**
             * Add each element / attribute in $item array to item node:
             * Mini-substitution: & in value string needs to be double HTML escaped.
             * saving DIDL as XML will do this once.
             * Here we do it again (to match what MiniDLNA does)
             */
            foreach ($item as $key => $value) {
                // Handle attributes. Better solution?
                switch ($key) {
                    case 'id':
                        $ndItem->setAttribute('id', $value);
                        break;
                    case 'parentID':
                        $ndItem->setAttribute('parentID', $value);
                        break;
                    case 'childCount':
                        $ndItem->setAttribute('childCount', $value);
                        break;
                    case 'restricted':
                        $ndItem->setAttribute('restricted', $value);
                        break;
                    case 'searchable':
                        $ndItem->SetAttribute('searchable', $value);
                        break;
                    case 'res':
                        break;
                    case 'duration':
                        if (self::isinFilter($filterValue, true, $key)) {
                            $ndRes->setAttribute('duration', $value);
                            $useRes = true;
                        }
                        break;
                    case 'size':
                        if (self::isinFilter($filterValue, true, $key)) {
                            $ndRes->setAttribute('size', $value);
                            $useRes = true;
                        }
                        break;
                    case 'bitrate':
                        if (self::isinFilter($filterValue, true, $key)) {
                            $ndRes->setAttribute('bitrate', $value);
                            $useRes = true;
                        }
                        break;
                    case 'protocolInfo':
                        if (self::isinFilter($filterValue, true, $key)) {
                            $ndRes->setAttribute('protocolInfo', $value);
                            $useRes = true;
                        }
                        break;
                    case 'resolution':
                        if (self::isinFilter($filterValue, true, $key)) {
                            $ndRes->setAttribute('resolution', $value);
                            $useRes = true;
                        }
                        break;
                    case 'colorDepth':
                        if (self::isinFilter($filterValue, true, $key)) {
                            $ndRes->setAttribute('colorDepth', $value);
                            $useRes = true;
                        }
                        break;
                    case 'sampleFrequency':
                        if (self::isinFilter($filterValue, true, $key)) {
                            $ndRes->setAttribute('sampleFrequency', $value);
                            $useRes = true;
                        }
                        break;
                    case 'nrAudioChannels':
                        if (self::isinFilter($filterValue, true, $key)) {
                            $ndRes->setAttribute('nrAudioChannels', $value);
                            $useRes = true;
                        }
                        break;
                    default:
                        if (self::isinFilter($filterValue, false, $key)) {
                            $ndTag = $xmlDoc->createElement($key);
                            $ndItem->appendChild($ndTag);
                            // check if string is already utf-8 encoded
                            $xvalue     = str_replace("&", "&amp;", $value);
                            $ndTag_text = $xmlDoc->createTextNode((mb_detect_encoding($xvalue, 'auto') == 'UTF-8')?$xvalue:utf8_encode($xvalue));
                            $ndTag->appendChild($ndTag_text);
                        }
                }
                if ($useRes) {
                    $ndItem->appendChild($ndRes);
                }
            }
            $ndDIDL->appendChild($ndItem);
        }

        return $xmlDoc;
    }

    /**
     * @param $prmDIDL
     * @param $prmNumRet
     * @param $prmTotMatches
     * @param string $prmResponseType
     * @param string $prmUpdateID
     * @return DOMDocument
     */
    public static function createSOAPEnvelope($prmDIDL, $prmNumRet, $prmTotMatches, $prmResponseType = 'u:BrowseResponse', $prmUpdateID = '0')
    {
        /*
         * $prmDIDL is DIDL XML string
         * XML-Layout:
         *
         *        -s:Envelope
         *            -s:Body
         *                -u:BrowseResponse
         *                    Result (DIDL)
         *                    NumberReturned
         *                    TotalMatches
         *                    UpdateID
         */
        $doc               = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = true;
        $ndEnvelope        = $doc->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 's:Envelope');
        $ndEnvelope->setAttribute("s:encodingStyle", "http://schemas.xmlsoap.org/soap/encoding/");
        $doc->appendChild($ndEnvelope);
        $ndBody = $doc->createElement('s:Body');
        $ndEnvelope->appendChild($ndBody);
        $ndBrowseResp = $doc->createElementNS('urn:schemas-upnp-org:service:ContentDirectory:1', $prmResponseType);
        $ndBody->appendChild($ndBrowseResp);
        $ndResult = $doc->createElement('Result', $prmDIDL);
        $ndBrowseResp->appendChild($ndResult);
        $ndNumRet = $doc->createElement('NumberReturned', $prmNumRet);
        $ndBrowseResp->appendChild($ndNumRet);
        $ndTotMatches = $doc->createElement('TotalMatches', $prmTotMatches);
        $ndBrowseResp->appendChild($ndTotMatches);
        $ndUpdateID = $doc->createElement('UpdateID', $prmUpdateID); // seems to be ignored by the WDTVL
        //$ndUpdateID = $doc->createElement('UpdateID', (string) mt_rand(); // seems to be ignored by the WDTVL
        $ndBrowseResp->appendChild($ndUpdateID);

        return $doc;
    }

    /**
     * @param string $prmPath
     * @return array|null
     */
    public static function _musicMetadata($prmPath)
    {
        $root    = 'amp://music';
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }

        $meta = null;

        switch ($pathreq[0]) {
            case 'artists':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::count_server(false, 'artist');
                        $meta   = array(
                            'id' => $root . '/artists',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['artist'],
                            'dc:title' => T_('Artists'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $artist = new Artist($pathreq[1]);
                        if ($artist->id) {
                            $artist->format();
                            $meta = self::_itemArtist($artist, $root . '/artists');
                        }
                        break;
                }
                break;
            case 'albums':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::count_server(false, 'album');
                        $meta   = array(
                            'id' => $root . '/albums',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['album'],
                            'dc:title' => T_('Albums'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $album = new Album($pathreq[1]);
                        if ($album->id) {
                            $album->format();
                            $meta = self::_itemAlbum($album, $root . '/albums');
                        }
                        break;
                }
                break;
            case 'songs':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::count_server(false, 'song');
                        $meta   = array(
                            'id' => $root . '/songs',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['song'],
                            'dc:title' => T_('Songs'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $song = new Song($pathreq[1]);
                        if ($song->id) {
                            $song->format();
                            $meta = self::_itemSong($song, $root . '/songs');
                        }
                        break;
                }
                break;
            case 'playlists':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::count_server(false, 'playlist');
                        $meta   = array(
                            'id' => $root . '/playlists',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['playlist'],
                            'dc:title' => T_('Playlists'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $playlist = new Playlist($pathreq[1]);
                        if ($playlist->id) {
                            $playlist->format();
                            $meta = self::_itemPlaylist($playlist, $root . '/playlists');
                        }
                        break;
                }
                break;
            case 'smartplaylists':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::count_server(false, 'smartplaylist');
                        $meta   = array(
                            'id' => $root . '/smartplaylists',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['smartplaylist'],
                            'dc:title' => T_('Smart Playlists'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $playlist = new Search($pathreq[1]);
                        if ($playlist->id) {
                            $playlist->format();
                            $meta = self::_itemSmartPlaylist($playlist, $root . '/smartplaylists');
                        }
                        break;
                }
                break;
            case 'live_streams':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::count_server(false, 'live_stream');
                        $meta   = array(
                            'id' => $root . '/live_streams',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['live_stream'],
                            'dc:title' => T_('Radio Stations'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $radio = new Live_Stream($pathreq[1]);
                        if ($radio->id) {
                            $radio->format();
                            $meta = self::_itemLiveStream($radio, $root . '/live_streams');
                        }
                        break;
                }
                break;
            case 'podcasts':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::count_server(false, 'podcast');
                        $meta   = array(
                            'id' => $root . '/podcasts',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['podcast'],
                            'dc:title' => T_('Podcasts'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $podcast = new Podcast($pathreq[1]);
                        if ($podcast->id) {
                            $podcast->format();
                            $meta = self::_itemPodcast($podcast, $root . '/podcasts');
                        }
                        break;
                    case 3:
                        $episode = new Podcast_Episode($pathreq[2]);
                        if ($episode->id !== null) {
                            $episode->format();
                            $meta = self::_itemPodcastEpisode($episode, $root . '/podcasts/' . $pathreq[1]);
                        }
                        break;
                }
                break;
            default:
                $meta = array(
                    'id' => $root,
                    'parentID' => '0',
                    'restricted' => '1',
                    'searchable' => '1',
                    'childCount' => '5',
                    'dc:title' => T_('Music'),
                    'upnp:class' => 'object.container', //.storageFolder',
                    'upnp:storageUsed' => '-1',
                );
                break;
        }

        return $meta;
    }

    /**
     * @param $items
     * @param $start
     * @param $count
     * @return array
     */
    public static function _slice($items, $start, $count)
    {
        $maxCount = count($items);
        //debug_event(self::class, 'slice: ' . $maxCount . "   " . $start . "    " . $count, 5);

        return array($maxCount, array_slice($items, $start, ($count == 0 ? $maxCount - $start : $count)));
    }

    /**
     * @param $prmPath
     * @param $prmQuery
     * @param $start
     * @param $count
     * @return array
     */
    public static function _musicChilds($prmPath, $prmQuery, $start, $count)
    {
        $mediaItems = array();
        $maxCount   = 0;
        $queryData  = array();
        parse_str($prmQuery, $queryData);

        debug_event(self::class, 'MusicChilds: [' . $prmPath . '] [' . $prmQuery . ']' . '[' . $start . '] [' . $count . ']', 5);

        $parent  = 'amp://music' . $prmPath;
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }
        debug_event(self::class, 'MusicChilds4: [' . $pathreq[0] . ']', 5);

        switch ($pathreq[0]) {
            case 'artists':
                switch (count($pathreq)) {
                    case 1: // Get artists list
                        $artists                  = Catalog::get_artists(null, $count, $start);
                        $counts                   = Catalog::count_server(false, 'artist');
                        list($maxCount, $artists) = array($counts['artist'], $artists);
                        foreach ($artists as $artist) {
                            $artist->format();
                            $mediaItems[] = self::_itemArtist($artist, $parent);
                        }
                        break;
                    case 2: // Get artist's albums list
                        $artist = new Artist($pathreq[1]);
                        if ($artist->id) {
                            $album_ids                  = $artist->get_albums();
                            list($maxCount, $album_ids) = self::_slice($album_ids, $start, $count);
                            foreach ($album_ids as $album_id) {
                                $album = new Album($album_id);
                                $album->format();
                                $mediaItems[] = self::_itemAlbum($album, $parent);
                            }
                        }
                        break;
                }
                break;
            case 'albums':
                switch (count($pathreq)) {
                    case 1: // Get albums list
                        $album_ids                  = Catalog::get_albums($count, $start);
                        $counts                     = Catalog::count_server(false, 'album');
                        list($maxCount, $album_ids) = array($counts['album'], $album_ids);
                        foreach ($album_ids as $album_id) {
                            $album = new Album($album_id);
                            $album->format();
                            $mediaItems[] = self::_itemAlbum($album, $parent);
                        }
                        break;
                    case 2: // Get album's songs list
                        $album = new Album($pathreq[1]);
                        if ($album->id) {
                            $song_ids                  = $album->get_songs();
                            list($maxCount, $song_ids) = self::_slice($song_ids, $start, $count);
                            foreach ($song_ids as $song_id) {
                                $song = new Song($song_id);
                                $song->format();
                                $mediaItems[] = self::_itemSong($song, $parent);
                            }
                        }
                        break;
                }
                break;
            case 'songs':
                switch (count($pathreq)) {
                    case 1: // Get songs list
                        $catalogs = Catalog::get_catalogs();
                        foreach ($catalogs as $catalog_id) {
                            $catalog                = Catalog::create_from_id($catalog_id);
                            $songs                  = $catalog->get_songs();
                            list($maxCount, $songs) = self::_slice($songs, $start, $count);
                            foreach ($songs as $song) {
                                $song->format();
                                $mediaItems[] = self::_itemSong($song, $parent);
                            }
                        }
                        break;
                }
                break;
            case 'playlists':
                switch (count($pathreq)) {
                    case 1: // Get playlists list
                        $pl_ids                  = Playlist::get_playlists();
                        list($maxCount, $pl_ids) = self::_slice($pl_ids, $start, $count);
                        foreach ($pl_ids as $pl_id) {
                            $playlist = new Playlist($pl_id);
                            $playlist->format();
                            $mediaItems[] = self::_itemPlaylist($playlist, $parent);
                        }
                        break;
                    case 2: // Get playlist's songs list
                        $playlist = new Playlist($pathreq[1]);
                        if ($playlist->id) {
                            $items                  = $playlist->get_items();
                            list($maxCount, $items) = self::_slice($items, $start, $count);
                            foreach ($items as $item) {
                                if ($item['object_type'] == 'song') {
                                    $song = new Song($item['object_id']);
                                    $song->format();
                                    $mediaItems[] = self::_itemSong($song, $parent);
                                }
                            }
                        }
                        break;
                }
                break;
            case 'smartplaylists':
                switch (count($pathreq)) {
                    case 1: // Get playlists list
                        $pl_ids                  = Search::get_searches();
                        list($maxCount, $pl_ids) = self::_slice($pl_ids, $start, $count);
                        foreach ($pl_ids as $pl_id) {
                            $playlist = new Search($pl_id);
                            $playlist->format();
                            $mediaItems[] = self::_itemPlaylist($playlist, $parent);
                        }
                        break;
                    case 2: // Get playlist's songs list
                        $playlist = new Search($pathreq[1]);
                        if ($playlist->id) {
                            $items                  = $playlist->get_items();
                            list($maxCount, $items) = self::_slice($items, $start, $count);
                            foreach ($items as $item) {
                                if ($item['object_type'] == 'song') {
                                    $song = new Song($item['object_id']);
                                    $song->format();
                                    $mediaItems[] = self::_itemSong($song, $parent);
                                }
                            }
                        }
                        break;
                }
                break;
            case 'live_streams':
                switch (count($pathreq)) {
                    case 1: // Get radios list
                        $radios                  = Live_Stream::get_all_radios();
                        list($maxCount, $radios) = self::_slice($radios, $start, $count);
                        foreach ($radios as $radio_id) {
                            $radio = new Live_Stream($radio_id);
                            $radio->format();
                            $mediaItems[] = self::_itemLiveStream($radio, $parent);
                        }
                        break;
                }
                break;
            case 'podcasts':
                switch (count($pathreq)) {
                    case 1: // Get podcasts list
                        $podcasts                  = Catalog::get_podcasts();
                        list($maxCount, $podcasts) = self::_slice($podcasts, $start, $count);
                        foreach ($podcasts as $podcast) {
                            $podcast->format();
                            $mediaItems[] = self::_itemPodcast($podcast, $parent);
                        }
                        break;
                    case 2: // Get podcast episodes list
                        $podcast = new Podcast($pathreq[1]);
                        if ($podcast->id) {
                            $episodes                  = $podcast->get_episodes();
                            list($maxCount, $episodes) = self::_slice($episodes, $start, $count);
                            foreach ($episodes as $episode_id) {
                                $episode = new Podcast_Episode($episode_id);
                                $episode->format();
                                $mediaItems[] = self::_itemPodcastEpisode($episode, $parent);
                            }
                        }
                        break;
                }
                break;
            default:
                $mediaItems[] = self::_musicMetadata('artists');
                $mediaItems[] = self::_musicMetadata('albums');
                $mediaItems[] = self::_musicMetadata('songs');
                $mediaItems[] = self::_musicMetadata('playlists');
                $mediaItems[] = self::_musicMetadata('smartplaylists');
                if (AmpConfig::get('live_stream')) {
                    $mediaItems[] = self::_musicMetadata('live_streams');
                }
                if (AmpConfig::get('podcast')) {
                    $mediaItems[] = self::_musicMetadata('podcasts');
                }
                list($maxCount, $mediaItems) = self::_slice($mediaItems, $start, $count);
                break;
        }

        if ($maxCount == 0) {
            $maxCount = count($mediaItems);
        }

        return array($maxCount, $mediaItems);
    }

    /**
     * @param string $prmPath
     * @return array|null
     */
    public static function _videoMetadata($prmPath)
    {
        $root    = 'amp://video';
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }

        $meta = null;
        switch ($pathreq[0]) {
            case 'tvshows':
                switch (count($pathreq)) {
                    case 1:
                        $counts = count(Catalog::get_tvshows());
                        $meta   = array(
                            'id' => $root . '/tvshows',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts,
                            'dc:title' => T_('TV Shows'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $tvshow = new TVShow($pathreq[1]);
                        if ($tvshow->id) {
                            $tvshow->format();
                            $meta = self::_itemTVShow($tvshow, $root . '/tvshows');
                        }
                        break;
                    case 3:
                        $season = new TVShow_Season($pathreq[2]);
                        if ($season->id) {
                            $season->format();
                            $meta = self::_itemTVShowSeason($season, $root . '/tvshows/' . $pathreq[1]);
                        }
                        break;
                    case 4:
                        $video = new TVShow_Episode($pathreq[3]);
                        if ($video->id) {
                            $video->format();
                            $meta = self::_itemVideo($video, $root . '/tvshows/' . $pathreq[1] . '/' . $pathreq[2]);
                        }
                    break;
                }
                break;
            case 'clips':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::get_videos_count(null, 'clip');
                        $meta   = array(
                            'id' => $root . '/clips',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts,
                            'dc:title' => T_('Clips'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $video = new Clip($pathreq[1]);
                        if ($video->id) {
                            $video->format();
                            $meta = self::_itemVideo($video, $root . '/clips');
                        }
                        break;
                }
                break;
            case 'movies':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::get_videos_count(null, 'movie');
                        $meta   = array(
                            'id' => $root . '/movies',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts,
                            'dc:title' => T_('Movies'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $video = new Movie($pathreq[1]);
                        if ($video->id) {
                            $video->format();
                            $meta = self::_itemVideo($video, $root . '/movies');
                        }
                        break;
                }
                break;
            case 'personal_videos':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::get_videos_count(null, 'personal_video');
                        $meta   = array(
                            'id' => $root . '/personal_videos',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts,
                            'dc:title' => T_('Personal Videos'),
                            'upnp:class' => 'object.container',
                        );
                        break;
                    case 2:
                        $video = new Personal_Video($pathreq[1]);
                        if ($video->id) {
                            $video->format();
                            $meta = self::_itemVideo($video, $root . '/personal_videos');
                        }
                        break;
                }
                break;
            default:
                $meta = array(
                    'id' => $root,
                    'parentID' => '0',
                    'restricted' => '1',
                    'searchable' => '1',
                    'childCount' => '4',
                    'dc:title' => T_('Video'),
                    'upnp:class' => 'object.container', // .storageFolder',
                    'upnp:storageUsed' => '-1',
                );
                break;
        }

        return $meta;
    }

    /**
     * @param $prmPath
     * @param $prmQuery
     * @param $start
     * @param $count
     * @return array
     */
    public static function _videoChilds($prmPath, $prmQuery, $start, $count)
    {
        $mediaItems = array();
        $maxCount   = 0;
        $queryData  = array();
        parse_str($prmQuery, $queryData);

        $parent  = 'amp://video' . $prmPath;
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }

        switch ($pathreq[0]) {
            case 'tvshows':
                switch (count($pathreq)) {
                    case 1: // Get tvshow list
                        $tvshows                  = Catalog::get_tvshows();
                        list($maxCount, $tvshows) = self::_slice($tvshows, $start, $count);
                        foreach ($tvshows as $tvshow) {
                            $tvshow->format();
                            $mediaItems[] = self::_itemTVShow($tvshow, $parent);
                        }
                        break;
                    case 2: // Get season list
                        $tvshow = new TVShow($pathreq[1]);
                        if ($tvshow->id) {
                            $season_ids                  = $tvshow->get_seasons();
                            list($maxCount, $season_ids) = self::_slice($season_ids, $start, $count);
                            foreach ($season_ids as $season_id) {
                                $season = new TVShow_Season($season_id);
                                $season->format();
                                $mediaItems[] = self::_itemTVShowSeason($season, $parent);
                            }
                        }
                        break;
                    case 3: // Get episode list
                        $season = new TVShow_Season($pathreq[2]);
                        if ($season->id) {
                            $episode_ids                  = $season->get_episodes();
                            list($maxCount, $episode_ids) = self::_slice($episode_ids, $start, $count);
                            foreach ($episode_ids as $episode_id) {
                                $video = new Video($episode_id);
                                $video->format();
                                $mediaItems[] = self::_itemVideo($video, $parent);
                            }
                        }
                        break;
                }
                break;
            case 'clips':
                switch (count($pathreq)) {
                    case 1: // Get clips list
                        $videos                  = Catalog::get_videos(null, 'clip');
                        list($maxCount, $videos) = self::_slice($videos, $start, $count);
                        foreach ($videos as $video) {
                            $video->format();
                            $mediaItems[] = self::_itemVideo($video, $parent);
                        }
                        break;
                }
                break;
            case 'movies':
                switch (count($pathreq)) {
                    case 1: // Get clips list
                        $videos                  = Catalog::get_videos(null, 'movie');
                        list($maxCount, $videos) = self::_slice($videos, $start, $count);
                        foreach ($videos as $video) {
                            $video->format();
                            $mediaItems[] = self::_itemVideo($video, $parent);
                        }
                        break;
                }
                break;
            case 'personal_videos':
                switch (count($pathreq)) {
                    case 1: // Get clips list
                        $videos                  = Catalog::get_videos(null, 'personal_video');
                        list($maxCount, $videos) = self::_slice($videos, $start, $count);
                        foreach ($videos as $video) {
                            $video->format();
                            $mediaItems[] = self::_itemVideo($video, $parent);
                        }
                        break;
                }
                break;
            default:
                $mediaItems[] = self::_videoMetadata('clips');
                $mediaItems[] = self::_videoMetadata('tvshows');
                $mediaItems[] = self::_videoMetadata('movies');
                $mediaItems[] = self::_videoMetadata('personal_videos');
                break;
        }

        if ($maxCount == 0) {
            $maxCount = count($mediaItems);
        }

        return array($maxCount, $mediaItems);
    }

    /**
     * @param string $str
     * @return array
     */
    private static function gettokens($str)
    {
        $tokens        = array();
        $nospacetokens = array();
        // put the string into lowercase
        //    $str = strtolower($str);

        // make sure ( or ) get picked up as separate tokens
        $str = str_replace("(", " ( ", $str);
        $str = str_replace(")", " ) ", $str);

        // get the actual tokens
        $actualtokens = explode(" ", $str);
        $actualsize   = sizeof($actualtokens);

        // trim spaces around tokens and discard those which have only spaces in them
        $index = 0;
        for ($i=0; $i < $actualsize; $i++) {
            $actualtokens[$i]=trim($actualtokens[$i]);
            if ($actualtokens[$i] != "") {
                $nospacetokens[$index++] = $actualtokens[$i];
            }
        }

        // now put together tokens which are actually one token e.g. upper hutt
        $onetoken    = "";
        $index       = 0;
        $nospacesize = sizeof($nospacetokens);
        for ($i=0; $i < $nospacesize; $i++) {
            $token = $nospacetokens[$i];
            switch ($token) {
                case "not":
                case "or":
                case "and":
                case "(":
                case ")":
                    if ($onetoken != "") {
                        $tokens[$index++] = $onetoken;
                        $onetoken         = "";
                    }
                    $tokens[$index++] = $token;
                    break;
                default:
                    if ($onetoken == "") {
                        $onetoken = $token;
                    } else {
                        $onetoken = $onetoken . " " . $token;
                    }
                    break;
            }
        }
        if ($onetoken != "") {
            $tokens[$index++] = $onetoken;
        }

        return $tokens;
    }

    /**
     * @param string $query
     * @param string $context
     * @return array
     */
    private static function parse_upnp_search_term($query, $context)
    {
        //echo "Search term ", $query, "\n";
        $tok = str_getcsv($query, ' ');
        //for ($i=0; $i<sizeof($tok); $i++) {
        //    echo $i, $tok[$i];
        //    echo "\n";
        //}
        debug_event(self::class, 'Token ' . var_export($tok, true), 5);

        $term = array();
        if (sizeof($tok) == 3) {
            // tuple, we understand
            switch ($tok[0]) {
                case 'dc:title':
                    $term['ruletype'] = 'title';
                    break;
                case 'upnp:album':
                    $term['ruletype'] = 'album';
                    break;
                case 'upnp:genre':
                    $term['ruletype'] = 'tag';
                    break;
                case 'upnp:artist': // Artist is not implemented unformly through the database
                                    // If we're about to search the album table, we need to look
                                    // for album_artist instead of artist
                    if ($context == 'album') {
                        $term['ruletype'] = 'album_artist';
                    } else {
                        $term['ruletype'] = 'artist';
                    }
                    break;
                case 'upnp:author':
                    $term['ruletype'] = 'author';
                    break;
                case 'upnp:author@role':
                    $term['ruletype'] = $tok[2];

                    return array();
                default:
                    return array();
            }
            switch ($tok[1]) {
                case '=':
                    $term['operator'] = 4;
                    break;
                case 'contains':
                default:
                    $term['operator'] = 0;
                    break;
            }
            $term['input'] = $tok[2];
        }

        return $term;
    }

    /**
     * Cannot be very precious about this as filtering capability ATM just relates to the kind of search we end up doing
     * @param $filter
     * @return string
     */
    private static function parse_upnp_filter($filter)
    {
        // TODO patched out for now: creates problems in search results
        unset($filter);
        // NB filtering is handled in creation of the DIDL now
        //if( strpos( $filter, 'upnp:album' ) ){
        //    return 'album';
        //}
        return 'song';
    }

    /**
     * @param $query
     * @param $type
     * @return array
     */
    private static function parse_upnp_searchcriteria($query, $type)
    {
        // Transforms a upnp search query into an Ampache search query
        $upnp_translations = array(
            array( 'upnp:class = "object.container.album.musicAlbum"', 'album' ),
            array( 'upnp:class derivedfrom "object.item.audioItem"' , 'song' ),
            array( 'upnp:class = "object.container.person.musicArtist"', 'artist'),
            array( 'upnp:class = "object.container.playlistContainer"', 'playlist'),
            array( 'upnp:class derivedfrom "object.container.playlistContainer"', 'playlist'),
            array( 'upnp:class = "object.container.genre.musicGenre"', 'tag' ),
            array( '@refID exists false', '' )
        );

        $tokens = self::gettokens($query);
        $size   = sizeof($tokens);
        //   for ($i=0; $i<sizeof($tokens); $i++) {
        //       echo $tokens[$i]."|";
        //   }
        //   echo "\n";

        // Go through all the tokens and transform anything we recognize
        //If any translation goes to NUL then must remove previous token provided it is AND or OR
        for ($i=0; $i < $size; $i++) {
            for ($j=0; $j < 7; $j++) {
                if ($tokens[$i] == $upnp_translations[$j][0]) {
                    $tokens[$i] = $upnp_translations[$j][1];
                    if ($upnp_translations[$j][1] == '' && $i > 1 && ($tokens[$i - 1] == "and" || $tokens[$i - 1] == "or")) {
                        $tokens[$i - 1] = '';
                    }
                }
            }
        }
        //for ($i=0; $i<sizeof($tokens); $i++) {
        //   echo $tokens[$i]."|";
        //}
        // Start to construct the Ampache Search data array
        $data = array();

        // In some cases the first search term gives the type of search
        // Other types of device may specify the type of search implicitly by the type of filter
        // they supply after the search term.
        // Start with assuming a search type of "song" in the case where the first search term
        // is actually a term rather than a type
        if (str_word_count($tokens[0]) > 1) {
            // first token is not a type, need to work out one
            if ($type == '') {
                $data['type'] = 'song';
            } else {
                $data['type'] = $type;
            }
        } else {
            $data['type'] = $tokens[0];
            $tokens[0]    = '';
        }

        // Construct the operator type. The first one is likely to be 'and' (if present),
        // and the remainder should be 'and' or 'or'
        // upnp allows all search terms to be and/or in any order.
        // Ampache's current search class can only handle terms being all of one type

        $num_and = 0;
        $num_or  = 0;
        $size    = sizeof($tokens);
        for ($i=0; $i < $size; $i++) {
            if ($tokens[$i] == 'and') {
                $num_and++;
                $tokens[$i] = '';
            } elseif ($tokens[$i] == 'or') {
                $num_or++;
                $tokens[$i] = '';
            } elseif ($tokens[$i] == '(' || $tokens[$i] == ')') {
                $tokens[$i] = '';
            }
        }
        //   echo "\nNUM_AND ", $num_and;
        //   echo "\nNUM_OR ", $num_or;
        //   echo "\n";

        if ($num_and == 0 && $num_or == 0) {
            $data['operator'] = 'and';
        } elseif ($num_and <= 1 && $num_or > 0) {
            $data['operator'] = 'or';
        } elseif ($num_and > 0 && $num_or == 0) {
            $data['operator'] = 'and';
        } else {
            $data['operator'] = 'error'; // Should really be an error operator/return
           return $data; // go no further because we can't handle the combination of and and or
        }

        $rule_num = 1;
        $size     = sizeof($tokens);
        for ($i=0; $i < $size; $i++) {
            if ($tokens[$i] != '') {
                $rule = 'rule_' . (string) $rule_num;
                $term = self::parse_upnp_search_term($tokens[$i], $data['type']);
                if (!empty($term)) {
                    $data[$rule]               = $term['ruletype'];
                    $data[$rule . '_operator'] = $term['operator'];
                    $data[$rule . '_input']    = $term['input'];
                    $rule_num++;
                }
            }
        }
        if ($rule_num == 1) {
            // Must be a wildcard search: no tuples detected. How to tell search class to search for something?
            // Insert search qualified on "ID > 0", which should call for everything
            $rule                      = 'rule_1';
            $data[$rule]               = 'id';
            $data[$rule . '_operator'] = 'GT';
            $data[$rule . '_input']    = '0';
        }

        return $data;
    }

    /**
     * @param $criteria
     * @param $filter
     * @param $start
     * @param $count
     * @return array
     */
    public static function _callSearch($criteria, $filter, $start, $count)
    {
        $mediaItems   = array();
        $maxCount     = 0;
        $type         = self::parse_upnp_filter($filter);
        $search_terms = self::parse_upnp_searchcriteria($criteria, $type);
        debug_event(self::class, 'Dumping $search_terms: ' . var_export($search_terms, true), 5);
        $ids = Search::run($search_terms);// return a list of IDs
        if (count($ids) == 0) {
            debug_event(self::class, 'Search returned no hits', 5);

            return array(0, $mediaItems);
        }
        //debug_event(self::class, 'Dumping $search results: '.var_export( $ids, true ) , 5);
        debug_event(self::class, ' ' . (string) count($ids) . ' ids looking for type ' . $search_terms['type'], 5);

        switch ($search_terms['type']) {
            case 'artist':
                list($maxCount, $ids) = self::_slice($ids, $start, $count);
                foreach ($ids as $artist_id) {
                    $artist = new Artist($artist_id);
                    $artist->format();
                    $mediaItems[] = self::_itemArtist($artist, "amp://music/artists");
                }
            break;
            case 'song':
                list($maxCount, $ids) = self::_slice($ids, $start, $count);
                foreach ($ids as $song_id) {
                    $song = new Song($song_id);
                    $song->format();
                    $mediaItems[] = self::_itemSong($song, $parent = 'amp://music/albums/' . (string) $song->album);
                }
            break;
            case 'album':
                list($maxCount, $ids) = self::_slice($ids, $start, $count);
                foreach ($ids as $album_id) {
                    $album = new Album($album_id);
                    $album->format();
                    //debug_event(self::class, $album->f_title, 5);
                    $mediaItems[] = self::_itemAlbum($album, "amp://music/albums");
                }
            break;
            case 'playlist':
                list($maxCount, $ids) = self::_slice($ids, $start, $count);
                foreach ($ids as $pl_id) {
                    $playlist = new Playlist($pl_id);
                    $playlist->format();
                    $mediaItems[] = self::_itemPlaylist($playlist, "amp://music/playlists");
                }
            break;
            case 'tag':
                list($maxCount, $ids) = self::_slice($ids, $start, $count);
                foreach ($ids as $tag_id) {
                    $tag = new Tag($tag_id);
                    $tag->format();
                    $mediaItems[] = self::_itemTag($tag, "amp://music/tags");
                }
            break;
    }
        if ($maxCount == 0) {
            $maxCount = count($mediaItems);
        }

        return array($maxCount, $mediaItems);
    }

    /**
     * @param $title
     * @return string|string[]|null
     */
    private static function _replaceSpecialSymbols($title)
    {
        /*
         * replace non letter or digits
         * 17 Oct. patched this out because it's changing the titles of tracks so that
         * when the device comes to play and searches for songs belonging to the album, the
         * album is no longer found as a match
         */
        //debug_event(self::class, 'replace <<< ' . $title, 5);
        //$title = preg_replace('~[^\\pL\d\.:\s\(\)\.\,\'\"]+~u', '-', $title);
        //debug_event(self::class, 'replace >>> ' . $title, 5);
        if ($title == "") {
            $title = '(no title)';
        }

        return $title;
    }

    /**
     * @param Artist $artist
     * @param string $parent
     * @return array
     */
    private static function _itemArtist($artist, $parent)
    {
        return array(
            'id' => 'amp://music/artists/' . $artist->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'childCount' => $artist->albums,
            'dc:title' => self::_replaceSpecialSymbols($artist->f_name),
            //'upnp:class' => 'object.container.person.musicArtist',
            'upnp:class' => 'object.container',
        );
    }
    /**
      * @param Tag $tag
      * @param string $parent
      * @return array
      */
    private static function _itemTag($tag, $parent)
    {
        return array(
            'id' => 'amp://music/tags/' . $tag->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'childCount' => 1,
            'dc:title' => self::_replaceSpecialSymbols($tag->f_name),
            //'upnp:class' => 'object.container.person.musicArtist',
            'upnp:class' => 'object.container',
        );
    }

    /**
     * @param Album $album
     * @param string $parent
     * @return array
     */
    private static function _itemAlbum($album, $parent)
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : false;
        $art_url     = Art::url($album->id, 'album', $api_session);

        return array(
            'id' => 'amp://music/albums/' . $album->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'childCount' => $album->song_count,
            'dc:title' => self::_replaceSpecialSymbols($album->f_title),
            'upnp:class' => 'object.container.album.musicAlbum',  // object.container.album.musicAlbum
            //'upnp:class' => 'object.container',
            'upnp:albumArtist' => $album->album_artist,
            'upnp:albumArtURI' => $art_url,
        );
    }

    /**
     * @param $playlist
     * @param string $parent
     * @return array
     */
    private static function _itemPlaylist($playlist, $parent)
    {
        return array(
            'id' => 'amp://music/playlists/' . $playlist->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'childCount' => count($playlist->get_items()),
            'dc:title' => self::_replaceSpecialSymbols($playlist->f_name),
            'upnp:class' => 'object.container',  // object.container.playlistContainer
        );
    }

    /**
     * @param Search $playlist
     * @param string $parent
     * @return array
     */
    private static function _itemSmartPlaylist($playlist, $parent)
    {
        return array(
            'id' => 'amp://music/smartplaylists/' . $playlist->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'childCount' => count($playlist->get_items()),
            'dc:title' => self::_replaceSpecialSymbols($playlist->f_name),
            'upnp:class' => 'object.container',
        );
    }

    /**
     * @param Song $song
     * @param string $parent
     * @return array
     */
    public static function _itemSong($song, $parent)
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : false;
        $art_url     = Art::url($song->album, 'album', $api_session);

        $fileTypesByExt = self::_getFileTypes();
        $arrFileType    = $fileTypesByExt[$song->type];
        /**
         * Properties observed for MS media player include
         * GetSearchCapabilities
         * @id, @refID,
         * dc:title, dc:creator, dc:publisher, dc:language, dc:date, dc:description,
         * upnp:class, upnp:genre, upnp:artist, upnp:author, upnp:author@role, upnp:album,
         * upnp:originalTrackNumber, upnp:producer, upnp:rating,upnp:actor, upnp:director, upnp:toc,
         * upnp:userAnnotation, upnp:channelName, upnp:longDescription, upnp:programTitle
         * res@size, res@duration, res@protocolInfo, res@protection,
         * microsoft:userRatingInStars, microsoft:userEffectiveRatingInStars, microsoft:userRating, microsoft:userEffectiveRating, microsoft:serviceProvider,
         * microsoft:artistAlbumArtist, microsoft:artistPerformer, microsoft:artistConductor, microsoft:authorComposer, microsoft:authorOriginalLyricist,
         * microsoft:authorWriter
         */
        return array(
            'id' => 'amp://music/songs/' . $song->id,
            'parentID' => $parent,
            'restricted' => 'false',  // XXX
            'dc:title' => self::_replaceSpecialSymbols($song->f_title),
            'dc:date' => date("c", (int) $song->addition_time),
            'dc:creator' => self::_replaceSpecialSymbols($song->f_artist),
            'upnp:class' => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI' => $art_url,
            'upnp:artist' => self::_replaceSpecialSymbols($song->f_artist),
            'upnp:album' => self::_replaceSpecialSymbols($song->f_album),
            'upnp:genre' => Tag::get_display($song->tags, false, 'song'),
            'upnp:originalTrackNumber' => $song->track,
            'res' => $song->play_url('', 'api', true), // For upnp, use local
            'protocolInfo' => $arrFileType['mime'],
            'size' => $song->size,
            'duration' => $song->f_time_h . '.0',
            'bitrate' => $song->bitrate,
            'sampleFrequency' => $song->rate,
            'nrAudioChannels' => '2',  // Just say its stereo as we don't have the real info
            'description' => self::_replaceSpecialSymbols($song->comment),
        );
    }

    /**
     * @param Live_Stream $radio
     * @param string $parent
     * @return array
     */
    public static function _itemLiveStream($radio, $parent)
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : false;
        $art_url     = Art::url($radio->id, 'live_stream', $api_session);

        $fileTypesByExt = self::_getFileTypes();
        $arrFileType    = $fileTypesByExt[$radio->codec];

        return array(
            'id' => 'amp://music/live_streams/' . $radio->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'dc:title' => self::_replaceSpecialSymbols($radio->name),
            'upnp:class' => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI' => $art_url,

            'res' => $radio->url,
            'protocolInfo' => $arrFileType['mime']
        );
    }

    /**
     * @param $tvshow
     * @param string $parent
     * @return array
     */
    private static function _itemTVShow($tvshow, $parent)
    {
        return array(
            'id' => 'amp://video/tvshows/' . $tvshow->id,
            'parentID' => $parent,
            'restricted' => '1',
            'childCount' => count($tvshow->get_seasons()),
            'dc:title' => self::_replaceSpecialSymbols($tvshow->f_name),
            'upnp:class' => 'object.container',
        );
    }

    /**
     * @param TVShow_Season $season
     * @param string $parent
     * @return array
     */
    private static function _itemTVShowSeason($season, $parent)
    {
        return array(
            'id' => 'amp://video/tvshows/' . $season->tvshow . '/' . $season->id,
            'parentID' => $parent,
            'restricted' => '1',
            'childCount' => count($season->get_episodes()),
            'dc:title' => self::_replaceSpecialSymbols($season->f_name),
            'upnp:class' => 'object.container',
        );
    }

    /**
     * @param $video
     * @param string $parent
     * @return array
     */
    private static function _itemVideo($video, $parent)
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : false;
        $art_url     = Art::url($video->id, 'video', $api_session);

        $fileTypesByExt = self::_getFileTypes();
        $arrFileType    = $fileTypesByExt[$video->type];

        return array(
            'id' => $parent . '/' . $video->id,
            'parentID' => $parent,
            'restricted' => '1',
            'dc:title' => self::_replaceSpecialSymbols($video->f_title),
            'upnp:class' => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI' => $art_url,
            'upnp:genre' => Tag::get_display($video->tags, false, 'video'),

            'res' => $video->play_url('', 'api'),
            'protocolInfo' => $arrFileType['mime'],
            'size' => $video->size,
            'duration' => $video->f_time_h . '.0',
        );
    }

    /**
     * @param $podcast
     * @param string $parent
     * @return array
     */
    private static function _itemPodcast($podcast, $parent)
    {
        return array(
            'id' => 'amp://music/podcasts/' . $podcast->id,
            'parentID' => $parent,
            'restricted' => '1',
            'childCount' => count($podcast->get_episodes()),
            'dc:title' => self::_replaceSpecialSymbols($podcast->f_title),
            'upnp:class' => 'object.container',
        );
    }

    /**
     * @param Podcast_Episode $episode
     * @param string $parent
     * @return array
     */
    private static function _itemPodcastEpisode($episode, $parent)
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : false;
        $art_url     = Art::url($episode->podcast, 'podcast', $api_session);

        $fileTypesByExt = self::_getFileTypes();
        $arrFileType    = (!empty($episode->type)) ? $fileTypesByExt[$episode->type] : array();

        $ret = array(
            'id' => 'amp://music/podcasts/' . $episode->podcast . '/' . $episode->id,
            'parentID' => $parent,
            'restricted' => '1',
            'dc:title' => self::_replaceSpecialSymbols($episode->f_title),
            'upnp:album' => self::_replaceSpecialSymbols($episode->f_podcast),
            'upnp:class' => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI' => $art_url
        );
        if (isset($arrFileType['mime'])) {
            $ret['res']          = $episode->play_url('', 'api');
            $ret['protocolInfo'] = $arrFileType['mime'];
            $ret['size']         = $episode->size;
            $ret['duration']     = $episode->f_time_h . '.0';
        }

        return $ret;
    }

    /**
     * @return array
     */
    private static function _getFileTypes()
    {
        return array(
            'wav' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-wav:*',),
            'mpa' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/mpeg:*',),
            '.mp1' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/mpeg:*',),
            'mp3' => array('class' => 'object.item.audioItem.musicTrack', 'mime' => 'http-get:*:audio/mpeg:*',),
            'aiff' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-aiff:*',),
            'aif' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-aiff:*',),
            'wma' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-ms-wma:*',),
            'lpcm' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/lpcm:*',),
            'aac' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-aac:*',),
            'm4a' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-m4a:*',),
            'ac3' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-ac3:*',),
            'pcm' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/lpcm:*',),
            'flac' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/flac:*',),
            'ogg' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:application/ogg:*',),
            'mka' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-matroska:*',),
            'mp4a' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-m4a:*',),
            'mp2' => array('class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/mpeg:*',),
            'gif' => array('class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/gif:*',),
            'jpg' => array('class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/jpeg:*',),
            'jpe' => array('class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/jpeg:*',),
            'png' => array('class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/png:*',),
            'tiff' => array('class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/tiff:*',),
            'tif' => array('class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/tiff:*',),
            'jpeg' => array('class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/jpeg:*',),
            'bmp' => array('class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/bmp:*',),
            'asf' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-ms-asf:*',),
            'wmv' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-ms-wmv:*',),
            'mpeg2' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2:*',),
            'avi' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-msvideo:*',),
            'divx' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-msvideo:*',),
            'mpg' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',),
            'm1v' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',),
            'm2v' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',),
            'mp4' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mp4:*',),
            'mov' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/quicktime:*',),
            'vob' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/dvd:*',),
            'dvr-ms' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-ms-dvr:*',),
            'dat' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',),
            'mpeg' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',),
            'm1s' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',),
            'm2p' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2:*',),
            'm2t' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',),
            'm2ts' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',),
            'mts' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',),
            'ts' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',),
            'tp' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',),
            'trp' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',),
            'm4t' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',),
            'm4v' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/MP4V-ES:*',),
            'vbs' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2:*',),
            'mod' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2:*',),
            'mkv' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-matroska:*',),
            '3g2' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mp4:*',),
            '3gp' => array('class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mp4:*',),
        );
    }
} // end upnp_api.class
