<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Plex_XML_Data Class
 *
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls
 *
 */
class Plex_XML_Data
{
    // Ampache doesn't have a global unique id but each items are unique per category. We use id pattern to identify item category.
    const AMPACHEID_ARTIST        = 10000000;
    const AMPACHEID_ALBUM         = 20000000;
    const AMPACHEID_TRACK         = 30000000;
    const AMPACHEID_SONG          = 40000000;
    const AMPACHEID_TVSHOW        = 50000000;
    const AMPACHEID_TVSHOW_SEASON = 60000000;
    const AMPACHEID_VIDEO         = 70000000;
    const AMPACHEID_PLAYLIST      = 80000000;
    const AMPACHEID_PART          = 100000000;

    const PLEX_ARTIST   = 8;
    const PLEX_ALBUM    = 9;
    const PLEX_TVSHOW   = 2;
    const PLEX_SEASON   = 3;
    const PLEX_EPISODE  = 4;
    const PLEX_MOVIE    = 1;
    const PLEX_PLAYLIST = 15;

    /**
     * constructor
     *
     * We don't use this, as its really a static class
     */
    private function __construct()
    {
    }

    public static function getArtistId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_ARTIST;
    }

    public static function getAlbumId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_ALBUM;
    }

    public static function getTrackId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_TRACK;
    }

    public static function getSongId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_SONG;
    }

    public static function getPartId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_PART;
    }

    public static function getTVShowId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_TVSHOW;
    }

    public static function getTVShowSeasonId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_TVSHOW_SEASON;
    }

    public static function getVideoId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_VIDEO;
    }

    public static function getPlaylistId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_PLAYLIST;
    }

    public static function getAmpacheId($id)
    {
        return ($id % Plex_XML_Data::AMPACHEID_ARTIST);
    }

    public static function getAmpacheIds($ids)
    {
        $ampids = array();
        foreach ($ids as $id) {
            $ampids[] = self::getAmpacheId($id);
        }
        return $ampids;
    }

    public static function isArtist($id)
    {
        $id = self::clearItemId($id);
        return ($id >= Plex_XML_Data::AMPACHEID_ARTIST && $id < Plex_XML_Data::AMPACHEID_ALBUM);
    }

    public static function isAlbum($id)
    {
        $id = self::clearItemId($id);
        return ($id >= Plex_XML_Data::AMPACHEID_ALBUM && $id < Plex_XML_Data::AMPACHEID_TRACK);
    }

    public static function isTrack($id)
    {
        $id = self::clearItemId($id);
        return ($id >= Plex_XML_Data::AMPACHEID_TRACK  && $id < Plex_XML_Data::AMPACHEID_SONG);
    }

    public static function isSong($id)
    {
        $id = self::clearItemId($id);
        return ($id >= Plex_XML_Data::AMPACHEID_SONG  && $id < Plex_XML_Data::AMPACHEID_TVSHOW);
    }

    public static function isTVShow($id)
    {
        $id = self::clearItemId($id);
        return ($id >= Plex_XML_Data::AMPACHEID_TVSHOW  && $id < Plex_XML_Data::AMPACHEID_TVSHOW_SEASON);
    }

    public static function isTVShowSeason($id)
    {
        $id = self::clearItemId($id);
        return ($id >= Plex_XML_Data::AMPACHEID_TVSHOW_SEASON  && $id < Plex_XML_Data::AMPACHEID_VIDEO);
    }

    public static function isVideo($id)
    {
        $id = self::clearItemId($id);
        return ($id >= Plex_XML_Data::AMPACHEID_VIDEO && $id < Plex_XML_Data::AMPACHEID_PLAYLIST);
    }

    public static function isPlaylist($id)
    {
        $id = self::clearItemId($id);
        return ($id >= Plex_XML_Data::AMPACHEID_PLAYLIST);
    }

    public static function clearItemId($id)
    {
        return ($id % Plex_XML_Data::AMPACHEID_PART);
    }

    public static function isPart($id)
    {
        return ($id >= Plex_XML_Data::AMPACHEID_PART);
    }

    public static function getPlexVersion()
    {
        return "0.9.9.13.525-197d5ed";
    }

    public static function getServerAddress()
    {
        return $_SERVER['SERVER_ADDR'];
    }

    public static function getServerPort()
    {
        $port = $_SERVER['SERVER_PORT'];
        return $port?:'32400';
    }

    public static function getServerPublicAddress()
    {
        $address = AmpConfig::get('plex_public_address');
        if (!$address) {
            $address = self::getServerAddress();
        }
        return $address;
    }

    public static function getServerPublicPort()
    {
        $port = AmpConfig::get('plex_public_port');
        if (!$port) {
            $port = self::getServerPort();
        }
        return $port;
    }

    public static function getServerUri()
    {
        return 'http://' . self::getServerPublicAddress() . ':' . self::getServerPublicPort();
    }

    public static function getServerName()
    {
        return AmpConfig::get('plex_servername') ?: 'Ampache';
    }

    public static function getMyPlexUsername()
    {
        return AmpConfig::get('myplex_username');
    }

    public static function getMyPlexAuthToken()
    {
        return AmpConfig::get('myplex_authtoken');
    }

    public static function getMyPlexPublished()
    {
        return AmpConfig::get('myplex_published');
    }

    public static function createContainer()
    {
        $response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><MediaContainer/>');
        return $response;
    }

    public static function createLibContainer()
    {
        $response = self::createContainer();
        $response->addAttribute('identifier', 'com.plexapp.plugins.library');
        $response->addAttribute('mediaTagPrefix', '/system/bundle/media/flags/');
        $response->addAttribute('mediaTagVersion', '1382796976');
        return $response;
    }

    public static function createPluginContainer()
    {
        $response = self::createContainer();
        $response->addAttribute('content', 'plugins');
        return $response;
    }

    public static function createSysContainer()
    {
        $response = self::createContainer();
        $response->addAttribute('noHistory', '0');
        $response->addAttribute('replaceParent', '0');
        $response->addAttribute('identifier', 'com.plexapp.system');
        return $response;
    }

    public static function createAccountContainer()
    {
        $response = self::createContainer();
        $response->addAttribute('identifier', 'com.plexapp.system.accounts');
        return $response;
    }

    public static function setContainerSize($container)
    {
        $container->addAttribute('size', $container->count());
    }

    public static function setContainerTitle($container, $title)
    {
        $container->addAttribute('title1', $title);
    }

    public static function getResourceUri($resource)
    {
        return '/resources/' . $resource;
    }

    public static function getMetadataUri($key)
    {
        return '/library/metadata/' . $key;
    }

    public static function getKeyFromMetadataUri($uri)
    {
        $up = '/library/metadata/';
        return substr($uri, strlen($up));
    }

    public static function getKeyFromFullUri($uri)
    {
        $key  = '';
        if (strpos($uri, "library://") === 0) {
            // We ignore library uuid
            $ppath = explode('/', substr($uri, 10));
            if (count($ppath) == 3) {
                if ($ppath['1'] == 'item') {
                    $key = rawurldecode($ppath[2]);
                }
            }
        }
        return $key;
    }

    public static function getSectionUri($key)
    {
        return '/library/sections/' . $key;
    }

    public static function getPartUri($key, $type)
    {
        return '/library/parts/' . $key . '/file.' . $type;
    }

    public static function uuidFromKey($key)
    {
        return hash('sha1', $key);
    }

    public static function uuidFromSubKey($key)
    {
        return self::uuidFromKey(self::getMachineIdentifier() . '-' . $key);
    }

    public static function getMachineIdentifier()
    {
        $uniqid = AmpConfig::get('plex_uniqid');
        if (!$uniqid) {
            $uniqid = self::getServerAddress();
        }
        return self::uuidFromKey($uniqid);
    }

    public static function getClientIdentifier()
    {
        return self::getMachineIdentifier();
    }

    public static function getPlexPlatform()
    {
        if (PHP_OS == 'WINNT') {
            return 'Windows';
        } else {
            return "Linux";
        }
    }

    public static function getPlexPlatformVersion()
    {
        if (PHP_OS == 'WINNT') {
            return '6.2 (Build 9200)';
        } else {
            return '(#1 SMP Debian 3.2.54-2)';
        }
    }

    public static function setRootContent(SimpleXMLElement $xml, $catalogs)
    {
        $xml->addAttribute('friendlyName', self::getServerName());
        $xml->addAttribute('machineIdentifier', self::getMachineIdentifier());

        $myplex_username  = self::getMyPlexUsername();
        $myplex_authtoken = self::getMyPlexAuthToken();
        $myplex_published = self::getMyPlexPublished();
        if ($myplex_username) {
            $xml->addAttribute('multiuser', '1');
            $xml->addAttribute('myPlex', '1');
            $xml->addAttribute('myPlexUsername', $myplex_username);
            if ($myplex_authtoken) {
                $xml->addAttribute('myPlexSigninState', 'ok');
                if ($myplex_published) {
                    $xml->addAttribute('myPlexMappingState', 'mapped');
                } else {
                    $xml->addAttribute('myPlexMappingState', 'unknown');
                }
            } else {
                $xml->addAttribute('myPlexSigninState', 'none');
            }
        } else {
            $xml->addAttribute('myPlex', '0');
        }

        $xml->addAttribute('platform', self::getPlexPlatform());
        $xml->addAttribute('platformVersion', self::getPlexPlatformVersion());
        $xml->addAttribute('requestParametersInCookie', '1');
        $xml->addAttribute('sync', '1');
        $xml->addAttribute('transcoderAudio', '1');
        $xml->addAttribute('transcoderActiveVideoSessions', AmpConfig::get('allow_video') ? '1' : '0');
        $xml->addAttribute('transcoderVideo', AmpConfig::get('allow_video') ? '1' : '0');
        if (AmpConfig::get('allow_video')) {
            $xml->addAttribute('transcoderVideoBitrates', '64,96,208,320,720,1500,2000,3000,4000,8000,10000,12000,20000');
            $xml->addAttribute('transcoderVideoQualities', '0,1,2,3,4,5,6,7,8,9,10,11,12');
            $xml->addAttribute('transcoderVideoResolutions', '128,128,160,240,320,480,768,720,720,1080,1080,1080,1080');
            //$xml->addAttributes('transcoderActiveVideoSessions', '0');
        }

        $xml->addAttribute('updatedAt', Catalog::getLastUpdate($catalogs));
        $xml->addAttribute('version', self::getPlexVersion());

        /*$dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'channels');
        $dir->addAttribute('title', 'channels');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'clients');
        $dir->addAttribute('title', 'clients');*/
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'library');
        $dir->addAttribute('title', 'library');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'music');
        $dir->addAttribute('title', 'music');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'playQueues');
        $dir->addAttribute('title', 'playQueues');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'player');
        $dir->addAttribute('title', 'player');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'playlists');
        $dir->addAttribute('title', 'playlists');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'search');
        $dir->addAttribute('title', 'search');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'servers');
        $dir->addAttribute('title', 'servers');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'system');
        $dir->addAttribute('title', 'system');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'transcode');
        $dir->addAttribute('title', 'transcode');
        /*$dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'video');
        $dir->addAttribute('title', 'video');*/
    }

    public static function setSysSections(SimpleXMLElement $xml, $catalogs)
    {
        foreach ($catalogs as $id) {
            $catalog = Catalog::create_from_id($id);
            if ($catalog) {
                $catalog->format();

                $dir = $xml->addChild('Directory');
                $key = base64_encode(self::getMachineIdentifier() . '-' . $id);
                $dir->addAttribute('type', 'music');
                $dir->addAttribute('key', $key);
                $dir->addAttribute('uuid', self::uuidFromSubKey($id));
                $dir->addAttribute('name', $catalog->name);
                $dir->addAttribute('unique', '1');
                $dir->addAttribute('serverVersion', self::getPlexVersion());
                $dir->addAttribute('machineIdentifier', self::getMachineIdentifier());
                $dir->addAttribute('serverName', self::getServerName());
                $dir->addAttribute('path', self::getSectionUri($id));
                $ip   = self::getServerAddress();
                $port = self::getServerPort();
                $dir->addAttribute('host', $ip);
                $dir->addAttribute('local', ($ip == "127.0.0.1") ? '1' : '0');
                $dir->addAttribute('port', $port);
                self::setSectionXContent($dir, $catalog, 'artist', 'title');
            }
        }
    }

    public static function setSections(SimpleXMLElement $xml, $catalogs)
    {
        foreach ($catalogs as $id) {
            $catalog = Catalog::create_from_id($id);
            if ($catalog) {
                $catalog->format();
                $dir = $xml->addChild('Directory');
                $dir->addAttribute('filters', '1');
                $dir->addAttribute('refreshing', '0');
                $dir->addAttribute('key', $id);
                $gtypes = $catalog->get_gather_types();
                switch ($gtypes[0]) {
                    case 'movie':
                        $stype = 'movie';
                        $dir->addAttribute('type', 'movie');
                        $dir->addAttribute('agent', 'com.plexapp.agents.imdb');
                        $dir->addAttribute('scanner', 'Ampache Movie Scanner');
                        break;
                    case 'tvshow':
                        $stype = 'show';
                        $dir->addAttribute('type', 'show');
                        $dir->addAttribute('agent', 'com.plexapp.agents.thetvdb');
                        $dir->addAttribute('scanner', 'Ampache Series Scanner');
                        break;
                    case 'music':
                    default:
                        $stype = 'artist';
                        $dir->addAttribute('type', 'artist');
                        $dir->addAttribute('agent', 'com.plexapp.agents.none'); // com.plexapp.agents.lastfm
                        $dir->addAttribute('scanner', 'Ampache Music Scanner');
                        break;
                }
                $dir->addAttribute('language', 'en');
                $dir->addAttribute('uuid', self::uuidFromSubKey($id));
                $dir->addAttribute('updatedAt', Catalog::getLastUpdate($catalogs));
                self::setSectionXContent($dir, $catalog, $stype, 'title');
                //$date = new DateTime("2013-01-01");
                //$dir->addAttribute('createdAt', $date->getTimestamp());

                $location = $dir->addChild('Location');
                $location->addAttribute('id', $id);
                $location->addAttribute('path', $catalog->f_name);
            }
        }

        $xml->addAttribute('allowSync', '0');
        self::setContainerTitle($xml, 'Plex Library');
    }

    public static function setLibraryContent(SimpleXMLElement $xml)
    {
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'sections');
        $dir->addAttribute('title', 'Library Sections');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'recentlyAdded');
        $dir->addAttribute('title', 'Recently Added Content');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'onDeck');
        $dir->addAttribute('title', 'On Deck Content');

        $xml->addAttribute('allowSync', '0');
        self::setContainerTitle($xml, 'Plex Library');
    }

    public static function setSectionContent(SimpleXMLElement $xml, Catalog $catalog)
    {
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'all');
        $dir->addAttribute('title', 'All Artists');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'albums');
        $dir->addAttribute('title', 'By Albums');
        /*$dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'genre');
        $dir->addAttribute('secondary', '1');
        $dir->addAttribute('title', 'By Genre');*/
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'recentlyAdded');
        $dir->addAttribute('title', 'Recently Added');

        /*$dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'search?type=8');
        $dir->addAttribute('prompt', 'Search for Artists');
        $dir->addAttribute('search', '1');
        $dir->addAttribute('title', 'Search Artists...');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'search?type=9');
        $dir->addAttribute('prompt', 'Search for Albums');
        $dir->addAttribute('search', '1');
        $dir->addAttribute('title', 'Search Albums...');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'search?type=10');
        $dir->addAttribute('prompt', 'Search for Tracks');
        $dir->addAttribute('search', '1');
        $dir->addAttribute('title', 'Search Tracks...');*/

        $xml->addAttribute('allowSync', '0');
        $xml->addAttribute('content', 'secondary');
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('viewGroup', 'secondary');
        $xml->addAttribute('viewMode', '65592');
        self::setSectionXContent($xml, $catalog, 'artist');
    }

    public static function setSectionXContent(SimpleXMLElement $xml, Catalog $catalog, $stype = 'artist', $title = 'title1')
    {
        switch ($stype) {
            case 'show':
            case 'season':
            case 'episode':
                $type = 'show';
                break;
            case 'movie':
                $type = 'movie';
                break;
            default:
                $type = 'artist';
                break;
        }

        $xml->addAttribute('art', self::getResourceUri($type . '-fanart.jpg'));
        $xml->addAttribute('thumb', self::getResourceUri($type . '.png'));
        $xml->addAttribute($title, $catalog->name);
    }

    public static function setSystemContent(SimpleXMLElement $xml)
    {
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'plexonline');
        $dir->addAttribute('title', 'Channel Directory');
        $dir->addAttribute('name', 'Channel Directory');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'help');
        $dir->addAttribute('title', 'Plex Help');
        $dir->addAttribute('name', 'Plex Help');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'library');
        $dir->addAttribute('title', 'Library Sections');
        $dir->addAttribute('name', 'Library Sections');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'plugins');
        $dir->addAttribute('title', 'Plug-ins');
        $dir->addAttribute('name', 'Plug-ins');
    }

    protected static function setSectionAllAttributes(SimpleXMLElement $xml, Catalog $catalog, $title2, $viewGroup)
    {
        $xml->addAttribute('allowSync', '1');
        self::setSectionXContent($xml, $catalog, $viewGroup);
        $xml->addAttribute('title2', $title2);
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('viewGroup', $viewGroup);
        $xml->addAttribute('viewMode', '65592');
        self::addCatalogIdentity($xml, $catalog);
    }

    public static function setSectionAll_Artists(SimpleXMLElement $xml, Catalog $catalog)
    {
        self::setSectionAllAttributes($xml, $catalog, 'All Artists', 'artist');

        $artists = Catalog::get_artists(array($catalog->id));
        foreach ($artists as $artist) {
            self::addArtist($xml, $artist);
        }
    }

    public static function setSectionAll_Albums(SimpleXMLElement $xml, Catalog $catalog)
    {
        self::setSectionAllAttributes($xml, $catalog, 'All Albums', 'album');

        $albums_ids = Catalog::get_albums(0, 0, array($catalog->id));
        foreach ($albums_ids as $album_id) {
            $album = new Album($album_id);
            $album->format();
            self::addAlbum($xml, $album);
        }
    }

    public static function setSectionAll_TVShows(SimpleXMLElement $xml, Catalog $catalog)
    {
        self::setSectionAllAttributes($xml, $catalog, 'All Shows', 'show');

        $shows = Catalog::get_tvshows(array($catalog->id));
        foreach ($shows as $show) {
            $show->format();
            self::addTVShow($xml, $show);
        }
    }

    public static function setSectionAll_Seasons(SimpleXMLElement $xml, Catalog $catalog)
    {
        self::setSectionAllAttributes($xml, $catalog, 'All Seasons', 'season');

        $shows = Catalog::get_tvshows(array($catalog->id));
        foreach ($shows as $show) {
            $seasons = $show->get_seasons();
            foreach ($seasons as $season_id) {
                $season = new TVShow_Season($season_id);
                $season->format();
                self::addTVShowSeason($xml, $season);
            }
        }
    }

    public static function setSectionAll_Episodes(SimpleXMLElement $xml, Catalog $catalog)
    {
        self::setSectionAllAttributes($xml, $catalog, 'All Episodes', 'episode');

        $shows = Catalog::get_tvshows(array($catalog->id));
        foreach ($shows as $show) {
            $episodes = $show->get_episodes();
            foreach ($episodes as $episode_id) {
                $episode = new TVShow_Episode($episode_id);
                $episode->format();
                self::addEpisode($xml, $episode);
            }
        }
    }

    public static function setSectionAll_Movies(SimpleXMLElement $xml, Catalog $catalog)
    {
        self::setSectionAllAttributes($xml, $catalog, 'All Movies', 'movie');

        $movies = Catalog::get_videos(array($catalog->id), 'movie');
        foreach ($movies as $movie) {
            $movie->format();
            self::addMovie($xml, $movie);
        }
    }

    public static function setSectionAlbums(SimpleXMLElement $xml, $catalog)
    {
        $albums = $catalog->get_album_ids();

        $xml->addAttribute('allowSync', '0');
        self::setSectionXContent($xml, $catalog, 'album');
        $xml->addAttribute('title2', 'By Album');
        $xml->addAttribute('mixedParents', '1');
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('viewGroup', 'album');
        $xml->addAttribute('viewMode', '65592');

        foreach ($albums as $id) {
            $album = new Album($id);
            $album->format();
            self::addAlbum($xml, $album);
        }
    }

    public static function setCustomSectionView(SimpleXMLElement $xml, $catalog, $albums)
    {
        $xml->addAttribute('allowSync', '1');
        self::setSectionXContent($xml, $catalog, 'album');
        $xml->addAttribute('title2', 'Recently Added');
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('mixedParents', '1');
        $xml->addAttribute('viewGroup', 'album');
        $xml->addAttribute('viewMode', '65592');
        self::addCatalogIdentity($xml, $catalog);
        self::setSectionXContent($xml, $catalog, 'album');

        $data          = array();
        $data['album'] = $albums;
        self::_setCustomView($xml, $data);
    }

    public static function setCustomView(SimpleXMLElement $xml, $data)
    {
        $xml->addAttribute('allowSync', '0');
        $xml->addAttribute('mixedParents', '1');
        self::_setCustomView($xml, $data);
    }

    protected static function _setCustomView(SimpleXMLElement $xml, $data)
    {
        foreach ($data as $key => $value) {
            foreach ($value as $id) {
                if ($key == 'artist') {
                    $artist = new Artist($id);
                    $artist->format();
                    self::addArtist($xml, $artist);
                } elseif ($key == 'album') {
                    $album = new Album($id);
                    $album->format();
                    self::addAlbum($xml, $album);
                } elseif ($key == 'song') {
                    $song = new Song($id);
                    $song->format();
                    self::addSong($xml, $song);
                }
            }
        }
    }

    public static function setServerInfo(SimpleXMLElement $xml, $catalogs)
    {
        $server = $xml->addChild('Server');
        $server->addAttribute('name', self::getServerName());
        $server->addAttribute('host', self::getServerPublicAddress());
        $localAddresses = self::getServerAddress();
        // Plex doesn't support server not listening locally on port 32400.
        // This is a plex hack to make it works with few clients. But better to listen on port 32400...
        if (self::getServerPort() != 32400) {
            $localAddresses .= ':' . self::getServerPort() . '/.hack/main';
        }
        $server->addAttribute('localAddresses', $localAddresses);
        $server->addAttribute('port', self::getServerPublicPort());
        $server->addAttribute('machineIdentifier', self::getMachineIdentifier());
        $server->addAttribute('version', self::getPlexVersion());

        self::setSections($xml, $catalogs);
    }

    public static function setLocalServerInfo(SimpleXMLElement $xml)
    {
        $server = $xml->addChild('Server');
        $server->addAttribute('name', self::getServerName());
        $server->addAttribute('host', '127.0.0.1');
        $server->addAttribute('address', '127.0.0.1');
        $server->addAttribute('port', self::getServerPort());
        $server->addAttribute('machineIdentifier', self::getMachineIdentifier());
        $server->addAttribute('version', self::getPlexVersion());
    }

    public static function addArtist(SimpleXMLElement $xml, Artist $artist)
    {
        $xdir = $xml->addChild('Directory');
        $id   = self::getArtistId($artist->id);
        $xdir->addAttribute('ratingKey', $id);
        $xdir->addAttribute('type', 'artist');
        $xdir->addAttribute('title', $artist->name);
        $xdir->addAttribute('index', '1');
        $xdir->addAttribute('addedAt', '');
        $xdir->addAttribute('updatedAt', '');

        $rating       = new Rating($artist->id, "artist");
        $rating_value = $rating->get_average_rating();
        if ($rating_value > 0) {
            $xdir->addAttribute('rating', intval($rating_value * 2));
        }

        self::addArtistMeta($xdir, $artist);
        self::addTags($xdir, 'artist', $artist->id);
    }

    public static function addArtistMeta(SimpleXMLElement $xml, Artist $artist)
    {
        $id = self::getArtistId($artist->id);
        if (!isset($xml['key'])) {
            $xml->addAttribute('key', self::getMetadataUri($id) . '/children');
        }
        $xml->addAttribute('summary', $artist->summary);
        self::addArtistThumb($xml, $artist->id);
    }

    protected static function addArtistThumb(SimpleXMLElement $xml, $artist_id, $attrthumb = 'thumb')
    {
        $id    = self::getArtistId($artist_id);
        $art   = new Art($artist_id, 'artist');
        $thumb = '';
        if ($art->get_db()) {
            $thumb = self::getMetadataUri($id) . '/thumb/' . $id;
        }
        $xml->addAttribute($attrthumb, $thumb);
    }

    public static function addAlbum(SimpleXMLElement $xml, Album $album)
    {
        $id   = self::getAlbumId($album->id);
        $xdir = $xml->addChild('Directory');
        self::addAlbumMeta($xdir, $album);
        $xdir->addAttribute('ratingKey', $id);
        $xdir->addAttribute('key', self::getMetadataUri($id) . '/children');
        $xdir->addAttribute('title', $album->f_title);
        $artistid = self::getArtistId($album->artist_id);
        $xdir->addAttribute('parentRatingKey', $artistid);
        $xdir->addAttribute('parentKey', self::getMetadataUri($artistid));
        $xdir->addAttribute('parentTitle', $album->f_artist);
        $xdir->addAttribute('leafCount', $album->song_count);
        if ($album->year != 0 && $album->year != 'N/A') {
            $xdir->addAttribute('year', $album->year);
        }

        $rating       = new Rating($album->id, "album");
        $rating_value = $rating->get_average_rating();
        if ($rating_value > 0) {
            $xdir->addAttribute('rating', intval($rating_value * 2));
        }

        $tags = Tag::get_top_tags('album', $album->id);
        if (is_array($tags)) {
            foreach ($tags as $tag_id=>$tag) {
                $xgenre = $xdir->addChild('Genre');
                $xgenre->addAttribute('tag', $tag['name']);
            }
        }
    }

    public static function addAlbumMeta(SimpleXMLElement $xml, Album $album)
    {
        $id = self::getAlbumId($album->id);
        $xml->addAttribute('allowSync', '1');
        $xml->addAttribute('type', 'album');
        $xml->addAttribute('summary', '');
        $xml->addAttribute('index', '1');
        if (Art::has_db($album->id, 'album')) {
            $xml->addAttribute('art', self::getMetadataUri($id) . '/thumb/' . $id);
            $xml->addAttribute('thumb', self::getMetadataUri($id) . '/thumb/' . $id);
        }
        if ($album->artist_id) {
            self::addArtistThumb($xml, $album->artist_id, 'parentThumb');
        }
        $xml->addAttribute('originallyAvailableAt', '');
        $xml->addAttribute('addedAt', '');
        $xml->addAttribute('updatedAt', '');
    }

    public static function addTVShow(SimpleXMLElement $xml, TVShow $tvshow)
    {
        $id   = self::getTVShowId($tvshow->id);
        $xdir = $xml->addChild('Directory');
        $xdir->addAttribute('ratingKey', $id);
        $xdir->addAttribute('key', self::getMetadataUri($id) . '/children');
        self::addTVShowMeta($xdir, $tvshow);
        $xdir->addAttribute('studio', '');
        $xdir->addAttribute('type', 'show');
        $xdir->addAttribute('title', $tvshow->f_name);
        $xdir->addAttribute('titleSort', $tvshow->name);
        $xdir->addAttribute('index', '1');
        $rating       = new Rating($tvshow->id, "tvshow");
        $rating_value = $rating->get_average_rating();
        if ($rating_value > 0) {
            $xdir->addAttribute('rating', intval($rating_value * 2));
        }
        if ($tvshow->year) {
            $xdir->addAttribute('year', $tvshow->year);
        }
        //$xdir->addAttribute('duration', '');
        //$xdir->addAttribute('originallyAvailableAt', '');
        $xdir->addAttribute('leafCount', $tvshow->episodes);
        $xdir->addAttribute('viewedLeafCount', '0');
        $xdir->addAttribute('addedAt', '');
        $xdir->addAttribute('updatedAt', '');

        self::addTags($xdir, 'tvshow', $tvshow->id);
    }

    public static function addTVShowMeta(SimpleXMLElement $xml, TVShow $tvshow)
    {
        $id = self::getTVShowId($tvshow->id);
        if (!isset($xml['key'])) {
            $xml->addAttribute('key', self::getMetadataUri($id) . '/children');
        }
        $xml->addAttribute('thumb', self::getMetadataUri($id) . '/thumb/' . $id);
        $xml->addAttribute('art', self::getMetadataUri($id) . '/art/' . $id);
        //$xml->addAttribute('banner', self::getMetadataUri($id) . '/banner/' . $id);
        $xml->addAttribute('summary', $tvshow->summary);
    }

    private static function addTags(SimpleXMLElement $xml, $object_type, $object_id)
    {
        $tags = Tag::get_top_tags($object_type, $object_id);
        if (is_array($tags) && count($tags) > 0) {
            foreach ($tags as $tag_id=>$tag) {
                $xgenre = $xml->addChild('Genre');
                $xgenre->addAttribute('tag', $tag['name']);
            }
        }
    }

    public static function setSectionTags(SimpleXMLElement $xml, $catalog, $object_type)
    {
        self::setSectionAllAttributes($xml, $catalog, 'All Genres', 'secondary');

        // TODO: should be catalog based
        if (!empty($object_type)) {
            $tags = Tag::get_tags($object_type);
            if (is_array($tags) && count($tags) > 0) {
                foreach ($tags as $tag_id=>$tag) {
                    $xdir = $xml->addChild('Directory');
                    $xdir->addAttribute('key', $tag['id']);
                    $xdir->addAttribute('title', $tag['name']);
                    $xdir->addAttribute('type', 'genre');
                }
            }
        }
    }

    public static function setArtistRoot(SimpleXMLElement $xml, Artist $artist)
    {
        $id = self::getAlbumId($artist->id);
        $xml->addAttribute('key', $id);
        self::addArtistMeta($xml, $artist);
        $xml->addAttribute('allowSync', '1');
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('parentIndex', '1'); // ??
        $xml->addAttribute('parentTitle', $artist->name);
        $xml->addAttribute('title1', ''); // TODO: Should be catalog name
        $xml->addAttribute('title2', $artist->name);
        $xml->addAttribute('viewGroup', 'album');
        $xml->addAttribute('viewMode', '65592');

        $allalbums = $artist->get_albums(null, true);
        foreach ($allalbums as $id) {
            $album = new Album($id);
            $album->format();
            self::addAlbum($xml, $album);
        }
    }

    public static function setAlbumRoot(SimpleXMLElement $xml, Album $album)
    {
        $id = self::getAlbumId($album->id);
        self::addAlbumMeta($xml, $album);
        if (!isset($xml['key'])) {
            $xml->addAttribute('key', $id);
        }
        $xml->addAttribute('grandparentTitle', $album->f_artist);
        $xml->addAttribute('title1', $album->f_artist);
        if (!isset($xml['allowSync'])) {
            $xml->addAttribute('allowSync', '1');
        }
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('parentIndex', '1'); // ??
        $xml->addAttribute('parentTitle', $album->f_title);
        $xml->addAttribute('title2', $album->f_title);
        if ($album->year != 0 && $album->year != 'N/A') {
            $xml->addAttribute('parentYear', $album->year);
        }
        $xml->addAttribute('viewGroup', 'track');
        $xml->addAttribute('viewMode', '65593');

        $allsongs = $album->get_songs();
        foreach ($allsongs as $sid) {
            $song = new Song($sid);
            self::addSong($xml, $song);
        }
    }

    public static function setTVShowRoot(SimpleXMLElement $xml, TVShow $tvshow)
    {
        self::addTVShowMeta($xml, $tvshow);
        $xml->addAttribute('allowSync', '1');
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('parentIndex', '1'); // ??
        $xml->addAttribute('parentTitle', $tvshow->f_name);
        $xml->addAttribute('parentYear', $tvshow->year);
        $xml->addAttribute('title1', ''); // TODO: Should be catalog name
        $xml->addAttribute('title2', $tvshow->f_name);
        $xml->addAttribute('viewGroup', 'season');
        $xml->addAttribute('viewMode', '65593');

        $seasons = $tvshow->get_seasons();
        foreach ($seasons as $season_id) {
            $season = new TVShow_Season($season_id);
            $season->format();
            self::addTVShowSeason($xml, $season);
        }
    }

    public static function addTVShowSeason(SimpleXMLElement $xml, TVShow_Season $season)
    {
        $id   = self::getTVShowSeasonId($season->id);
        $xdir = $xml->addChild('Directory');
        $xdir->addAttribute('ratingKey', $id);
        $xdir->addAttribute('key', self::getMetadataUri($id) . '/children');
        $tvshowid = self::getTVShowId($season->tvshow);
        $xdir->addAttribute('parentRatingKey', $tvshowid);
        $xdir->addAttribute('parentKey', self::getMetadataUri($tvshowid));
        $xdir->addAttribute('type', 'season');
        $xdir->addAttribute('title', $season->f_name);
        $xdir->addAttribute('summary', '');
        $xdir->addAttribute('index', '1'); // ??
        $xdir->addAttribute('thumb', self::getMetadataUri($id) . '/thumb/' . $id);
        $xdir->addAttribute('leafCount', $season->episodes);
        $xdir->addAttribute('viewedLeafCount', '0');
        $xdir->addAttribute('addedAt', '');
        $xdir->addAttribute('updatedAt', '');
    }

    public static function setTVShowSeasonRoot(SimpleXMLElement $xml, TVShow_Season $season)
    {
        $tvshow = new TVShow($season->tvshow);
        $tvshow->format();

        $id = self::getTVShowSeasonId($season->id);
        $xml->addAttribute('key', $id);
        $xml->addAttribute('allowSync', '1');
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('parentIndex', '1'); // ??
        $xml->addAttribute('parentTitle', '');
        $xml->addAttribute('grandparentStudio', '');
        $xml->addAttribute('grandparentTitle', $tvshow->f_name);
        $xml->addAttribute('title1', $tvshow->f_name);
        $xml->addAttribute('title2', $season->f_name);
        $xml->addAttribute('viewGroup', 'episode');
        $xml->addAttribute('viewMode', '65592');
        $xml->addAttribute('thumb', self::getMetadataUri($id) . '/thumb/' . $id);
        $xml->addAttribute('art', self::getMetadataUri($id) . '/art/' . $id);
        //$xml->addAttribute('banner', self::getMetadataUri($id) . '/banner/' . $id);

        $episodes = $season->get_episodes();
        foreach ($episodes as $episode_id) {
            $episode = new TVShow_Episode($episode_id);
            $episode->format();
            self::addEpisode($xml, $episode);
        }
    }

    public static function addSong(SimpleXMLElement $xml, Song $song)
    {
        $xdir = $xml->addChild('Track');
        self::addSongMeta($xdir, $song);
        $time = $song->time * 1000;
        $xdir->addAttribute('title', $song->title);
        $id       = self::getAlbumId($song->id);
        $albumid  = self::getAlbumId($song->album);
        $artistid = self::getAlbumId($song->artist);
        $xdir->addAttribute('grandparentRatingKey', $artistid);
        $xdir->addAttribute('parentRatingKey', $albumid);
        $xdir->addAttribute('grandparentKey', self::getMetadataUri($albumid));
        $xdir->addAttribute('parentKey', self::getMetadataUri($albumid));
        $xdir->addAttribute('grandparentTitle', $song->f_artist);
        $xdir->addAttribute('parentTitle', $song->f_album);
        $xdir->addAttribute('originalTitle', $song->f_artist);
        $xdir->addAttribute('summary', '');
        $xdir->addAttribute('art', self::getMetadataUri($id) . '/art/' . $id);
        $xdir->addAttribute('grandparentThumb', self::getMetadataUri($artistid) . '/thumb/' . $artistid);
        $xdir->addAttribute('parentThumb', self::getMetadataUri($albumid) . '/thumb/' . $albumid);
        $xdir->addAttribute('thumb', self::getMetadataUri($albumid) . '/thumb/' . $albumid); // No song art, set album art
       $xdir->addAttribute('index', $song->track);
        $xdir->addAttribute('duration', $time);
        $xdir->addAttribute('type', 'track');
        $xdir->addAttribute('addedAt', '');
        $xdir->addAttribute('updatedAt', '');

        $rating       = new Rating($song->id, "song");
        $rating_value = $rating->get_average_rating();
        if ($rating_value > 0) {
            $xdir->addAttribute('rating', intval($rating_value * 2));
        }

        $xmedia  = $xdir->addChild('Media');
        $mediaid = self::getSongId($song->id);
        $xmedia->addAttribute('id', $mediaid);
        $xmedia->addAttribute('duration', $time);
        $xmedia->addAttribute('bitrate', intval($song->bitrate / 1000));
        $xmedia->addAttribute('audioChannels', $song->channels);
       // Type != Codec != Container, but that's how Ampache works today...
       $xmedia->addAttribute('audioCodec', $song->type);
        $xmedia->addAttribute('container', $song->type);

        $xpart  = $xmedia->addChild('Part');
        $partid = self::getPartId($mediaid);
        $xpart->addAttribute('id', $partid);
        $xpart->addAttribute('key', self::getPartUri($partid, $song->type));
        $xpart->addAttribute('duration', $time);
        $xpart->addAttribute('file', $song->file);
        $xpart->addAttribute('size', $song->size);
        $xpart->addAttribute('container', $song->type);

        return $xdir;
    }

    public static function addSongMeta(SimpleXMLElement $xml, Song $song)
    {
        $id = self::getTrackId($song->id);
        $xml->addAttribute('ratingKey', $id);
        $xml->addAttribute('key', self::getMetadataUri($id));

        return $xml;
    }

    public static function addMovie(SimpleXMLElement $xml, Movie $movie, $details = false)
    {
        $xvid = self::addVideo($xml, $movie, $details);
        $xvid->addAttribute('type', 'movie');
        $xvid->addAttribute('summary', $movie->summary);
        $xvid->addAttribute('originalTitle', $movie->original_name);
        if ($movie->year) {
            if (isset($xml['year'])) {
                $xml['year'] = $movie->year;
            } else {
                $xvid->addAttribute('year', $movie->year);
            }
        }
        return $xvid;
    }

    public static function addEpisode(SimpleXMLElement $xml, TVShow_Episode $episode, $details = false)
    {
        $xvid     = self::addVideo($xml, $episode, $details);
        $seasonid = self::getTVShowSeasonId($episode->season);
        $xvid->addAttribute('parentRatingKey', $seasonid);
        $xvid->addAttribute('parentKey', self::getMetadataUri($seasonid));
        $xvid->addAttribute('type', 'episode');
        $xvid->addAttribute('summary', $episode->summary);
        $xvid->addAttribute('index', $episode->episode_number);
        return $xvid;
    }

    private static function addVideoExt(SimpleXMLElement $xml, Video $video, $details = false)
    {
        $type = strtolower(get_class($video));
        switch ($type) {
            case 'tvshow_episode':
                return self::addEpisode($xml, $video, $details);
            case 'movie':
                return self::addMovie($xml, $video, $details);
            default:
                return self::addVideo($xml, $video, $details);
        }
    }

    private static function addVideo(SimpleXMLElement $xml, Video $video, $details = false)
    {
        $id   = self::getVideoId($video->id);
        $xvid = $xml->addChild('Video');
        $xvid->addAttribute('ratingKey', $id);
        $xvid->addAttribute('key', self::getMetadataUri($id));
        $xvid->addAttribute('title', $video->f_title);
        if ($video->release_date) {
            $year = date('Y', $video->release_date);
            if ($year) {
                $xvid->addAttribute('year', $year);
            }
            $xvid->addAttribute('originallyAvailableAt', $video->f_release_date);
        }
        $rating       = new Rating($video->id, "video");
        $rating_value = $rating->get_average_rating();
        if ($rating_value > 0) {
            $xvid->addAttribute('rating', intval($rating_value * 2));
        }
        $time = $video->time * 1000;
        $xvid->addAttribute('duration', $time);
        $xvid->addAttribute('addedAt', '');
        $xvid->addAttribute('updatedAt', '');
        $xvid->addAttribute('thumb', self::getMetadataUri($id) . '/thumb/' . $id);

        $xmedia = $xvid->addChild('Media');
        $xmedia->addAttribute('id', $id); // Same ID that video => OK?
       $xmedia->addAttribute('duration', $time);
        $xmedia->addAttribute('bitrate', intval($video->bitrate / 1000));
        $xmedia->addAttribute('audioChannels', $video->channels);
       // Type != Codec != Container, but that's how Ampache works today...
       $xmedia->addAttribute('audioCodec', $video->audio_codec);
        $xmedia->addAttribute('videoCodec', $video->video_codec);
        $xmedia->addAttribute('container', $video->type);
        $xmedia->addAttribute('width', $video->resolution_x);
        $xmedia->addAttribute('height', $video->resolution_y);
       //$xmedia->addAttribute('videoResolution', 'sd'); // TODO
       //$xmedia->addAttribute('aspectRatio', '1.78'); // TODO
       $xmedia->addAttribute('videoFrameRate', intval(ceil($video->frame_rate)) . 'p');

        $xpart  = $xmedia->addChild('Part');
        $partid = self::getPartId($id);
        $xpart->addAttribute('id', $partid);
        $xpart->addAttribute('key', self::getPartUri($partid, $video->type));
        $xpart->addAttribute('duration', $time);
        $xpart->addAttribute('file', $video->file);
        $xpart->addAttribute('size', $video->size);
        $xpart->addAttribute('container', $video->type);

       // TODO: support Writer/Director tags here as part of Video/
       /*
        <Writer tag="Grant Scharbo" />
        <Writer tag="Richard Hatem" />
        <Director tag="Terry McDonough" />
        */

       $tags = Tag::get_top_tags('video', $video->id);
        if (is_array($tags)) {
            foreach ($tags as $tag_id=>$tag) {
                $xgenre = $xvid->addChild('Genre');
                $xgenre->addAttribute('id', $tag['id']);
                $xgenre->addAttribute('tag', $tag['name']);
            }
        }

        if ($details) {
            // Subtitles
           $subtitles = $video->get_subtitles();
            foreach ($subtitles as $subtitle) {
                $streamid = hexdec(bin2hex($subtitle['lang_code'])) . $partid;
                $xstream  = $xpart->addChild('Stream');
                $xstream->addAttribute('id', $streamid);
                $xstream->addAttribute('key', '/library/streams/' . $streamid);
                $xstream->addAttribute('streamType', '3');
                $xstream->addAttribute('codec', 'srt');
                $xstream->addAttribute('language', $subtitle['lang_name']);
                $xstream->addAttribute('languageCode', $subtitle['lang_code']);
                $xstream->addAttribute('format', 'srt');
            }

           // TODO: support real audio/video streams!
           /*
            <Stream id="93" streamType="1" codec="mpeg4" index="0" bitrate="833" bitDepth="8" chromaSubsampling="4:2:0" colorSpace="yuv" duration="2989528" frameRate="23,976" gmc="0" height="352" level="5" profile="asp" qpel="0" scanType="progressive" width="624" />
            <Stream id="94" streamType="2" selected="1" codec="mp3" index="1" channels="2" bitrate="135" bitrateMode="vbr" duration="2989488" samplingRate="48000" />
            */
        }

        return $xvid;
    }

    public static function setPlaylists(SimpleXMLElement $xml)
    {
        $playlists = Playlist::get_playlists();
        foreach ($playlists as $playlist_id) {
            $playlist = new Playlist($playlist_id);
            $playlist->format();
            self::addPlaylist($xml, $playlist);
        }
    }

    public static function addPlaylist(SimpleXMLElement $xml, $playlist)
    {
        $id  = self::getPlaylistId($playlist->id);
        $xpl = $xml->addChild('Playlist');
        $xpl->addAttribute('ratingKey', $id);
        $xpl->addAttribute('key', '/playlists/' . $id . '/items');
        $xpl->addAttribute('type', 'playlist');
        $xpl->addAttribute('title', $playlist->name);
        $xpl->addAttribute('summary', '');
        $xpl->addAttribute('smart', '0');
        //$xpl->addAttribute('composite', '');
        $xpl->addAttribute('playlistType', 'audio');
        $xpl->addAttribute('duration', $playlist->get_total_duration() * 1000);
        $xpl->addAttribute('leafCount', $playlist->get_media_count('song'));
        $xpl->addAttribute('addedAt', '');
        $xpl->addAttribute('updatedAt', '');
    }

    public static function setPlaylistItems(SimpleXMLElement $xml, $playlist)
    {
        $xml->addAttribute('duration', $playlist->get_total_duration() * 1000);
        $xml->addAttribute('leafCount', $playlist->get_media_count('song'));
        $items = $playlist->get_items();
        self::addPlaylistsItems($xml, $items);
    }

    private static function addPlaylistsItems(SimpleXMLElement $xml, $items, $itemIDName = 'playlistItemID')
    {
        foreach ($items as $item) {
            $xitem = null;
            if ($item['object_type'] == 'song') {
                $media = new Song($item['object_id']);
                $media->format();
                $xitem = self::addSong($xml, $media);
            } elseif ($item['object_type'] == 'video') {
                $media = Video::create_from_id($item['object_id']);
                $media->format();
                $xitem = self::addVideoExt($xml, $media);
            }

            if ($xitem != null) {
                if (isset($item['track_id'])) {
                    $xitem->addAttribute($itemIDName, $item['track_id']);
                }
            }
        }
    }

    public static function setPlayQueue(SimpleXMLElement $xml, $type, $playlistID, $uri, $key, $shuffle)
    {
        $GLOBALS['user']->playlist->clear();

        $id = 0;
        if (!empty($key)) {
            $id = self::getKeyFromMetadataUri($key);
        } elseif (!empty($uri)) {
            $key = self::getKeyFromFullUri($uri);
            $id  = self::getKeyFromMetadataUri($key);
        }

        $plmedias = array();

        if ($id) {
            if ($type == 'audio' || $type == 'video') {
                $item = self::createLibraryItem($id);
                if ($item != null) {
                    $plmedias = $item->get_medias();
                }
            }
        } else {
            // Add complete playlist
            if (self::isPlaylist($playlistID)) {
                $playlist = new Playlist(self::getAmpacheId($playlistID));
                if ($shuffle) {
                    $plmedias = $playlist->get_random_items();
                } else {
                    $plmedias = $playlist->get_items();
                }
            }
        }

        $GLOBALS['user']->playlist->add_medias($plmedias);
        self::setTmpPlayQueue($xml, 1);
    }

    public static function setTmpPlayQueue(SimpleXMLElement $xml, $playlistID)
    {
        // We only support one queue at time
        if ($playlistID != 1) {
            debug_event('plex', 'Unsupported playlist, it should be 1.', 3);
        } else {
            $xml->addAttribute('playQueueID', '1');
            $xml->addAttribute('playQueueVersion', '1');

            $items = $GLOBALS['user']->playlist->get_items();
            $c     = count($items);
            if ($c > 0) {
                self::addPlaylistsItems($xml, $items, 'playQueueItemID');

                // TODO: This should be the real selected item.
                // But we're missing this information in Ampache playlist
                $currentIndex = 0;
                $currentItem  = $items[$currentIndex];
                if (isset($currentItem['track_id'])) {
                    $xml->addAttribute('playQueueSelectedItemID', $currentItem['track_id']);
                }
                $xml->addAttribute('playQueueSelectedItemOffset', $currentIndex);
            }

            $xml->addAttribute('playQueueTotalCount', $c);
        }
    }

    public static function createMyPlexAccount()
    {
        $xml              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><MyPlex/>');
        $myplex_username  = self::getMyPlexUsername();
        $myplex_authtoken = self::getMyPlexAuthToken();
        $myplex_published = self::getMyPlexPublished();
        if ($myplex_username) {
            $xml->addAttribute('myPlex', '1');
            $xml->addAttribute('username', $myplex_username);
            if ($myplex_authtoken) {
                $xml->addAttribute('authToken', $myplex_authtoken);
                $xml->addAttribute('signInState', 'ok');
                if ($myplex_published) {
                    $xml->addAttribute('mappingState', 'mapped');
                } else {
                    $xml->addAttribute('mappingState', 'unknown');
                }
            } else {
                $xml->addAttribute('signInState', 'none');
            }
        } else {
            $xml->addAttribute('signInState', 'none');
        }
        $xml->addAttribute('mappingError', '');
        $xml->addAttribute('mappingErrorMessage', '1');

        $xml->addAttribute('publicAddress', '1');
        $xml->addAttribute('publicPort', self::getServerPublicPort());
        $xml->addAttribute('privateAddress', '1');
        $xml->addAttribute('privatePort', self::getServerPort());

        $xml->addAttribute('subscriptionActive', '1');
        $xml->addAttribute('subscriptionFeatures', 'cloudsync,pass,sync');
        $xml->addAttribute('subscriptionState', 'Active');

        return $xml;
    }

    public static function setSysAgents(SimpleXMLElement $xml)
    {
        /*$agent = $xml->addChild('Agent');
        $agent->addAttribute('primary', '0');
        $agent->addAttribute('hasPrefs', '0');
        $agent->addAttribute('hasAttribution', '1');
        $agent->addAttribute('identifier', 'com.plexapp.agents.wikipedia');*/

        $agent = $xml->addChild('Agent');
        $agent->addAttribute('primary', '1');
        $agent->addAttribute('hasPrefs', '0');
        $agent->addAttribute('hasAttribution', '0');
        $agent->addAttribute('identifier', 'com.plexapp.agents.none');
        self::addNoneAgentMediaType($agent, 'Personal Media Artists', Plex_XML_Data::PLEX_ARTIST);
        self::addNoneAgentMediaType($agent, 'Personal Media', Plex_XML_Data::PLEX_MOVIE);
        self::addNoneAgentMediaType($agent, 'Personal Media Shows', Plex_XML_Data::PLEX_TVSHOW);
        self::addNoneAgentMediaType($agent, 'Photos', '13');
        self::addNoneAgentMediaType($agent, 'Personal Media Albums', Plex_XML_Data::PLEX_ALBUM);
    }

    protected static function addNoneAgentMediaType(SimpleXMLElement $xml, $name, $type)
    {
        $media = $xml->addChild('MediaType');
        $media->addAttribute('name', $name);
        $media->addAttribute('mediaType', $type);
        self::addLanguages($media, 'xn');
    }

    protected static function addLanguages(SimpleXMLElement $xml, $languages)
    {
        $langs = explode(',', $languages);
        foreach ($langs as $lang) {
            $lg = $xml->addChild('Language');
            $lg->addAttribute('code', $lang);
        }
    }

    protected static function addNoneAgent(SimpleXMLElement $xml, $name)
    {
        self::addAgent($xml, $name, false, 'com.plexapp.agents.none', true, 'xn');
    }

    protected static function addAgent(SimpleXMLElement $xml, $name, $hasPrefs, $identifier, $enabled = false, $langs='')
    {
        $agent = $xml->addChild('Agent');
        $agent->addAttribute('name', $name);
        if ($enabled) {
            $agent->addAttribute('enabled', ($enabled) ? '1' : '0');
        }
        $agent->addAttribute('hasPrefs', ($hasPrefs) ? '1' : '0');
        $agent->addAttribute('identifier', $identifier);
        if (!empty($langs)) {
            self::addLanguages($agent, $langs);
        }
        return $agent;
    }

    public static function setSysMovieAgents(SimpleXMLElement $xml)
    {
        self::addNoneAgent($xml, 'Ampache Media Movies');
    }

    public static function setSysTVShowAgents(SimpleXMLElement $xml)
    {
        self::addNoneAgent($xml, 'Ampache Media Series');
    }

    public static function setSysPhotoAgents(SimpleXMLElement $xml)
    {
        self::addNoneAgent($xml, 'Photos');
    }

    public static function setSysMusicAgents($xml, $category = 'Artists')
    {
        self::addNoneAgent($xml, 'Ampache Media ' . $category);
        //self::addAgent($xml, 'Last.fm', '1', 'com.plexapp.agents.lastfm', 'true', 'en,sv,fr,es,de,pl,it,pt,ja,tr,ru,zh');
    }

    public static function getAmpacheType($plex_type)
    {
        switch ($plex_type) {
            case Plex_XML_Data::PLEX_MOVIE:
                return 'movie';
            case Plex_XML_Data::PLEX_TVSHOW:
                return 'tvshow';
            case Plex_XML_Data::PLEX_SEASON:
                return 'season';
            case Plex_XML_Data::PLEX_EPISODE:
                return 'episode';
            case Plex_XML_Data::PLEX_ARTIST:
                return 'artist';
            case Plex_XML_Data::PLEX_ALBUM:
                return 'album';
        }
    }

    public static function getLibraryItemType($id)
    {
        $type = null;

        if (self::isArtist($id)) {
            $type = 'artist';
        } elseif (self::isAlbum($id)) {
            $type = 'album';
        } elseif (self::isSong($id) || self::isTrack($id)) {
            $type = 'song';
        } elseif (self::isTVShow($id)) {
            $type = 'tvshow';
        } elseif (self::isTVShowSeason($id)) {
            $type = 'tvshow_season';
        } elseif (self::isVideo($id)) {
            $type = 'video';
        } elseif (self::isPlaylist($id)) {
            $type = 'playlist';
        }

        return $type;
    }

    public static function createLibraryItem($id)
    {
        $item = null;
        $oid  = self::getAmpacheId($id);
        $type = self::getLibraryItemType($id);

        if ($type) {
            if ($type == 'video') {
                $item = Video::create_from_id($oid);
            } else {
                $item = new $type($oid);
            }
        }

        if ($item != null) {
            if ($item->id) {
                $item->format();
            } else {
                $item = null;
            }
        }

        return $item;
    }

    public static function setAgentsContributors(SimpleXMLElement $xml, $plex_type, $primaryAgent)
    {
        if ($primaryAgent == 'com.plexapp.agents.none') {
            $type = '';
            switch ($plex_type) {
                case Plex_XML_Data::PLEX_MOVIE:
                    $type = 'Movies';
                break;
                case Plex_XML_Data::PLEX_TVSHOW:
                    $type = 'TV';
                break;
                case Plex_XML_Data::PLEX_PHOTO:
                    $type = 'Photos';
                break;
                case Plex_XML_Data::PLEX_ARTIST:
                    $type = 'Artists';
                break;
                case Plex_XML_Data::PLEX_ALBUM:
                    $type = 'Albums';
                break;
            }

            self::addAgent($xml, 'Local Media Assets (' . $type . ')', '0', 'com.plexapp.agents.localmedia', true);
        }
    }

    /**
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function setAccounts(SimpleXMLElement $xml, $userid)
    {
        // Not sure how to handle Plex accounts vs Ampache accounts, return only 1 for now.

        $account = $xml->addChild('Account');
        $account->addAttribute('key', '/accounts/1');
        $account->addAttribute('name', 'Administrator');
        $account->addAttribute('defaultAudioLanguage', 'en');
        $account->addAttribute('autoSelectAudio', '1');
        $account->addAttribute('defaultSubtitleLanguage', 'en');
        $account->addAttribute('subtitleMode', '0');
    }

    public static function setStatus(SimpleXMLElement $xml)
    {
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'sessions');
        $dir->addAttribute('title', 'sessions');
    }

    public static function setPrefs(SimpleXMLElement $xml)
    {
        self::addSettings($xml, 'AcceptedEULA', 'Has the user accepted the EULA', 'false', '', 'bool', 'true', '1', '0', '');
        //self::addSettings($xml, 'ApertureLibraryXmlPath', 'Aperture library XML path', '', '', 'text', '', '0', '1', 'channels');
        //self::addSettings($xml, 'ApertureSharingEnabled', 'Enable Aperture sharing', 'true', '', 'bool', 'true', '0', '0', 'channels');
        //self::addSettings($xml, 'AppCastUrl', 'AppCast URL', 'https://www.plexapp.com/appcast/mac/pms.xml', '', 'text', 'https://www.plexapp.com/appcast/mac/pms.xml', '0', '1', 'network');
        self::addSettings($xml, 'ConfigurationUrl', 'Web Manager URL', 'http://127.0.0.1:32400/web', '', 'text', self::getServerUri() . '/web', '1', '0', 'network');
        //self::addSettings($xml, 'DisableHlsAuthorization', 'Disable HLS authorization', 'false', '', 'bool', 'false', '1', '0', '');
        //self::addSettings($xml, 'DlnaAnnouncementLeaseTime', 'DLNA server announcement lease time', '1800', 'Duration of DLNA Server SSDP announcement lease time, in seconds', 'int', '1800', '0', '1', 'dlna');
        self::addSettings($xml, 'FirstRun', 'First run of PMS on this machine', 'true', '', 'bool', 'false', '1', '0', '');
        self::addSettings($xml, 'ForceSecureAccess', 'Force secure access', 'false', 'Disallow access on the local network except to authorized users', 'bool', 'false', '1', '0', 'general');
        self::addSettings($xml, 'FriendlyName', 'Friendly name', '', 'This name will be used to identify this media server to other computers on your network. If you leave it blank, your computer\'s name will be used instead.', 'text', self::getServerName(), '0', '0', 'general');
        self::addSettings($xml, 'LogVerbose', 'Plex Media Server verbose logging', 'false', 'Enable Plex Media Server verbose logging', 'bool', AmpConfig::get('debug_level') == '5', '0', '1', 'general');
        self::addSettings($xml, 'MachineIdentifier', 'A unique identifier for the machine', '', '', 'text', self::getMachineIdentifier(), '1', '0', '');
        self::addSettings($xml, 'ManualPortMappingMode', 'Disable Automatic Port Mapping', 'false', 'When enabled, PMS is not trying to set-up a port mapping through your Router automatically', 'bool', 'false', '1', '0', '');
        self::addSettings($xml, 'ManualPortMappingPort', 'External Port', '32400', 'When Automatic Port Mapping is disabled, you need to specify the external port that is mapped to this machine.', 'int', self::getServerPublicPort(), '1', '0', '');
        self::addSettings($xml, 'PlexOnlineMail', 'myPlex email', '', 'The email address you use to login to myPlex.', 'text', self::getMyPlexUsername(), '1', '0', '');
        self::addSettings($xml, 'PlexOnlineUrl', 'myPlex service URL', 'https://my.plexapp.com', 'The URL of the myPlex service', 'text', 'https://my.plexapp.com', '1', '0', '');
        self::addSettings($xml, 'allowMediaDeletion', 'Allow clients to delete media', 'false', 'Clients will be able to delete media', 'bool', 'false', '0', '1', 'library');
        self::addSettings($xml, 'logDebug', 'Plex Media Server debug logging', 'false', 'Enable Plex Media Server debug logging', 'bool', AmpConfig::get('debug'), '0', '1', 'general');
    }

    protected static function addSettings(SimpleXMLElement $xml, $id, $label, $default, $summary, $type, $value, $hidden, $advanced, $group)
    {
        $setting = $xml->addChild('Setting');
        $setting->addAttribute('id', $id);
        $setting->addAttribute('label', $label);
        $setting->addAttribute('default', $default);
        $setting->addAttribute('summary', $summary);
        $setting->addAttribute('type', $type);
        $setting->addAttribute('value', $value);
        $setting->addAttribute('hidden', $hidden);
        $setting->addAttribute('advanced', $advanced);
        $setting->addAttribute('group', $group);
    }

    public static function setScanners(SimpleXMLElement $xml, $type)
    {
        $scanner = $xml->addChild('Scanner');
        switch ($type) {
            case self::PLEX_ALBUM:
            case self::PLEX_ARTIST:
                $scanner->addAttribute('name', 'Ampache Music Scanner');
                break;
            case self::PLEX_TVSHOW:
            case self::PLEX_SEASON:
            case self::PLEX_EPISODE:
                $scanner->addAttribute('name', 'Ampache Series Scanner');
                break;
            case self::PLEX_MOVIE:
                $scanner->addAttribute('name', 'Ampache Movie Scanner');
                break;
        }
    }

    public static function createAppStore()
    {
        // Maybe we can setup our own store here? Ignore for now.
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ASContainer/>');
        $xml->addAttribute('art', self::getResourceUri('store-art.png'));
        $xml->addAttribute('noCache', '1');
        $xml->addAttribute('noHistory', '0');
        $xml->addAttribute('title1', 'Channel Directory');
        $xml->addAttribute('replaceParent', '0');
        $xml->addAttribute('identifier', 'com.plexapp.system');

        return $xml;
    }

    public static function setMyPlexSubscription(SimpleXMLElement $xml)
    {
        $subscription = $xml->addChild('subscription');
        $subscription->addAttribute('active', '1');
        $subscription->addAttribute('status', 'Active');
        $subscription->addAttribute('plan', 'lifetime');
        $features = array('cloudsync', 'pass', 'sync');
        foreach ($features as $feature) {
            $fxml = $subscription->addChild('feature');
            $fxml->addAttribute('id', $feature);
        }
    }

    public static function setServices(SimpleXMLElement $xml)
    {
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'browse');
        $dir->addAttribute('title', 'browse');
    }

    protected static function getPathDelimiter()
    {
        if (strpos(PHP_OS, 'WIN') === 0) {
            return '\\';
        } else {
            return '/';
        }
    }

    public static function setBrowseService(SimpleXMLElement $xml, $path)
    {
        $delim = self::getPathDelimiter();
        if (!empty($path)) {
            $dir = base64_decode($path);
            debug_event('plex', 'Dir: ' . $dir, '5');
        } else {
            self::addDirPath($xml, AmpConfig::get('prefix') . $delim . 'plex', 'plex', true);

            if ($delim == '/') {
                $dir = '/';
            } else {
                $dir = '';
                // TODO: found a better way to list Windows drive
                $letters = str_split("CDEFGHIJ");
                foreach ($letters as $letter) {
                    self::addDirPath($xml, $letter . ':');
                }
            }
        }

        if (!empty($dir)) {
            $dh  = opendir($dir);
            if (is_resource($dh)) {
                while (false !== ($filename = readdir($dh))) {
                    $path = $dir . $delim . $filename;
                    if ($filename != '.' && $filename != '..' && is_dir($path)) {
                        self::addDirPath($xml, $path);
                    }
                }
            }
        }
    }

    public static function addDirPath(SimpleXMLElement $xml, $path, $title='', $isHome=false)
    {
        $delim = self::getPathDelimiter();
        $dir   = $xml->addChild('Path');
        if ($isHome) {
            $dir->addAttribute('isHome', '1');
        }
        if (empty($title)) {
            $pp    = explode($delim, $path);
            $title = $pp[count($pp)-1];
            if (empty($title)) {
                $title = $path;
            }
        }
        $key = '/services/browse/' . base64_encode($path);
        $dir->addAttribute('key', $key);
        $dir->addAttribute('title', $title);
        $dir->addAttribute('path', $path);
    }

    public static function addCatalogIdentity(SimpleXMLElement $xml, Catalog $catalog)
    {
        $xml->addAttribute('librarySectionID', $catalog->id);
        $xml->addAttribute('librarySectionTitle', $catalog->name);
        $xml->addAttribute('librarySectionUUID', self::uuidFromkey($catalog->id));
    }

    public static function getPhotoKind($type)
    {
        switch ($type) {
            case 'art':
            case 'arts':
                return 'art';
            case 'background':
            case 'backgrounds':
                return 'background';
            case 'thumb':
            case 'thumbs':
            case 'poster':
            case 'posters':
            default:
                return 'default';
        }
    }

    public static function getPhotoPlexKind($type)
    {
        switch ($type) {
            case 'preview':
                return 'thumb';
            case 'art':
                return 'art';
            case 'background':
                return 'background';
            case 'default':
            default:
                return 'poster';
        }
    }

    public static function addPhotos(SimpleXMLElement $xml, $id, $kind = 'default')
    {
        self::addPhoto($xml, $id, $kind);
    }

    public static function addPhoto(SimpleXMLElement $xml, $id, $kind = 'default')
    {
        $xart = $xml->addChild('Photo');
        $uri  = self::getMetadataUri($id) . '/' . self::getPhotoPlexKind($kind) . '/' . $id;
        $xart->addAttribute('key', $uri);
        $xart->addAttribute('ratingKey', $uri);
        $xart->addAttribute('thumb', $uri);
    }
}
