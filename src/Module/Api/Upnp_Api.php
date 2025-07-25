<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Api;

use Ampache\Module\System\Core;
use Ampache\Repository\Model\Album;
use Ampache\Module\Playback\Stream;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Video;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use DateTime;
use DOMDocument;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Exception;
use XMLReader;

/**
 * UPnP Class
 *
 * This class wrap Ampache to UPnP API functions.
 * These are all static calls.
 *
 * This class is a derived work from UMSP project (http://wiki.wdlxtv.com/UMSP).
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
    public const SSDP_DEBUG = false;

    /**
     * get_uuidStr
     */
    public static function get_uuidStr(): string
    {
        // Create uuid based on host
        $key  = 'ampache_' . AmpConfig::get('http_host');
        $hash = hash('md5', $key);

        return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4) . '-' . substr($hash, 16, 4) . '-' . substr($hash, 20);
    }

    /* ================================== Begin SSDP functions ================================== */

    /**
     * @param string $buf
     * @param int $delay
     * @param string $host
     * @param int $port
     */
    private static function udpSend($buf, $delay = 15, $host = "239.255.255.250", $port = 1900): void
    {
        usleep($delay * 1000); // we are supposed to delay before sending
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket !== false) {
            // when broadcast, set broadcast socket option
            if ($host == "239.255.255.250") {
                socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
            }
            socket_sendto($socket, $buf, strlen((string)$buf), 0, $host, $port);
            socket_close($socket);
        }
    }

    /**
     * @param int $delay
     * @param string $host
     * @param int $port
     * @param string $prefix
     * @param bool $alive
     */
    public static function sddpSend($delay = 15, $host = "239.255.255.250", $port = 1900, $prefix = "NT", $alive = true): void
    {
        $strHeader = 'NOTIFY * HTTP/1.1' . "\r\n";
        $strHeader .= 'HOST: ' . $host . ':' . $port . "\r\n";
        $strHeader .= 'LOCATION: http://' . gethostbyname(AmpConfig::get('http_host')) . ':' . AmpConfig::get('http_port', 80) . AmpConfig::get('raw_web_path') . '/upnp/MediaServerServiceDesc.php' . "\r\n";
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
    public static function sendResponse($delaytime, $actst, $address): void
    {
        $response = 'HTTP/1.1 200 OK' . "\r\n";
        $response .= 'CACHE-CONTROL: max-age=1800' . "\r\n";
        $dt = new DateTime('UTC');
        $response .= 'DATE: ' . $dt->format('D, d M Y H:i:s \G\M\T') . "\r\n"; // RFC2616 date
        $response .= 'EXT:' . "\r\n";
        // Note that quite a few devices are unable to resolve a URL into an IP address. Therefore we have to use a
        // local IP address - resolve http_host into IP address
        $response .= 'LOCATION: http://' . gethostbyname(AmpConfig::get('http_host')) . ':' . AmpConfig::get('http_port', 80) . AmpConfig::get('raw_web_path') . '/upnp/MediaServerServiceDesc.php' . "\r\n";
        $response .= 'SERVER: DLNADOC/1.50 UPnP/1.0 Ampache/' . AmpConfig::get('version') . "\r\n";
        $response .= 'ST: ' . $actst . "\r\n";
        $response .= 'USN: ' . 'uuid:' . self::get_uuidStr() . '::' . $actst . "\r\n";
        $response .= "\r\n"; // gupnp-universal-cp cannot see us without this line.

        if ($delaytime > 5) {
            $delaytime = 5;
        }
        // Delay in ms
        $delay = random_int(15, $delaytime * 1000);

        $addr = explode(":", $address);
        if (self::SSDP_DEBUG) {
            debug_event(self::class, 'Sending response to: ' . $addr[0] . ':' . $addr[1] . PHP_EOL . $response, 5);
        }
        self::udpSend($response, $delay, $addr[0], (int) $addr[1]);
        if (self::SSDP_DEBUG) {
            // for timing
            debug_event(self::class, '(Sent)', 5);
        }
    }

    /**
     * @param $unpacked
     * @param $remote
     */
    public static function notify_request($unpacked, $remote): void
    {
        $headers = self::get_headers($unpacked);
        $str     = 'Notify ' . $remote . ' ' . $headers['nts'] . ' for ' . $headers['nt'];
        // We don't do anything with notifications except log them to check rx working
        if (self::SSDP_DEBUG) {
            debug_event(self::class, $str, 5);
        }
    }

    /**
     * Extracts headers from a given data string and returns them as an associative array.
     *
     * @param string $data The raw data string containing the headers to process.
     * @return array An associative array of headers where keys are header names and values are their corresponding values.
     */
    public static function get_headers($data): array
    {
        $lines  = explode(PHP_EOL, $data); // split into lines
        $keys   = [];
        $values = [];
        foreach ($lines as $line) {
            //$line = str_replace( ' ', '', $line );
            $line   = (string)preg_replace('/[\x00-\x1F\x7F]/', '', $line);
            $tokens = explode(' ', $line);
            //echo 'BARELINE:'.$line.'&'.count($tokens).PHP_EOL;
            if (count($tokens) > 1) {
                $tokens[0] = str_replace(':', '', $tokens[0]); // remove ':' and convert to keys lowercase for match
                $tokens[0] = strtolower($tokens[0]);
                $keys[]    = $tokens[0];
                $tokens[1] = str_replace("\"", '', $tokens[1]);
                $values[]  = $tokens[1];
            }
        }

        return array_combine($keys, $values);
    }

    /**
     * @param string $data
     * @param string $address
     * @throws Exception
     */
    public static function discovery_request($data, $address): void
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
            } elseif (self::SSDP_DEBUG) {
                debug_event(self::class, 'ST header not for a service we provide [' . $actst . ']', 5);
            }
        } elseif (self::SSDP_DEBUG) {
            debug_event(self::class, 'M-SEARCH MAN header not understood [' . $headers['man'] . ']', 5);
        }
    }

    /* ================================== End SSDP functions ================================== */

    /**
     * @param $prmRequest
     * @return array
     */
    public static function parseUPnPRequest($prmRequest): array
    {
        $retArr = [];
        $reader = new XMLReader();
        $result = $reader->XML($prmRequest);
        if (!$result) {
            debug_event(self::class, 'XML reader class setup failed', 5);
        }

        while ($reader->read()) {
            debug_event(self::class, $reader->localName . ' ' . $reader->nodeType . ' ' . (string) XMLReader::ELEMENT . ' ' . (string) $reader->isEmptyElement, 5);

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
    }

    /**
     * @param $filterValue
     * @param $keyisRes
     * @param $keytoCheck
     * Checks whether key is in filter string, taking account of allowable filter wildcards and null strings
     */
    public static function isinFilter($filterValue, $keyisRes, $keytoCheck): bool
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
        $filt = explode(',', $filterValue); // do exact word match rather than partial, which is what strpos does.

        //debug_event(self::class, 'checking '.$testKey.' in '.var_export($filt, true), 5);
        return in_array($testKey, $filt, true); // this is necessary, (rather than strpos) because "res" turns up in many keys, whose results may not be wanted
    }

    /**
     * @param $prmItems
     * @param $filterValue
     * @return DOMDocument
     */
    public static function createDIDL($prmItems, $filterValue): DOMDocument
    {
        $xmlDoc               = new DOMDocument('1.0' /*, 'utf-8'*/);
        $xmlDoc->formatOutput = true; // Note: other players don't seem to do this
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
            $prmItems = [$prmItems];
        }

        // Add each item in $prmItems array to $ndDIDL:
        foreach ($prmItems as $item) {
            if (!is_array($item)) {
                debug_event(self::class, 'item is not array', 2);
                debug_event(self::class, $item, 5);
                continue;
            }

            if (
                $item['upnp:class'] == 'object.container' ||
                $item['upnp:class'] == 'object.container.album.musicAlbum' ||
                $item['upnp:class'] == 'object.container.person.musicArtist' ||
                $item['upnp:class'] == 'object.container.storageFolder'
            ) {
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
                        $ndItem->setAttribute('searchable', $value);
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
                            $ndTag_text = $xmlDoc->createTextNode((mb_detect_encoding($xvalue, 'auto') == 'UTF-8') ? $xvalue : utf8_encode($xvalue));
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
    public static function createSOAPEnvelope(
        $prmDIDL,
        $prmNumRet,
        $prmTotMatches,
        $prmResponseType = 'u:BrowseResponse',
        $prmUpdateID = '0'
    ): DOMDocument {
        /**
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
        //$ndUpdateID = $doc->createElement('UpdateID', (string) bin2hex(random_bytes(20)); // seems to be ignored by the WDTVL
        $ndBrowseResp->appendChild($ndUpdateID);

        return $doc;
    }

    /**
     * @param string $prmPath
     */
    public static function _musicMetadata($prmPath): ?array
    {
        $root    = 'amp://music';
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }

        $meta   = null;
        $counts = Catalog::get_server_counts(0);

        switch ($pathreq[0]) {
            case 'artists':
                switch (count($pathreq)) {
                    case 1:
                        $meta = [
                            'id' => $root . '/artists',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['artist'],
                            'dc:title' => T_('Artists'),
                            'upnp:class' => 'object.container',
                        ];
                        break;
                    case 2:
                        $artist = new Artist((int)$pathreq[1]);
                        if ($artist->isNew() === false) {
                            $meta = self::_itemArtist($artist, $root . '/artists');
                        }
                        break;
                }
                break;
            case 'albums':
                switch (count($pathreq)) {
                    case 1:
                        $meta = [
                            'id' => $root . '/albums',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['album'],
                            'dc:title' => T_('Albums'),
                            'upnp:class' => 'object.container',
                        ];
                        break;
                    case 2:
                        $album = new Album((int)$pathreq[1]);
                        if ($album->isNew() === false) {
                            $meta = self::_itemAlbum($album, $root . '/albums');
                        }
                        break;
                }
                break;
            case 'songs':
                switch (count($pathreq)) {
                    case 1:
                        $meta = [
                            'id' => $root . '/songs',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['song'],
                            'dc:title' => T_('Songs'),
                            'upnp:class' => 'object.container',
                        ];
                        break;
                    case 2:
                        $song = new Song((int)$pathreq[1]);
                        if ($song->isNew() === false) {
                            $song->fill_ext_info();
                            $meta = self::_itemSong($song, $root . '/songs');
                        }
                        break;
                }
                break;
            case 'playlists':
                switch (count($pathreq)) {
                    case 1:
                        $meta = [
                            'id' => $root . '/playlists',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['playlist'],
                            'dc:title' => T_('Playlists'),
                            'upnp:class' => 'object.container',
                        ];
                        break;
                    case 2:
                        $playlist = new Playlist((int)$pathreq[1]);
                        if ($playlist->isNew() === false) {
                            $meta = self::_itemPlaylist($playlist, $root . '/playlists');
                        }
                        break;
                }
                break;
            case 'smartplaylists':
                switch (count($pathreq)) {
                    case 1:
                        $meta = [
                            'id' => $root . '/smartplaylists',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['search'],
                            'dc:title' => T_('Smart Playlists'),
                            'upnp:class' => 'object.container',
                        ];
                        break;
                    case 2:
                        $playlist = new Search((int)$pathreq[1], 'song');
                        if ($playlist->isNew() === false) {
                            $meta = self::_itemSmartPlaylist($playlist, $root . '/smartplaylists');
                        }
                        break;
                }
                break;
            case 'live_streams':
                switch (count($pathreq)) {
                    case 1:
                        $meta = [
                            'id' => $root . '/live_streams',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['live_stream'],
                            'dc:title' => T_('Radio Stations'),
                            'upnp:class' => 'object.container',
                        ];
                        break;
                    case 2:
                        $radio = new Live_Stream((int)$pathreq[1]);
                        if ($radio->isNew() === false) {
                            $meta = self::_itemLiveStream($radio, $root . '/live_streams');
                        }
                        break;
                }
                break;
            case 'podcasts':
                switch (count($pathreq)) {
                    case 1:
                        $meta = [
                            'id' => $root . '/podcasts',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts['podcast'],
                            'dc:title' => T_('Podcasts'),
                            'upnp:class' => 'object.container',
                        ];
                        break;
                    case 2:
                        $podcast = self::getPodcastRepository()->findById((int)$pathreq[1]);
                        if ($podcast !== null) {
                            $meta = self::_itemPodcast($podcast, $root . '/podcasts');
                        }
                        break;
                    case 3:
                        $episode = new Podcast_Episode((int)$pathreq[2]);
                        if (isset($episode->id)) {
                            $meta = self::_itemPodcastEpisode($episode, $root . '/podcasts/' . $pathreq[1]);
                        }
                        break;
                }
                break;
            default:
                $meta = [
                    'id' => $root,
                    'parentID' => '0',
                    'restricted' => '1',
                    'searchable' => '1',
                    'childCount' => '5',
                    'dc:title' => T_('Music'),
                    'upnp:class' => 'object.container', //.storageFolder',
                    'upnp:storageUsed' => '-1',
                ];
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
    public static function _slice($items, $start, $count): array
    {
        $maxCount = count($items);
        //debug_event(self::class, 'slice: ' . $maxCount . "   " . $start . "    " . $count, 5);

        return [
            $maxCount,
            array_slice($items, $start, (($count == 0) ? $maxCount - $start : $count)),
        ];
    }

    /**
     * @param $prmPath
     * @param $prmQuery
     * @param $start
     * @param $count
     * @return array
     */
    public static function _musicChilds($prmPath, $prmQuery, $start, $count): array
    {
        $mediaItems = [];
        $maxCount   = 0;
        $queryData  = [];
        parse_str($prmQuery, $queryData);

        debug_event(self::class, 'MusicChilds: [' . $prmPath . '] [' . $prmQuery . ']' . '[' . $start . '] [' . $count . ']', 5);

        $parent  = 'amp://music' . $prmPath;
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }
        debug_event(self::class, 'MusicChilds4: [' . $pathreq[0] . ']', 5);
        $counts = Catalog::get_server_counts(0);

        switch ($pathreq[0]) {
            case 'artists':
                switch (count($pathreq)) {
                    case 1: // Get artists list
                        $artists              = Catalog::get_artists(null, $count, $start);
                        [$maxCount, $artists] = [$counts['artist'], $artists];
                        foreach ($artists as $artist) {
                            $mediaItems[] = self::_itemArtist($artist, $parent);
                        }
                        break;
                    case 2: // Get artist's albums list
                        $artist = new Artist((int)$pathreq[1]);
                        if ($artist->isNew() === false) {
                            $album_ids              = self::getAlbumRepository()->getAlbumByArtist($artist->id);
                            [$maxCount, $album_ids] = self::_slice($album_ids, $start, $count);
                            foreach ($album_ids as $album_id) {
                                $album = new Album($album_id);
                                if ($album->isNew()) {
                                    continue;
                                }

                                $mediaItems[] = self::_itemAlbum($album, $parent);
                            }
                        }
                        break;
                }
                break;
            case 'albums':
                switch (count($pathreq)) {
                    case 1: // Get albums list
                        $album_ids              = Catalog::get_albums($count, $start);
                        [$maxCount, $album_ids] = [$counts['album'], $album_ids];
                        foreach ($album_ids as $album_id) {
                            $album = new Album($album_id);
                            if ($album->isNew()) {
                                continue;
                            }

                            $mediaItems[] = self::_itemAlbum($album, $parent);
                        }
                        break;
                    case 2: // Get album's songs list
                        $album = new Album((int)$pathreq[1]);
                        if (isset($album->id)) {
                            $song_ids              = self::getSongRepository()->getByAlbum($album->id);
                            [$maxCount, $song_ids] = self::_slice($song_ids, $start, $count);
                            foreach ($song_ids as $song_id) {
                                $song = new Song($song_id);
                                if ($song->isNew() === false) {
                                    $song->fill_ext_info();
                                    $mediaItems[] = self::_itemSong($song, $parent);
                                }
                            }
                        }
                        break;
                }
                break;
            case 'songs':
                // Get songs list
                if (count($pathreq) == 1) {
                    $catalogs = Catalog::get_catalogs();
                    foreach ($catalogs as $catalog_id) {
                        $catalog = Catalog::create_from_id($catalog_id);
                        if ($catalog === null) {
                            break;
                        }
                        $songs              = $catalog->get_songs();
                        [$maxCount, $songs] = self::_slice($songs, $start, $count);
                        foreach ($songs as $song) {
                            if ($song->isNew() === false) {
                                $song->fill_ext_info();
                                $mediaItems[] = self::_itemSong($song, $parent);
                            }
                        }
                    }
                }
                break;
            case 'playlists':
                switch (count($pathreq)) {
                    case 1: // Get playlists list
                        $pl_ids              = Playlist::get_playlists();
                        [$maxCount, $pl_ids] = self::_slice($pl_ids, $start, $count);
                        foreach ($pl_ids as $pl_id) {
                            $playlist     = new Playlist($pl_id);
                            $mediaItems[] = self::_itemPlaylist($playlist, $parent);
                        }
                        break;
                    case 2: // Get playlist's songs list
                        $playlist = new Playlist((int)$pathreq[1]);
                        if ($playlist->isNew() === false) {
                            $items              = $playlist->get_items();
                            [$maxCount, $items] = self::_slice($items, $start, $count);
                            foreach ($items as $item) {
                                if ($item['object_type'] == LibraryItemEnum::SONG) {
                                    $song = new Song($item['object_id']);
                                    if ($song->isNew() === false) {
                                        $song->fill_ext_info();
                                        $mediaItems[] = self::_itemSong($song, $parent);
                                    }
                                }
                            }
                        }
                        break;
                }
                break;
            case 'smartplaylists':
                switch (count($pathreq)) {
                    case 1: // Get playlists list
                        $searches              = Search::get_searches();
                        [$maxCount, $searches] = self::_slice($searches, $start, $count);
                        foreach ($searches as $search) {
                            $playlist     = new Search($search['id'], 'song');
                            $mediaItems[] = self::_itemPlaylist($playlist, $parent);
                        }
                        break;
                    case 2: // Get playlist's songs list
                        $playlist = new Search((int)$pathreq[1], 'song');
                        if ($playlist->isNew() === false) {
                            $items              = $playlist->get_items();
                            [$maxCount, $items] = self::_slice($items, $start, $count);
                            foreach ($items as $item) {
                                if ($item['object_type'] == LibraryItemEnum::SONG) {
                                    $song = new Song($item['object_id']);
                                    if ($song->isNew() === false) {
                                        $song->fill_ext_info();
                                        $mediaItems[] = self::_itemSong($song, $parent);
                                    }
                                }
                            }
                        }
                        break;
                }
                break;
            case 'live_streams':
                // Get radios list
                if (count($pathreq) == 1) {
                    /** @var User|null $user */
                    $user   = (!empty(Core::get_global('user'))) ? Core::get_global('user') : null;
                    $radios = self::getLiveStreamRepository()->findAll(
                        $user
                    );

                    [$maxCount, $radios] = self::_slice($radios, $start, $count);
                    foreach ($radios as $radio_id) {
                        $radio        = new Live_Stream($radio_id);
                        $mediaItems[] = self::_itemLiveStream($radio, $parent);
                    }
                }
                break;
            case 'podcasts':
                switch (count($pathreq)) {
                    case 1: // Get podcasts list
                        $podcasts              = Catalog::get_podcasts();
                        [$maxCount, $podcasts] = self::_slice($podcasts, $start, $count);
                        foreach ($podcasts as $podcast) {
                            $mediaItems[] = self::_itemPodcast($podcast, $parent);
                        }
                        break;
                    case 2: // Get podcast episodes list
                        $podcast = self::getPodcastRepository()->findById((int)$pathreq[1]);
                        if ($podcast !== null) {
                            $episodes = $podcast->getEpisodeIds();

                            [$maxCount, $episodes] = self::_slice($episodes, $start, $count);
                            foreach ($episodes as $episode_id) {
                                $episode      = new Podcast_Episode($episode_id);
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
                [$maxCount, $mediaItems] = self::_slice($mediaItems, $start, $count);
                break;
        }

        if ($maxCount == 0) {
            $maxCount = count($mediaItems);
        }

        return [
            $maxCount,
            $mediaItems,
        ];
    }

    /**
     * @param string $prmPath
     */
    public static function _videoMetadata($prmPath): ?array
    {
        $root    = 'amp://video';
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }

        $meta = null;
        switch ($pathreq[0]) {
            case 'clips':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::get_videos_count();
                        $meta   = [
                            'id' => $root . '/videos',
                            'parentID' => $root,
                            'restricted' => '1',
                            'childCount' => $counts,
                            'dc:title' => T_('Videos'),
                            'upnp:class' => 'object.container',
                        ];
                        break;
                    case 2:
                        $video = new Video((int)$pathreq[1]);
                        if ($video->isNew() === false) {
                            $meta = self::_itemVideo($video, $root . '/videos');
                        }
                        break;
                }
                break;
            default:
                $meta = [
                    'id' => $root,
                    'parentID' => '0',
                    'restricted' => '1',
                    'searchable' => '1',
                    'childCount' => '4',
                    'dc:title' => T_('Video'),
                    'upnp:class' => 'object.container', // .storageFolder',
                    'upnp:storageUsed' => '-1',
                ];
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
    public static function _videoChilds($prmPath, $prmQuery, $start, $count): array
    {
        $mediaItems = [];
        $maxCount   = 0;
        $queryData  = [];
        parse_str($prmQuery, $queryData);

        $parent  = 'amp://video' . $prmPath;
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }

        switch ($pathreq[0]) {
            case 'videos':
                // Get videos list
                if (count($pathreq) == 1) {
                    $videos              = Catalog::get_videos();
                    [$maxCount, $videos] = self::_slice($videos, $start, $count);
                    foreach ($videos as $video) {
                        $mediaItems[] = self::_itemVideo($video, $parent);
                    }
                }
                break;
            default:
                $mediaItems[] = self::_videoMetadata('videos');
                break;
        }

        if ($maxCount == 0) {
            $maxCount = count($mediaItems);
        }

        return [
            $maxCount,
            $mediaItems,
        ];
    }

    /**
     * @return string[]
     */
    private static function gettokens(string $str): array
    {
        $tokens        = [];
        $nospacetokens = [];
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
        for ($i = 0; $i < $actualsize; $i++) {
            $actualtokens[$i] = trim($actualtokens[$i]);
            if ($actualtokens[$i] != "") {
                $nospacetokens[$index++] = $actualtokens[$i];
            }
        }

        // now put together tokens which are actually one token e.g. upper hutt
        $onetoken    = "";
        $index       = 0;
        $nospacesize = sizeof($nospacetokens);
        for ($i = 0; $i < $nospacesize; $i++) {
            $token = $nospacetokens[$i];
            switch ($token) {
                case 'not':
                case 'or':
                case 'and':
                case '(':
                case ')':
                    if ($onetoken != "") {
                        $tokens[$index++] = $onetoken;
                        $onetoken         = "";
                    }
                    $tokens[$index++] = (string)$token;
                    break;
                default:
                    if ($onetoken == "") {
                        $onetoken = (string)$token;
                    } else {
                        $onetoken = $onetoken . " " . $token;
                    }
                    break;
            }
        }
        if ($onetoken != "") {
            $tokens[$index] = $onetoken;
        }

        return $tokens;
    }

    /**
     * @param string $query
     * @param string $context
     * @return array
     */
    private static function parse_upnp_search_term($query, $context): array
    {
        //echo "Search term ", $query, "\n";
        $tok = str_getcsv($query, ' ');
        //for ($i = 0; $i<sizeof($tok); $i++) {
        //    echo $i, $tok[$i];
        //    echo "\n";
        //}
        debug_event(self::class, 'Token ' . var_export($tok, true), 5);

        $term = [];
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
                case 'upnp:artist':
                    // Artist is not implemented unformly through the database
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

                    return [];
                default:
                    return [];
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
     */
    private static function parse_upnp_filter($filter): string
    {
        // TODO patched out for now: creates problems in search results
        unset($filter);

        // NB filtering is handled in creation of the DIDL now
        //if ( strpos( $filter, 'upnp:album' ) ){
        //    return 'album';
        //}
        return 'song';
    }

    /**
     * @param $query
     * @param $type
     * @return array
     */
    private static function parse_upnp_searchcriteria($query, $type): array
    {
        // Transforms a upnp search query into an Ampache search query
        $upnp_translations = [
            ['upnp:class = "object.container.album.musicAlbum"', 'album'],
            ['upnp:class derivedfrom "object.item.audioItem"', 'song'],
            ['upnp:class = "object.container.person.musicArtist"', 'artist'],
            ['upnp:class = "object.container.playlistContainer"', 'playlist'],
            ['upnp:class derivedfrom "object.container.playlistContainer"', 'playlist'],
            ['upnp:class = "object.container.genre.musicGenre"', 'tag'],
            ['@refID exists false', ''],
        ];

        $tokens = self::gettokens($query);
        $size   = sizeof($tokens);
        // for ($i = 0; $i<sizeof($tokens); $i++) {
        //     echo $tokens[$i]."|";
        // }
        // echo "\n";

        // Go through all the tokens and transform anything we recognize
        // If any translation goes to NUL then must remove previous token provided it is AND or OR
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < 7; $j++) {
                if ($tokens[$i] == $upnp_translations[$j][0]) {
                    $tokens[$i] = $upnp_translations[$j][1];
                    if ($upnp_translations[$j][1] == '' && $i > 1 && ($tokens[$i - 1] == "and" || $tokens[$i - 1] == "or")) {
                        $tokens[$i - 1] = '';
                    }
                }
            }
        }
        //for ($i = 0; $i<sizeof($tokens); $i++) {
        //   echo $tokens[$i]."|";
        //}
        // Start to construct the Ampache Search data array
        $data = [];

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
        for ($i = 0; $i < $size; $i++) {
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
        for ($i = 0; $i < $size; $i++) {
            if ($tokens[$i] != '') {
                $rule = 'rule_' . $rule_num;
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
    public static function _callSearch($criteria, $filter, $start, $count): array
    {
        $type = self::parse_upnp_filter($filter);
        $data = self::parse_upnp_searchcriteria($criteria, $type);
        debug_event(self::class, 'Dumping search data: ' . var_export($data, true), 5);
        $ids = Search::run($data); // return a list of IDs
        if (count($ids) == 0) {
            debug_event(self::class, 'Search returned no hits', 5);

            return [
                0,
                []
            ];
        }
        //debug_event(self::class, 'Dumping $search results: '.var_export( $ids, true ), 5);
        debug_event(self::class, ' ' . (string) count($ids) . ' ids looking for type ' . $data['type'], 5);

        $mediaItems = [];
        $maxCount   = 0;
        switch ($data['type']) {
            case 'artist':
                [$maxCount, $ids] = self::_slice($ids, $start, $count);
                foreach ($ids as $artist_id) {
                    $artist = new Artist($artist_id);
                    if ($artist->isNew()) {
                        continue;
                    }

                    $mediaItems[] = self::_itemArtist($artist, "amp://music/artists");
                }
                break;
            case 'song':
                [$maxCount, $ids] = self::_slice($ids, $start, $count);
                foreach ($ids as $song_id) {
                    $song = new Song($song_id);
                    if ($song->isNew() === false) {
                        $song->fill_ext_info();
                        $parent       = 'amp://music/albums/' . (string)$song->album;
                        $mediaItems[] = self::_itemSong($song, $parent);
                    }
                }
                break;
            case 'album':
                [$maxCount, $ids] = self::_slice($ids, $start, $count);
                foreach ($ids as $album_id) {
                    $album = new Album($album_id);
                    if ($album->isNew()) {
                        continue;
                    }

                    //debug_event(self::class, $album->get_fullname(), 5);
                    $mediaItems[] = self::_itemAlbum($album, "amp://music/albums");
                }
                break;
            case 'playlist':
                [$maxCount, $ids] = self::_slice($ids, $start, $count);
                foreach ($ids as $pl_id) {
                    $playlist     = new Playlist($pl_id);
                    $mediaItems[] = self::_itemPlaylist($playlist, "amp://music/playlists");
                }
                break;
            case 'tag':
                [$maxCount, $ids] = self::_slice($ids, $start, $count);
                foreach ($ids as $tag_id) {
                    $tag          = new Tag($tag_id);
                    $mediaItems[] = self::_itemTag($tag, "amp://music/tags");
                }
                break;
        }
        if ($maxCount == 0) {
            $maxCount = count($mediaItems);
        }

        return [
            $maxCount,
            $mediaItems,
        ];
    }

    /**
     * @param string|null $title
     * @return string
     */
    private static function _replaceSpecialSymbols($title): string
    {
        /**
        * replace non letter or digits
        * 17 Oct. patched this out because it's changing the titles of tracks so that
        * when the device comes to play and searches for songs belonging to the album, the
        * album is no longer found as a match
        */
        //debug_event(self::class, 'replace <<< ' . $title, 5);
        //$title = preg_replace('~[^\\pL\d\.:\s\(\)\.\,\'\"]+~u', '-', $title);
        //debug_event(self::class, 'replace >>> ' . $title, 5);
        if (empty($title)) {
            return '(no title)';
        }

        return $title;
    }

    /**
     * @param Artist $artist
     * @param string $parent
     * @return array
     */
    private static function _itemArtist($artist, $parent): array
    {
        return [
            'id' => 'amp://music/artists/' . $artist->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'childCount' => $artist->album_count,
            'dc:title' => self::_replaceSpecialSymbols($artist->get_fullname()),
            //'upnp:class' => 'object.container.person.musicArtist',
            'upnp:class' => 'object.container',
        ];
    }

    /**
     * @param Tag $tag
     * @param string $parent
     * @return array
     */
    private static function _itemTag($tag, $parent): array
    {
        return [
            'id' => 'amp://music/tags/' . $tag->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'childCount' => 1,
            'dc:title' => self::_replaceSpecialSymbols(scrub_out($tag->name)),
            //'upnp:class' => 'object.container.person.musicArtist',
            'upnp:class' => 'object.container',
        ];
    }

    /**
     * @param Album $album
     * @param string $parent
     * @return array
     */
    private static function _itemAlbum($album, $parent): array
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : null;
        $art_url     = Art::url($album->id, 'album', $api_session);

        return [
            'id' => 'amp://music/albums/' . $album->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'childCount' => $album->song_count,
            'dc:title' => self::_replaceSpecialSymbols($album->get_fullname()),
            'upnp:class' => 'object.container.album.musicAlbum', // object.container.album.musicAlbum
            //'upnp:class' => 'object.container',
            'upnp:albumArtist' => $album->album_artist,
            'upnp:albumArtURI' => $art_url,
        ];
    }

    /**
     * @param $playlist
     * @param string $parent
     * @return array
     */
    private static function _itemPlaylist($playlist, $parent): array
    {
        return [
            'id' => 'amp://music/playlists/' . $playlist->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'childCount' => count($playlist->get_items()),
            'dc:title' => self::_replaceSpecialSymbols($playlist->name),
            'upnp:class' => 'object.container', // object.container.playlistContainer
        ];
    }

    /**
     * @param Search $playlist
     * @param string $parent
     * @return array
     */
    private static function _itemSmartPlaylist($playlist, $parent): array
    {
        return [
            'id' => 'amp://music/smartplaylists/' . $playlist->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'childCount' => count($playlist->get_items()),
            'dc:title' => self::_replaceSpecialSymbols($playlist->name),
            'upnp:class' => 'object.container',
        ];
    }

    /**
     * @param Song $song
     * @param string $parent
     * @return array
     */
    public static function _itemSong($song, $parent): array
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : null;
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
        return [
            'id' => 'amp://music/songs/' . $song->id,
            'parentID' => $parent,
            'restricted' => 'false', // XXX
            'dc:title' => self::_replaceSpecialSymbols($song->get_fullname()),
            'dc:date' => $song->getAdditionTime()->format(DATE_ATOM),
            'dc:creator' => self::_replaceSpecialSymbols($song->get_artist_fullname()),
            'upnp:class' => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI' => $art_url,
            'upnp:artist' => self::_replaceSpecialSymbols($song->get_artist_fullname()),
            'upnp:album' => self::_replaceSpecialSymbols($song->get_album_fullname()),
            'upnp:genre' => Tag::get_display($song->get_tags(), false, 'song'),
            'upnp:originalTrackNumber' => $song->track,
            'res' => $song->play_url('', 'api', true), // For upnp, use local
            'protocolInfo' => $arrFileType['mime'],
            'size' => $song->size,
            'duration' => $song->get_f_time(true) . '.0',
            'bitrate' => $song->bitrate,
            'sampleFrequency' => $song->rate,
            'nrAudioChannels' => '2', // Just say its stereo as we don't have the real info
            'description' => self::_replaceSpecialSymbols($song->comment),
        ];
    }

    /**
     * @param Live_Stream $radio
     * @param string $parent
     * @return array
     */
    public static function _itemLiveStream($radio, $parent): array
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : null;
        $art_url     = Art::url($radio->id, 'live_stream', $api_session);

        $fileTypesByExt = self::_getFileTypes();
        $arrFileType    = $fileTypesByExt[$radio->codec];

        return [
            'id' => 'amp://music/live_streams/' . $radio->id,
            'parentID' => $parent,
            'restricted' => 'false',
            'dc:title' => self::_replaceSpecialSymbols($radio->name),
            'upnp:class' => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI' => $art_url,

            'res' => $radio->url,
            'protocolInfo' => $arrFileType['mime'],
        ];
    }

    /**
     * @param Video $video
     * @param string $parent
     * @return array
     */
    private static function _itemVideo($video, $parent): array
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : null;
        $art_url     = Art::url($video->id, 'video', $api_session);

        $fileTypesByExt = self::_getFileTypes();
        $arrFileType    = $fileTypesByExt[$video->type];

        return [
            'id' => $parent . '/' . $video->id,
            'parentID' => $parent,
            'restricted' => '1',
            'dc:title' => self::_replaceSpecialSymbols($video->get_fullname()),
            'upnp:class' => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI' => $art_url,
            'upnp:genre' => Tag::get_display($video->get_tags(), false, 'video'),

            'res' => $video->play_url('', 'api'),
            'protocolInfo' => $arrFileType['mime'],
            'size' => $video->size,
            'duration' => $video->get_f_time(true) . '.0',
        ];
    }

    /**
     * @param $podcast
     * @param string $parent
     * @return array
     */
    private static function _itemPodcast($podcast, $parent): array
    {
        return [
            'id' => 'amp://music/podcasts/' . $podcast->id,
            'parentID' => $parent,
            'restricted' => '1',
            'childCount' => count($podcast->get_episodes()),
            'dc:title' => self::_replaceSpecialSymbols($podcast->get_fullname()),
            'upnp:class' => 'object.container',
        ];
    }

    /**
     * @param Podcast_Episode $episode
     * @param string $parent
     * @return array
     */
    private static function _itemPodcastEpisode($episode, $parent): array
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : null;
        $art_url     = Art::url($episode->podcast, 'podcast', $api_session);

        $fileTypesByExt = self::_getFileTypes();
        $arrFileType    = (!empty($episode->type)) ? $fileTypesByExt[$episode->type] : [];

        $ret = [
            'id' => 'amp://music/podcasts/' . $episode->podcast . '/' . $episode->id,
            'parentID' => $parent,
            'restricted' => '1',
            'dc:title' => self::_replaceSpecialSymbols($episode->get_fullname()),
            'upnp:album' => self::_replaceSpecialSymbols($episode->getPodcastName()),
            'upnp:class' => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI' => $art_url,
        ];
        if (isset($arrFileType['mime'])) {
            $ret['res']          = $episode->play_url('', 'api');
            $ret['protocolInfo'] = $arrFileType['mime'];
            $ret['size']         = $episode->size;
            $ret['duration']     = $episode->get_f_time(true) . '.0';
        }

        return $ret;
    }

    /**
     * @return array
     */
    private static function _getFileTypes(): array
    {
        return [
            'wav' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-wav:*',],
            'mpa' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/mpeg:*',],
            '.mp1' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/mpeg:*',],
            'mp3' => ['class' => 'object.item.audioItem.musicTrack', 'mime' => 'http-get:*:audio/mpeg:*',],
            'aiff' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-aiff:*',],
            'aif' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-aiff:*',],
            'wma' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-ms-wma:*',],
            'lpcm' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/lpcm:*',],
            'aac' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-aac:*',],
            'm4a' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-m4a:*',],
            'ac3' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-ac3:*',],
            'pcm' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/lpcm:*',],
            'flac' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/flac:*',],
            'ogg' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:application/ogg:*',],
            'mka' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-matroska:*',],
            'mp4a' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/x-m4a:*',],
            'mp2' => ['class' => 'object.item.audioItem', 'mime' => 'http-get:*:audio/mpeg:*',],
            'gif' => ['class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/gif:*',],
            'jpg' => ['class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/jpeg:*',],
            'jpe' => ['class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/jpeg:*',],
            'png' => ['class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/png:*',],
            'tiff' => ['class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/tiff:*',],
            'tif' => ['class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/tiff:*',],
            'jpeg' => ['class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/jpeg:*',],
            'bmp' => ['class' => 'object.item.imageItem', 'mime' => 'http-get:*:image/bmp:*',],
            'asf' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-ms-asf:*',],
            'wmv' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-ms-wmv:*',],
            'mpeg2' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2:*',],
            'avi' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-msvideo:*',],
            'divx' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-msvideo:*',],
            'mpg' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',],
            'm1v' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',],
            'm2v' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',],
            'mp4' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mp4:*',],
            'mov' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/quicktime:*',],
            'vob' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/dvd:*',],
            'dvr-ms' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-ms-dvr:*',],
            'dat' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',],
            'mpeg' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',],
            'm1s' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg:*',],
            'm2p' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2:*',],
            'm2t' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',],
            'm2ts' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',],
            'mts' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',],
            'ts' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',],
            'tp' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',],
            'trp' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',],
            'm4t' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2ts:*',],
            'm4v' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/MP4V-ES:*',],
            'vbs' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2:*',],
            'mod' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mpeg2:*',],
            'mkv' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/x-matroska:*',],
            '3g2' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mp4:*',],
            '3gp' => ['class' => 'object.item.videoItem', 'mime' => 'http-get:*:video/mp4:*',],
        ];
    }

    /**
     * @deprecated
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getLiveStreamRepository(): LiveStreamRepositoryInterface
    {
        global $dic;

        return $dic->get(LiveStreamRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }
}
