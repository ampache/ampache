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
    /* UPnP classes:
     * object.item.audioItem
     * object.item.imageItem
     * object.item.videoItem
     * object.item.playlistItem
     * object.item.textItem
     * object.container
     */

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

    /**
     * @param string $buf
     * @param integer $delay
     * @param string $host
     * @param integer $port
     */
    private static function udpSend($buf, $delay = 15, $host = "239.255.255.250", $port = 1900)
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_sendto($socket, $buf, strlen((string) $buf), 0, $host, $port);
        socket_close($socket);
        usleep($delay * 1000);
    }

    /**
     * @param integer $delay
     * @param string $host
     * @param integer $port
     * @param string $prefix
     */
    public static function sddpSend($delay = 15, $host = "239.255.255.250", $port = 1900, $prefix = "NT")
    {
        $strHeader  = 'NOTIFY * HTTP/1.1' . "\r\n";
        $strHeader .= 'HOST: ' . $host . ':' . $port . "\r\n";
        $strHeader .= 'LOCATION: http://' . AmpConfig::get('http_host') . ':' . AmpConfig::get('http_port') . AmpConfig::get('raw_web_path') . '/upnp/MediaServerServiceDesc.php' . "\r\n";
        $strHeader .= 'SERVER: DLNADOC/1.50 UPnP/1.0 Ampache/' . AmpConfig::get('version') . "\r\n";
        $strHeader .= 'CACHE-CONTROL: max-age=1800' . "\r\n";
        $strHeader .= 'NTS: ssdp:alive' . "\r\n";
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
     * @param $prmRequest
     * @return array
     */
    public static function parseUPnPRequest($prmRequest)
    {
        $retArr = array();
        $reader = new XMLReader();
        $reader->XML($prmRequest);
        while ($reader->read()) {
            if (($reader->nodeType == XMLReader::ELEMENT) && !$reader->isEmptyElement) {
                switch ($reader->localName) {
                    case 'Browse':
                        $retArr['action'] = 'browse';
                        break;
                    case 'Search':
                        $retArr['action'] = 'search';
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
     * @param $prmItems
     * @return DOMDocument
     */
    public static function createDIDL($prmItems)
    {
        $xmlDoc               = new DOMDocument('1.0', 'utf-8');
        $xmlDoc->formatOutput = true;

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
                debug_event('upnp_api.class', 'item is not array', 2);
                debug_event('upnp_api.class', $item, 5);
                continue;
            }

            if ($item['upnp:class'] == 'object.container') {
                $ndItem = $xmlDoc->createElement('container');
            } else {
                $ndItem = $xmlDoc->createElement('item');
            }
            $useRes     = false;
            $ndRes      = $xmlDoc->createElement('res');
            $ndRes_text = $xmlDoc->createTextNode($item['res']);
            $ndRes->appendChild($ndRes_text);

            // Add each element / attribute in $item array to item node:
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
                    case 'res':
                        break;
                    case 'duration':
                        $ndRes->setAttribute('duration', $value);
                        $useRes = true;
                        break;
                    case 'size':
                        $ndRes->setAttribute('size', $value);
                        $useRes = true;
                        break;
                    case 'bitrate':
                        $ndRes->setAttribute('bitrate', $value);
                        $useRes = true;
                        break;
                    case 'protocolInfo':
                        $ndRes->setAttribute('protocolInfo', $value);
                        $useRes = true;
                        break;
                    case 'resolution':
                        $ndRes->setAttribute('resolution', $value);
                        $useRes = true;
                        break;
                    case 'colorDepth':
                        $ndRes->setAttribute('colorDepth', $value);
                        $useRes = true;
                        break;
                    case 'sampleFrequency':
                        $ndRes->setAttribute('sampleFrequency', $value);
                        $useRes = true;
                        break;
                    case 'nrAudioChannels':
                        $ndRes->setAttribute('nrAudioChannels', $value);
                        $useRes = true;
                        break;
                    default:
                        $ndTag = $xmlDoc->createElement($key);
                        $ndItem->appendChild($ndTag);
                        // check if string is already utf-8 encoded
                        $ndTag_text = $xmlDoc->createTextNode((mb_detect_encoding($value, 'auto') == 'UTF-8')?$value:utf8_encode($value));
                        $ndTag->appendChild($ndTag_text);
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
     * @param string $prmQuery
     * @return array|null
     */
    public static function _musicMetadata($prmPath, $prmQuery = '')
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
                        $counts = Catalog::count_server();
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
                        $counts = Catalog::count_server();
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
                        $counts = Catalog::count_server();
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
                        $counts = Catalog::count_server();
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
                        $counts = Catalog::count_server();
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
                        $playlist = new Search($pathreq[1], 'song');
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
                        $counts = Catalog::count_server();
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
                        $counts = Catalog::count_server();
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
                    'childCount' => '5',
                    'dc:title' => T_('Music'),
                    'upnp:class' => 'object.container',
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
    private static function _slice($items, $start, $count)
    {
        $maxCount = count($items);
        debug_event('upnp_api.class', 'slice: ' . $maxCount . "   " . $start . "    " . $count, 5);

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

        $parent  = 'amp://music' . $prmPath;
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }

        switch ($pathreq[0]) {
            case 'artists':
                switch (count($pathreq)) {
                    case 1: // Get artists list
                        // $artists = Catalog::get_artists();
                        // list($maxCount, $artists) = self::_slice($artists, $start, $count);
                        $artists                  = Catalog::get_artists(null, $count, $start);
                        list($maxCount, $artists) = array(999999, $artists);
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
                        //!!$album_ids = Catalog::get_albums();
                        //!!list($maxCount, $album_ids) = self::_slice($album_ids, $start, $count);
                        $album_ids                  = Catalog::get_albums($count, $start);
                        list($maxCount, $album_ids) = array(999999, $album_ids);
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
                            $playlist = new Search($pl_id, 'song');
                            $playlist->format();
                            $mediaItems[] = self::_itemPlaylist($playlist, $parent);
                        }
                        break;
                    case 2: // Get playlist's songs list
                        $playlist = new Search($pathreq[1], 'song');
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
                break;
        }

        if ($maxCount == 0) {
            $maxCount = count($mediaItems);
        }

        return array($maxCount, $mediaItems);
    }

    /**
     * @param string $prmPath
     * @param string $prmQuery
     * @return array|null
     */
    public static function _videoMetadata($prmPath, $prmQuery = '')
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
                    'childCount' => '4',
                    'dc:title' => T_('Video'),
                    'upnp:class' => 'object.container',
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
     * @param $criteria
     * @return array
     */
    public static function _callSearch($criteria)
    {
        // Not supported yet
        return array();
    }

    /**
     * @param $title
     * @return string|string[]|null
     */
    private static function _replaceSpecialSymbols($title)
    {
        ///debug_event('upnp_api.class', 'replace <<< ' . $title, 5);
        // replace non letter or digits
        $title = preg_replace('~[^\\pL\d\.\s\(\)\.\,\'\"]+~u', '-', $title);
        ///debug_event('upnp_api.class', 'replace >>> ' . $title, 5);

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
            'restricted' => '1',
            'childCount' => $artist->albums,
            'dc:title' => self::_replaceSpecialSymbols($artist->f_name),
            'upnp:class' => 'object.container', // object.container.person.musicArtist
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
            'restricted' => '1',
            'childCount' => $album->song_count,
            'dc:title' => self::_replaceSpecialSymbols($album->f_title),
            'upnp:class' => 'object.container', // object.container.album.musicAlbum
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
            'restricted' => '1',
            'childCount' => count($playlist->get_items()),
            'dc:title' => self::_replaceSpecialSymbols($playlist->f_name),
            'upnp:class' => 'object.container', // object.container.playlistContainer
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
            'restricted' => '1',
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

        return array(
            'id' => 'amp://music/songs/' . $song->id,
            'parentID' => $parent,
            'restricted' => '1',
            'dc:title' => self::_replaceSpecialSymbols($song->f_title),
            'upnp:class' => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI' => $art_url,
            'upnp:artist' => self::_replaceSpecialSymbols($song->f_artist),
            'upnp:album' => self::_replaceSpecialSymbols($song->f_album),
            'upnp:genre' => Tag::get_display($song->tags, false, 'song'),
            //'dc:date'                   => date("c", (int) $song->addition_time),
            'upnp:originalTrackNumber' => $song->track,

            'res' => Song::play_url($song->id, '', 'api'),
            'protocolInfo' => $arrFileType['mime'],
            'size' => $song->size,
            'duration' => $song->f_time_h . '.0',
            'bitrate' => $song->bitrate,
            'sampleFrequency' => $song->rate,
            //'nrAudioChannels'           => '1',
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
            'restricted' => '1',
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

            'res' => Video::play_url($video->id, '', 'api'),
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
            $ret['res']          = Podcast_Episode::play_url($episode->id, '', 'api');
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
