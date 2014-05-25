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
    const AMPACHEID_ARTIST = 100000000;
    const AMPACHEID_ALBUM = 200000000;
    const AMPACHEID_TRACK = 300000000;
    const AMPACHEID_MEDIA = 400000000;
    const AMPACHEID_PART = 500000000;

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

    public static function getMediaId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_MEDIA;
    }

    public static function getPartId($id)
    {
        return $id + Plex_XML_Data::AMPACHEID_PART;
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
        return ($id >= Plex_XML_Data::AMPACHEID_ARTIST && $id < Plex_XML_Data::AMPACHEID_ALBUM);
    }

    public static function isAlbum($id)
    {
        return ($id >= Plex_XML_Data::AMPACHEID_ALBUM && $id < Plex_XML_Data::AMPACHEID_TRACK);
    }

    public static function isTrack($id)
    {
        return ($id >= Plex_XML_Data::AMPACHEID_TRACK  && $id < Plex_XML_Data::AMPACHEID_MEDIA);
    }

    public static function isMedia($id)
    {
        return ($id >= Plex_XML_Data::AMPACHEID_MEDIA  && $id < Plex_XML_Data::AMPACHEID_PART);
    }

    public static function isPart($id)
    {
        return ($id >= Plex_XML_Data::AMPACHEID_PART);
    }

    public static function getPlexVersion()
    {
        return "0.9.8.18.290-11b7fdd";
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
        $response->addAttribute('mediaTagVersion', '1365384731');
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

    public static function setRootContent($xml, $catalogs)
    {
        $xml->addAttribute('friendlyName', self::getServerName());
        $xml->addAttribute('machineIdentifier', self::getMachineIdentifier());

        $myplex_username = self::getMyPlexUsername();
        $myplex_authtoken = self::getMyPlexAuthToken();
        $myplex_published = self::getMyPlexPublished();
        if ($myplex_username) {
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
        $xml->addAttribute('transcoderActiveVideoSessions', '0');
        $xml->addAttribute('transcoderAudio', '1');
        $xml->addAttribute('transcoderVideo', '0');

        $xml->addAttribute('updatedAt', self::getLastUpdate($catalogs));
        $xml->addAttribute('version', self::getPlexVersion());

        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'channels');
        $dir->addAttribute('title', 'channels');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('count', '1');
        $dir->addAttribute('key', 'clients');
        $dir->addAttribute('title', 'clients');
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

    public static function getLastUpdate($catalogs)
    {
        $last_update = 0;
        foreach ($catalogs as $id) {
            $catalog = Catalog::create_from_id($id);
            if ($catalog->last_add > $last_update) {
                $last_update = $catalog->last_add;
            }
            if ($catalog->last_update > $last_update) {
                $last_update = $catalog->last_update;
            }
            if ($catalog->last_clean > $last_update) {
                $last_update = $catalog->last_clean;
            }
        }

        return $last_update;
    }

    public static function setSysSections($xml, $catalogs)
    {
        foreach ($catalogs as $id) {
            $catalog = Catalog::create_from_id($id);
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
            $ip = self::getServerAddress();
            $port = self::getServerPort();
            $dir->addAttribute('host', $ip);
            $dir->addAttribute('local', ($ip == "127.0.0.1") ? '1' : '0');
            $dir->addAttribute('port', $port);
            self::setSectionXContent($dir, $catalog, 'title');
        }
    }

    public static function setSections($xml, $catalogs)
    {
        foreach ($catalogs as $id) {
            $catalog = Catalog::create_from_id($id);
            $catalog->format();
            $dir = $xml->addChild('Directory');
            $dir->addAttribute('filters', '1');
            $dir->addAttribute('refreshing', '0');
            $dir->addAttribute('key', $id);
            $dir->addAttribute('type', 'artist');
            $dir->addAttribute('agent', 'com.plexapp.agents.none'); // com.plexapp.agents.lastfm
            $dir->addAttribute('scanner', 'Plex Music Scanner');
            $dir->addAttribute('language', 'en');
            $dir->addAttribute('uuid', self::uuidFromSubKey($id));
            $dir->addAttribute('updatedAt', self::getLastUpdate($catalogs));
            self::setSectionXContent($dir, $catalog, 'title');
            //$date = new DateTime("2013-01-01");
            //$dir->addAttribute('createdAt', $date->getTimestamp());

            $location = $dir->addChild('Location');
            $location->addAttribute('id', $id);
            $location->addAttribute('path', $catalog->f_full_info);
        }

        $xml->addAttribute('allowSync', '0');
        self::setContainerTitle($xml, 'Plex Library');
    }

    public static function setLibraryContent($xml)
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

    public static function setSectionContent($xml, $catalog)
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
        self::setSectionXContent($xml, $catalog);
    }

    public static function setSectionXContent($xml, $catalog, $title = 'title1')
    {
        $xml->addAttribute('art', self::getResourceUri('artist-fanart.jpg'));
        $xml->addAttribute('thumb', self::getResourceUri('artist.png'));
        $xml->addAttribute($title, $catalog->name);
    }

    public static function setSystemContent($xml)
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

    public static function setSectionAll($xml, $catalog)
    {
        $artists = Catalog::get_artists(array($catalog->id));

        $xml->addAttribute('allowSync', '1');
        self::setSectionXContent($xml, $catalog);
        $xml->addAttribute('title2', 'All Artists');
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('viewGroup', 'artist');
        $xml->addAttribute('viewMode', '65592');
        $xml->addAttribute('librarySectionID', $catalog->id);
        $xml->addAttribute('librarySectionUUID', self::uuidFromSubKey($catalog->id));

        foreach ($artists as $artist) {
            self::addArtist($xml, $artist);
        }
    }

    public static function setSectionAlbums($xml, $catalog)
    {
        $albums = $catalog->get_album_ids();

        $xml->addAttribute('allowSync', '0');
        self::setSectionXContent($xml, $catalog);
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

    public static function setCustomSectionView($xml, $catalog, $albums)
    {
        $xml->addAttribute('allowSync', '1');
        self::setSectionXContent($xml, $catalog);
        $xml->addAttribute('title2', 'Recently Added');
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('mixedParents', '1');
        $xml->addAttribute('viewGroup', 'album');
        $xml->addAttribute('viewMode', '65592');
        $xml->addAttribute('librarySectionID', $catalog->id);
        $xml->addAttribute('librarySectionUUID', self::uuidFromSubKey($catalog->id));
        self::setSectionXContent($xml, $catalog);

        $data = array();
        $data['album'] = $albums;
        self::_setCustomView($xml, $data);
    }

    public static function setCustomView($xml, $data)
    {
        $xml->addAttribute('allowSync', '0');
        $xml->addAttribute('mixedParents', '1');
        self::_setCustomView($xml, $data);
    }

    protected static function _setCustomView($xml, $data)
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

    public static function setServerInfo($xml, $catalogs)
    {
        $server = $xml->addChild('Server');
        $server->addAttribute('name', self::getServerName());
        $server->addAttribute('host', self::getServerPublicAddress());
        $server->addAttribute('localAddresses', self::getServerAddress());
        $server->addAttribute('port', self::getServerPublicPort());
        $server->addAttribute('machineIdentifier', self::getMachineIdentifier());
        $server->addAttribute('version', self::getPlexVersion());

        self::setSections($xml, $catalogs);
    }

    public static function addArtist($xml, $artist)
    {
        $xdir = $xml->addChild('Directory');
        $id = self::getArtistId($artist->id);
        $xdir->addAttribute('ratingKey', $id);
        $xdir->addAttribute('type', 'artist');
        $xdir->addAttribute('title', $artist->name);
        $xdir->addAttribute('index', '1');
        $xdir->addAttribute('addedAt', '');
        $xdir->addAttribute('updatedAt', '');

        $rating = new Rating($artist->id, "artist");
        $rating_value = $rating->get_average_rating();
        if ($rating_value > 0) {
            $xdir->addAttribute('rating', intval($rating_value * 2));
        }

        self::addArtistMeta($xdir, $artist);

        $tags = Tag::get_top_tags('artist', $artist->id);
        if (is_array($tags)) {
            foreach ($tags as $tag_id=>$value) {
                $tag = new Tag($tag_id);
                $xgenre = $xdir->addChild('Genre');
                $xgenre->addAttribute('tag', $tag->name);
            }
        }
    }

    public static function addArtistMeta($xml, $artist)
    {
        $id = self::getArtistId($artist->id);
        if (!isset($xml['key'])) {
            $xml->addAttribute('key', self::getMetadataUri($id) . '/children');
        }
        $xml->addAttribute('summary', $artist->summary);
        self::addArtistThumb($xml, $artist->id);
    }

    protected static function addArtistThumb($xml, $artist_id, $attrthumb = 'thumb')
    {
        $id = self::getArtistId($artist_id);
        $art = new Art($artist_id, 'artist');
        $thumb = '';
        if ($art->get_db()) {
            $thumb = self::getMetadataUri($id) . '/thumb/' . $id;
        }
        $xml->addAttribute($attrthumb, $thumb);
    }

    public static function addAlbum($xml, $album)
    {
        $id = self::getAlbumId($album->id);
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

        $rating = new Rating($album->id, "album");
        $rating_value = $rating->get_average_rating();
        if ($rating_value > 0) {
            $xdir->addAttribute('rating', intval($rating_value * 2));
        }

        $tags = Tag::get_top_tags('album', $album->id);
        if (is_array($tags)) {
            foreach ($tags as $tag_id=>$value) {
                $tag = new Tag($tag_id);
                $xgenre = $xdir->addChild('Genre');
                $xgenre->addAttribute('tag', $tag->name);
            }
        }
    }

    public static function addAlbumMeta($xml, $album)
    {
        $id = self::getAlbumId($album->id);
        $xml->addAttribute('allowSync', '1');
        $xml->addAttribute('librarySectionID', $album->catalog_id);
        $xml->addAttribute('librarySectionUUID', self::uuidFromkey($album->catalog_id));
        $xml->addAttribute('type', 'album');
        $xml->addAttribute('summary', '');
        $xml->addAttribute('index', '1');
        if ($album->has_art || $album->has_thumb) {
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

    public static function setArtistRoot($xml, $artist)
    {
        $id = self::getAlbumId($artist->id);
        $xml->addAttribute('key', $id);
        self::addArtistMeta($xml, $artist);
        $xml->addAttribute('allowSync', '1');
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('parentIndex', '1'); // ??
        $xml->addAttribute('parentTitle', $artist->name);
        $xml->addAttribute('title1', ''); // Should be catalog name
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

    public static function setAlbumRoot($xml, $album)
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

     public static function addSong($xml, $song)
     {
        $xdir = $xml->addChild('Track');
        self::addSongMeta($xdir, $song);
        $time = $song->time * 1000;
        $xdir->addAttribute('title', $song->title);
        $albumid = self::getAlbumId($song->album);
        $album = new Album($song->album);
        $xdir->addAttribute('parentRatingKey', $albumid);
        $xdir->addAttribute('parentKey', self::getMetadataUri($albumid));
        $xdir->addAttribute('originalTitle', $album->f_name);
        $xdir->addAttribute('summary', '');
        $xdir->addAttribute('index', $song->track);
        $xdir->addAttribute('duration', $time);
        $xdir->addAttribute('type', 'track');
        $xdir->addAttribute('addedAt', '');
        $xdir->addAttribute('updatedAt', '');

        $rating = new Rating($song->id, "song");
        $rating_value = $rating->get_average_rating();
        if ($rating_value > 0) {
            $xdir->addAttribute('rating', intval($rating_value * 2));
        }

        $xmedia = $xdir->addChild('Media');
        $mediaid = self::getMediaId($song->id);
        $xmedia->addAttribute('id', $mediaid);
        $xmedia->addAttribute('duration', $time);
        $xmedia->addAttribute('bitrate', intval($song->bitrate / 1000));
        $xmedia->addAttribute('audioChannels', '');
        // Type != Codec != Container, but that's how Ampache works today...
        $xmedia->addAttribute('audioCodec', $song->type);
        $xmedia->addAttribute('container', $song->type);

        $xpart = $xmedia->addChild('Part');
        $partid = self::getPartId($song->id);
        $xpart->addAttribute('id', $partid);
        $xpart->addAttribute('key', self::getPartUri($partid, $song->type));
        $xpart->addAttribute('duration', $time);
        $xpart->addAttribute('file', $song->file);
        $xpart->addAttribute('size', $song->size);
        $xpart->addAttribute('container', $song->type);
     }

    public static function addSongMeta($xml, $song)
    {
        $id = self::getTrackId($song->id);
        $xml->addAttribute('ratingKey', $id);
        $xml->addAttribute('key', self::getMetadataUri($id));

        return $xml;
    }

    public static function createMyPlexAccount()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><MyPlex/>');
        $myplex_username = self::getMyPlexUsername();
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

    public static function setSysAgents($xml)
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
        self::addNoneAgentMediaType($agent, 'Personal Media Artists', '8');
        self::addNoneAgentMediaType($agent, 'Personal Media', '1');
        self::addNoneAgentMediaType($agent, 'Personal Media Shows', '2');
        self::addNoneAgentMediaType($agent, 'Photos', '13');
        self::addNoneAgentMediaType($agent, 'Personal Media Albums', '9');
    }

    protected static function addNoneAgentMediaType($xml, $name, $type)
    {
        $media = $xml->addChild('MediaType');
        $media->addAttribute('name', $name);
        $media->addAttribute('mediaType', $type);
        self::addLanguages($media, 'xn');
    }

    protected static function addLanguages($xml, $languages)
    {
        $langs = explode(',', $languages);
        foreach ($langs as $lang) {
            $lg = $xml->addChild('Language');
            $lg->addAttribute('code', $lang);
        }
    }

    protected static function addNoneAgent($xml, $name)
    {
        self::addAgent($xml, $name, '0', 'com.plexapp.agents.none', true, 'xn');
    }

    protected static function addAgent($xml, $name, $hasPrefs, $identifier, $enabled = false, $langs='')
    {
        $agent = $xml->addChild('Agent');
        $agent->addAttribute('name', $name);
        if ($enabled) {
            $agent->addAttribute('enabled', ($enabled) ? '1' : '0');
        }
        $agent->addAttribute('hasPrefs', $hasPrefs);
        $agent->addAttribute('identifier', $identifier);
        if (!empty($langs)) {
            self::addLanguages($agent, $langs);
        }
        return $agent;
    }

    public static function setSysMovieAgents($xml)
    {
        self::addNoneAgent($xml, 'Personal Media');
    }

    public static function setSysTVShowAgents($xml)
    {
        self::addNoneAgent($xml, 'Personal Media Shows');
    }

    public static function setSysPhotoAgents($xml)
    {
        self::addNoneAgent($xml, 'Photos');
    }

    public static function setSysMusicAgents($xml, $category = 'Artists')
    {
        self::addNoneAgent($xml, 'Personal Media ' . $category);
        //self::addAgent($xml, 'Last.fm', '1', 'com.plexapp.agents.lastfm', 'true', 'en,sv,fr,es,de,pl,it,pt,ja,tr,ru,zh');
    }

    public static function setAgentsContributors($xml, $mediaType, $primaryAgent)
    {
        if ($primaryAgent == 'com.plexapp.agents.none') {
            $type = '';
            switch ($mediaType) {
                case '1':
                    $type = 'Movies';
                break;
                case '2':
                    $type = 'TV';
                break;
                case '13':
                    $type = 'Photos';
                break;
                case '8':
                    $type = 'Artists';
                break;
                case '9':
                    $type = 'Albums';
                break;
            }

            self::addAgent($xml, 'Local Media Assets (' . $type . ')', '0', 'com.plexapp.agents.localmedia', true);
        }
    }

    public static function setAccounts($xml, $userid)
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

    public static function setStatus($xml)
    {
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'sessions');
        $dir->addAttribute('title', 'sessions');
    }

    public static function setPrefs($xml)
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

    protected static function addSettings($xml, $id, $label, $default, $summary, $type, $value, $hidden, $advanced, $group)
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

    public static function setMusicScanners($xml)
    {
        $scanner = $xml->addChild('Scanner');
        $scanner->addAttribute('name', 'Plex Music Scanner');
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

    public static function setMyPlexSubscription($xml)
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

    public static function setServices($xml)
    {
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'browse');
        $dir->addAttribute('title', 'browse');
    }

    protected static function getPathDelimiter()
    {
        if (strpos(PHP_OS, 'WIN') === 0)
            return '\\';
        else
            return '/';
    }

    public static function setBrowseService($xml, $path)
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

    public static function addDirPath($xml, $path, $title='', $isHome=false)
    {
        $delim = self::getPathDelimiter();
        $dir = $xml->addChild('Path');
        if ($isHome) {
            $dir->addAttribute('isHome', '1');
        }
        if (empty($title)) {
            $pp = explode($delim, $path);
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
}
