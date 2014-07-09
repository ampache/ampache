<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
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
    # UPnP classes:
    # object.item.audioItem
    # object.item.imageItem
    # object.item.videoItem
    # object.item.playlistItem
    # object.item.textItem
    # object.container

    const UUIDSTR = '2d8a2e2b-7869-4836-a9ec-76447d620734';

    /**
     * constructor
     * This really isn't anything to do here, so it's private
     */
    private function __construct()
    {
    }

    private static function udpSend($buf, $delay=15, $host="239.255.255.250", $port=1900)
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_sendto($socket, $buf, strlen($buf), 0, $host, $port);
        socket_close($socket);
        usleep($delay*1000);
    }

    public static function sddpSend($delay=15, $host="239.255.255.250", $port=1900)
    {
        $strHeader  = 'NOTIFY * HTTP/1.1' . "\r\n";
        $strHeader .= 'HOST: ' . $host . ':' . $port . "\r\n";
        $strHeader .= 'LOCATION: http://' . AmpConfig::get('http_host') . ':'. AmpConfig::get('http_port') . AmpConfig::get('raw_web_path') . '/upnp/MediaServerServiceDesc.php' . "\r\n";
        $strHeader .= 'SERVER: DLNADOC/1.50 UPnP/1.0 Ampache/3.7' . "\r\n";
        $strHeader .= 'CACHE-CONTROL: max-age=1800' . "\r\n";
        $strHeader .= 'NTS: ssdp:alive' . "\r\n";
        $rootDevice = 'NT: upnp:rootdevice' . "\r\n";
        $rootDevice .= 'USN: uuid:' . self::UUIDSTR . '::upnp:rootdevice' . "\r\n". "\r\n";

        $buf = $strHeader . $rootDevice;
        self::udpSend($buf, $delay, $host, $port);

        $uuid = 'NT: uuid:' . self::UUIDSTR . "\r\n";
        $uuid .= 'USN: uuid:' . self::UUIDSTR . "\r\n". "\r\n";
        $buf = $strHeader . $uuid;
        self::udpSend($buf, $delay, $host, $port);

        $deviceType = 'NT: urn:schemas-upnp-org:device:MediaServer:1' . "\r\n";
        $deviceType .= 'USN: uuid:' . self::UUIDSTR . '::urn:schemas-upnp-org:device:MediaServer:1' . "\r\n". "\r\n";
        $buf = $strHeader . $deviceType;
        self::udpSend($buf, $delay, $host, $port);

        $serviceCM = 'NT: urn:schemas-upnp-org:service:ConnectionManager:1' . "\r\n";
        $serviceCM .= 'USN: uuid:' . self::UUIDSTR . '::urn:schemas-upnp-org:service:ConnectionManager:1' . "\r\n". "\r\n";
        $buf = $strHeader . $serviceCM;
        self::udpSend($buf, $delay, $host, $port);

        $serviceCD = 'NT: urn:schemas-upnp-org:service:ContentDirectory:1' . "\r\n";
        $serviceCD .= 'USN: uuid:' . self::UUIDSTR . '::urn:schemas-upnp-org:service:ContentDirectory:1' . "\r\n". "\r\n";
        $buf = $strHeader . $serviceCD;
        self::udpSend($buf, $delay, $host, $port);
    }

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
                        } # end if
                        break;
                    case 'BrowseFlag':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['browseflag'] = $reader->value;
                        } # end if
                        break;
                    case 'Filter':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['filter'] = $reader->value;
                        } # end if
                        break;
                    case 'StartingIndex':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['startingindex'] = $reader->value;
                        } # end if
                        break;
                    case 'RequestedCount':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['requestedcount'] = $reader->value;
                        } # end if
                        break;
                    case 'SearchCriteria':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                          $retArr['searchcriteria'] = $reader->value;
                        } # end if
                        break;
                    case 'SortCriteria':
                        $reader->read();
                        if ($reader->nodeType == XMLReader::TEXT) {
                            $retArr['sortcriteria'] = $reader->value;
                        } # end if
                        break;
                } # end switch
            } # end if
        } #end while
        return $retArr;
    } #end function


    public static function createDIDL($prmItems)
    {
        $xmlDoc = new DOMDocument('1.0', 'utf-8');
        $xmlDoc->formatOutput = true;

        # Create root element and add namespaces:
        $ndDIDL = $xmlDoc->createElementNS('urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/', 'DIDL-Lite');
        $ndDIDL->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $ndDIDL->setAttribute('xmlns:upnp', 'urn:schemas-upnp-org:metadata-1-0/upnp/');
        $xmlDoc->appendChild($ndDIDL);

        # Return empty DIDL if no items present:
        if ( (!isset($prmItems)) || (!is_array($prmItems)) ) {
            return $xmlDoc;
        }

        # Add each item in $prmItems array to $ndDIDL:
        foreach ($prmItems as $item) {
            if ($item['upnp:class']	== 'object.container') {
                $ndItem = $xmlDoc->createElement('container');
            } else {
                $ndItem = $xmlDoc->createElement('item');
            }
            $useRes = false;
            $ndRes = $xmlDoc->createElement('res');
            $ndRes_text = $xmlDoc->createTextNode($item['res']);
            $ndRes->appendChild($ndRes_text);

            # Add each element / attribute in $item array to item node:
            foreach ($item as $key => $value) {
                # Handle attributes. Better solution?
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
                        # check if string is already utf-8 encoded
                        $ndTag_text = $xmlDoc->createTextNode((mb_detect_encoding($value,'auto')=='UTF-8')?$value:utf8_encode($value));
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


    public static function createSOAPEnvelope($prmDIDL, $prmNumRet, $prmTotMatches, $prmResponseType = 'u:BrowseResponse', $prmUpdateID = '0')
    {
        # $prmDIDL is DIDL XML string
        # XML-Layout:
        #
        #		-s:Envelope
        #				-s:Body
        #						-u:BrowseResponse
        #								Result (DIDL)
        #								NumberReturned
        #								TotalMatches
        #								UpdateID
        #
        $doc  = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = true;
        $ndEnvelope = $doc->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 's:Envelope');
        $ndEnvelope->setAttribute("s:encodingStyle", "http://schemas.xmlsoap.org/soap/encoding/");
        $doc->appendChild($ndEnvelope);
        $ndBody = $doc->createElement('s:Body');
        $ndEnvelope->appendChild($ndBody);
        $ndBrowseResp = $doc->createElementNS('urn:schemas-upnp-org:service:ContentDirectory:1', $prmResponseType);
        $ndBody->appendChild($ndBrowseResp);
        $ndResult = $doc->createElement('Result',$prmDIDL);
        $ndBrowseResp->appendChild($ndResult);
        $ndNumRet = $doc->createElement('NumberReturned', $prmNumRet);
        $ndBrowseResp->appendChild($ndNumRet);
        $ndTotMatches = $doc->createElement('TotalMatches', $prmTotMatches);
        $ndBrowseResp->appendChild($ndTotMatches);
        $ndUpdateID = $doc->createElement('UpdateID', $prmUpdateID); # seems to be ignored by the WDTVL
        #$ndUpdateID = $doc->createElement('UpdateID', (string) mt_rand(); # seems to be ignored by the WDTVL
        $ndBrowseResp->appendChild($ndUpdateID);

        Return $doc;
    }

    public static function _musicMetadata($prmPath, $prmQuery = '')
    {
        $root = 'amp://music';
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }

        $meta = null;
        switch ($pathreq[0]) {
            case 'artists':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::count_medias();
                        $meta = array(
                            'id'			=> $root . '/artists',
                            'parentID'		=> $root,
                            'restricted'    => '1',
                            'childCount'	=> $counts['artists'],
                            'dc:title'		=> T_('Artists'),
                            'upnp:class'	=> 'object.container',
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
                        $counts = Catalog::count_medias();
                        $meta = array(
                            'id'			=> $root . '/albums',
                            'parentID'		=> $root,
                            'restricted'    => '1',
                            'childCount'	=> $counts['albums'],
                            'dc:title'		=> T_('Albums'),
                            'upnp:class'	=> 'object.container',
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
                        $counts = Catalog::count_medias();
                        $meta = array(
                            'id'			=> $root . '/songs',
                            'parentID'		=> $root,
                            'restricted'    => '1',
                            'childCount'	=> $counts['songs'],
                            'dc:title'		=> T_('Songs'),
                            'upnp:class'	=> 'object.container',
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
                        $counts = Catalog::count_medias();
                        $meta = array(
                            'id'			=> $root . '/playlists',
                            'parentID'		=> $root,
                            'restricted'    => '1',
                            'childCount'	=> $counts['playlists'],
                            'dc:title'		=> T_('Playlists'),
                            'upnp:class'	=> 'object.container',
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
                        $counts = Catalog::count_medias();
                        $meta = array(
                            'id'			=> $root . '/smartplaylists',
                            'parentID'		=> $root,
                            'restricted'    => '1',
                            'childCount'	=> $counts['smartplaylists'],
                            'dc:title'		=> T_('Smart Playlists'),
                            'upnp:class'	=> 'object.container',
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

            default:
                $meta = array(
                    'id'			=> $root,
                    'parentID'		=> '0',
                    'restricted'    => '1',
                    'childCount'    => '5',
                    'dc:title'		=> T_('Music'),
                    'upnp:class'	=> 'object.container',
                );
            break;
        }

        return $meta;
    }

    public static function _musicChilds($prmPath, $prmQuery)
    {
        $mediaItems = array();
        $queryData = array();
        parse_str($prmQuery, $queryData);

        $parent = 'amp://music' . $prmPath;
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }

        switch ($pathreq[0]) {
            case 'artists':
                switch (count($pathreq)) {
                    case 1: // Get artists list
                        $artists = Catalog::get_artists();
                        foreach ($artists as $artist) {
                            $artist->format();
                            $mediaItems[] = self::_itemArtist($artist, $parent);
                        }
                    break;
                    case 2: // Get artist's albums list
                        $artist = new Artist($pathreq[1]);
                        if ($artist->id) {
                            $album_ids = $artist->get_albums();
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
                        $album_ids = Catalog::get_albums();
                        foreach ($album_ids as $album_id) {
                            $album = new Album($album_id);
                            $album->format();
                            $mediaItems[] = self::_itemAlbum($album, $parent);
                        }
                    break;
                    case 2: // Get album's songs list
                        $album = new Album($pathreq[1]);
                        if ($album->id) {
                            $song_ids = $album->get_songs();
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
                            $catalog = Catalog::create_from_id($catalog_id);
                            $songs = $catalog->get_songs();
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
                        $pl_ids = Playlist::get_playlists();
                        foreach ($pl_ids as $pl_id) {
                            $playlist = new Playlist($pl_id);
                            $playlist->format();
                            $mediaItems[] = self::_itemPlaylist($playlist, $parent);
                        }
                    break;
                    case 2: // Get playlist's songs list
                        $playlist = new Playlist($pathreq[1]);
                        if ($playlist->id) {
                            $items = $playlist->get_items();
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
                        $pl_ids = Search::get_searches();
                        foreach ($pl_ids as $pl_id) {
                            $playlist = new Search($pl_id, 'song');
                            $playlist->format();
                            $mediaItems[] = self::_itemPlaylist($playlist, $parent);
                        }
                    break;
                    case 2: // Get playlist's songs list
                        $playlist = new Search($pathreq[1], 'song');
                        if ($playlist->id) {
                            $items = $playlist->get_items();
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

            default:
                $mediaItems[] = self::_musicMetadata('artists');
                $mediaItems[] = self::_musicMetadata('albums');
                $mediaItems[] = self::_musicMetadata('songs');
                $mediaItems[] = self::_musicMetadata('playlists');
                $mediaItems[] = self::_musicMetadata('smartplaylists');
            break;
        }

        return $mediaItems;
    }

    public static function _videoMetadata($prmPath, $prmQuery = '')
    {
        $root = 'amp://video';
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
                        $meta = array(
                            'id'			=> $root . '/tvshows',
                            'parentID'		=> $root,
                            'restricted'    => '1',
                            'childCount'	=> $counts,
                            'dc:title'		=> T_('TV Shows'),
                            'upnp:class'	=> 'object.container',
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
                            $meta = self::_itemVideo($video, $root . '/tvshows/' . $pathreq[1] . '/' . $pathreq[2] );
                        }
                    break;
                }
            break;

            case 'clips':
                switch (count($pathreq)) {
                    case 1:
                        $counts = Catalog::get_videos_count(null, 'clip');
                        $meta = array(
                            'id'			=> $root . '/clips',
                            'parentID'		=> $root,
                            'restricted'    => '1',
                            'childCount'	=> $counts,
                            'dc:title'		=> T_('Clips'),
                            'upnp:class'	=> 'object.container',
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
                        $meta = array(
                            'id'			=> $root . '/movies',
                            'parentID'		=> $root,
                            'restricted'    => '1',
                            'childCount'	=> $counts,
                            'dc:title'		=> T_('Movies'),
                            'upnp:class'	=> 'object.container',
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
                        $meta = array(
                            'id'			=> $root . '/personal_videos',
                            'parentID'		=> $root,
                            'restricted'    => '1',
                            'childCount'	=> $counts,
                            'dc:title'		=> T_('Personal Videos'),
                            'upnp:class'	=> 'object.container',
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
                    'id'			=> $root,
                    'parentID'		=> '0',
                    'restricted'    => '1',
                    'childCount'    => '4',
                    'dc:title'		=> T_('Video'),
                    'upnp:class'	=> 'object.container',
                );
            break;
        }

        return $meta;
    }

    public static function _videoChilds($prmPath, $prmQuery)
    {
        $mediaItems = array();
        $queryData = array();
        parse_str($prmQuery, $queryData);

        $parent = 'amp://video' . $prmPath;
        $pathreq = explode('/', $prmPath);
        if ($pathreq[0] == '' && count($pathreq) > 0) {
            array_shift($pathreq);
        }

        switch ($pathreq[0]) {
            case 'tvshows':
                switch (count($pathreq)) {
                    case 1: // Get tvshow list
                        $tvshows = Catalog::get_tvshows();
                        foreach ($tvshows as $tvshow) {
                            $tvshow->format();
                            $mediaItems[] = self::_itemTVShow($tvshow, $parent);
                        }
                    break;
                    case 2: // Get season list
                        $tvshow = new TVShow($pathreq[1]);
                        if ($tvshow->id) {
                            $season_ids = $tvshow->get_seasons();
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
                            $episode_ids = $season->get_episodes();
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
                        $videos = Catalog::get_videos(null, 'clip');
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
                        $videos = Catalog::get_videos(null, 'movie');
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
                        $videos = Catalog::get_videos(null, 'personal_video');
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

        return $mediaItems;
    }

    public static function _callSearch($criteria)
    {
        // Not supported yet
        return array();
    }

    private static function _itemArtist($artist, $parent)
    {
        return array(
            'id'			=> 'amp://music/artists/' . $artist->id,
            'parentID'		=> $parent,
            'restricted'    => '1',
            'childCount'	=> $artist->albums,
            'dc:title'		=> $artist->f_name,
            'upnp:class'	=> 'object.container',   // object.container.person.musicArtist
        );
    }

    private static function _itemAlbum($album, $parent)
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::$session : false;
        $art_url = Art::url($album->id, 'album', $api_session);

        return array(
            'id'			    => 'amp://music/albums/' . $album->id,
            'parentID'		    => $parent,
            'restricted'    => '1',
            'childCount'	    => $album->song_count,
            'dc:title'		    => $album->f_title,
            'upnp:class'	    => 'object.container',  // object.container.album.musicAlbum
            'upnp:albumArtURI'  => $art_url,
        );
    }

    private static function _itemPlaylist($playlist, $parent)
    {
        return array(
            'id'			=> 'amp://music/playlists/' . $playlist->id,
            'parentID'		=> $parent,
            'restricted'    => '1',
            'childCount'	=> count($playlist->get_items()),
            'dc:title'		=> $playlist->f_name,
            'upnp:class'	=> 'object.container',  // object.container.playlistContainer
        );
    }

    private static function _itemSmartPlaylist($playlist, $parent)
    {
        return array(
            'id'			=> 'amp://music/smartplaylists/' . $playlist->id,
            'parentID'		=> $parent,
            'restricted'    => '1',
            'childCount'	=> count($playlist->get_items()),
            'dc:title'		=> $playlist->f_name,
            'upnp:class'	=> 'object.container',
        );
    }

    private static function _itemSong($song, $parent)
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::$session : false;
        $art_url = Art::url($song->album, 'album', $api_session);

        $fileTypesByExt = self::_getFileTypes();
        $arrFileType = $fileTypesByExt[$song->type];

        return array(
            'id'			            => 'amp://music/songs/' . $song->id,
            'parentID'		            => $parent,
            'restricted'                => '1',
            'dc:title'		            => $song->f_title,
            'upnp:class'	            => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI'          => $art_url,
            'upnp:artist'               => $song->f_artist,
            'upnp:album'                => $song->f_album,
            'upnp:genre'                => Tag::get_display($song->tags, false, 'song'),
            //'dc:date'                   => date("c", $song->addition_time),
            'upnp:originalTrackNumber'    => $song->track,

            'res'                       => Song::play_url($song->id),
            'protocolInfo'              => $arrFileType['mime'],
            'size'                      => $song->size,
            'duration'                  => $song->f_time_h . '.0',
            'bitrate'                   => $song->bitrate,
            'sampleFrequency'           => $song->rate,
            //'nrAudioChannels'           => '1',
            'description'               => $song->comment,
        );
    }

    private static function _itemTVShow($tvshow, $parent)
    {
        return array(
            'id'			=> 'amp://video/tvshows/' . $tvshow->id,
            'parentID'		=> $parent,
            'restricted'    => '1',
            'childCount'	=> count($tvshow->get_seasons()),
            'dc:title'		=> $tvshow->f_name,
            'upnp:class'	=> 'object.container',
        );
    }

    private static function _itemTVShowSeason($season, $parent)
    {
        return array(
            'id'			=> 'amp://video/tvshows/' . $season->tvshow . '/' . $season->id,
            'parentID'		=> $parent,
            'restricted'    => '1',
            'childCount'	=> count($season->get_episodes()),
            'dc:title'		=> $season->f_name,
            'upnp:class'	=> 'object.container',
        );
    }

    private static function _itemVideo($video, $parent)
    {
        $api_session = (AmpConfig::get('require_session')) ? Stream::$session : false;
        $art_url = Art::url($video->id, 'video', $api_session);

        $fileTypesByExt = self::_getFileTypes();
        $arrFileType = $fileTypesByExt[$video->type];

        return array(
            'id'			            => $parent . '/' . $video->id,
            'parentID'		            => $parent,
            'restricted'                => '1',
            'dc:title'		            => $video->f_title,
            'upnp:class'	            => (isset($arrFileType['class'])) ? $arrFileType['class'] : 'object.item.unknownItem',
            'upnp:albumArtURI'          => $art_url,
            'upnp:genre'                => Tag::get_display($video->tags, false, 'video'),

            'res'                       => Video::play_url($video->id),
            'protocolInfo'              => $arrFileType['mime'],
            'size'                      => $video->size,
            'duration'                  => $video->f_time_h . '.0',
        );
    }

    private static function _getFileTypes()
    {
        return array(
            'wav' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/x-wav:*',
            ),
            'mpa' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/mpeg:*',
            ),
            '.mp1' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/mpeg:*',
            ),
            'mp3' => array(
                'class' => 'object.item.audioItem.musicTrack',
                'mime' => 'http-get:*:audio/mpeg:*',
            ),
            'aiff' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/x-aiff:*',
            ),
            'aif' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/x-aiff:*',
            ),
            'wma' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/x-ms-wma:*',
            ),
            'lpcm' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/lpcm:*',
            ),
            'aac' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/x-aac:*',
            ),
            'm4a' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/x-m4a:*',
            ),
            'ac3' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/x-ac3:*',
            ),
            'pcm' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/lpcm:*',
            ),
            'flac' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/flac:*',
            ),
            'ogg' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:application/ogg:*',
            ),
            'mka' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/x-matroska:*',
            ),
            'mp4a' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/x-m4a:*',
            ),
            'mp2' => array(
                'class' => 'object.item.audioItem',
                'mime' => 'http-get:*:audio/mpeg:*',
            ),
            'gif' => array(
                'class' => 'object.item.imageItem',
                'mime' => 'http-get:*:image/gif:*',
            ),
            'jpg' => array(
                'class' => 'object.item.imageItem',
                'mime' => 'http-get:*:image/jpeg:*',
            ),
            'jpe' => array(
                'class' => 'object.item.imageItem',
                'mime' => 'http-get:*:image/jpeg:*',
            ),
            'png' => array(
                'class' => 'object.item.imageItem',
                'mime' => 'http-get:*:image/png:*',
            ),
            'tiff' => array(
                'class' => 'object.item.imageItem',
                'mime' => 'http-get:*:image/tiff:*',
            ),
            'tif' => array(
                'class' => 'object.item.imageItem',
                'mime' => 'http-get:*:image/tiff:*',
            ),
            'jpeg' => array(
                'class' => 'object.item.imageItem',
                'mime' => 'http-get:*:image/jpeg:*',
            ),
            'bmp' => array(
                'class' => 'object.item.imageItem',
                'mime' => 'http-get:*:image/bmp:*',
            ),
            'asf' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/x-ms-asf:*',
            ),
            'wmv' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/x-ms-wmv:*',
            ),
            'mpeg2' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2:*',
            ),
            'avi' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/x-msvideo:*',
            ),
            'divx' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/x-msvideo:*',
            ),
            'mpg' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg:*',
            ),
            'm1v' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg:*',
            ),
            'm2v' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg:*',
            ),
            'mp4' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mp4:*',
            ),
            'mov' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/quicktime:*',
            ),
            'vob' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/dvd:*',
            ),
            'dvr-ms' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/x-ms-dvr:*',
            ),
            'dat' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg:*',
            ),
            'mpeg' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg:*',
            ),
            'm1s' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg:*',
            ),
            'm2p' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2:*',
            ),
            'm2t' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2ts:*',
            ),
            'm2ts' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2ts:*',
            ),
            'mts' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2ts:*',
            ),
            'ts' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2ts:*',
            ),
            'tp' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2ts:*',
            ),
            'trp' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2ts:*',
            ),
            'm4t' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2ts:*',
            ),
            'm4v' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/MP4V-ES:*',
            ),
            'vbs' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2:*',
            ),
            'mod' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mpeg2:*',
            ),
            'mkv' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/x-matroska:*',
            ),
            '3g2' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mp4:*',
            ),
            '3gp' => array(
                'class' => 'object.item.videoItem',
                'mime' => 'http-get:*:video/mp4:*',
            ),
        );
    }
}
