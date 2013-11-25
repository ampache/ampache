<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

    public static function createFailedResponse($version = "")
    {
        $response = self::createResponse($version);
        $response->addAttribute('status', 'failed');
        return $response;
    }

    public static function createSuccessResponse($version = "")
    {
        $response = self::createResponse($version);
        $response->addAttribute('status', 'ok');
        return $response;
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

    public static function getPartUri($key, $type)
    {
        return '/library/parts/' . $key . '/file.' . $type;
    }

    public static function uuidFromKey($key)
    {
        return hash('sha1', $key);
    }

    public static function setRootContent($xml, $catalogs)
    {
        $xml->addAttribute('friendlyName', 'Ampache');
        $xml->addAttribute('machineIdentifier', self::uuidFromKey($_SERVER['SERVER_ADDR']));
        $xml->addAttribute('myPlex', '0');
        $xml->addAttribute('platform', PHP_OS);
        $xml->addAttribute('platformVersion', '');
        $xml->addAttribute('requestParametersInCookie', '1');
        $xml->addAttribute('sync', '1');
        $xml->addAttribute('transcoderActiveVideoSessions', '0');
        $xml->addAttribute('transcoderAudio', '0');
        $xml->addAttribute('transcoderVideo', '0');
        $xml->addAttribute('updatedAt', self::getLastUpdate($catalogs));
        $xml->addAttribute('version', '0.9.8.10.215-020456b');

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
        $dir->addAttribute('key', 'playQueues');
        $dir->addAttribute('title', 'playQueues');
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
            $dir->addAttribute('scanner', 'Plex Music Scanner');
            $dir->addAttribute('language', 'en');
            $dir->addAttribute('uuid', self::uuidFromKey($id));
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
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'genre');
        $dir->addAttribute('secondary', '1');
        $dir->addAttribute('title', 'By Genre');
        $dir = $xml->addChild('Directory');
        $dir->addAttribute('key', 'recentlyAdded');
        $dir->addAttribute('title', 'Recently Added');

        $dir = $xml->addChild('Directory');
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
        $dir->addAttribute('title', 'Search Tracks...');

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
        $xml->addAttribute('librarySectionUUID', self::uuidFromKey($catalog->id));

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

    public static function addArtist($xml, $artist)
    {
        $xdir = $xml->addChild('Directory');
        $xdir->addAttribute('type', 'artist');
        $xdir->addAttribute('title', $artist->name);
        $xdir->addAttribute('index', '1');
        $xdir->addAttribute('addedAt', '');
        $xdir->addAttribute('updatedAt', '');
        self::addArtistMeta($xdir, $artist);

        $tags = Tag::get_top_tags('artist', $artist->id);
        foreach ($tags as $tag_id=>$value) {
            $tag = new Tag($tag_id);
            $xgenre = $xdir->addChild('Genre');
            $xgenre->addAttribute('tag', $tag->name);
        }
    }

    public static function addArtistMeta($xml, $artist)
    {
        $id = self::getArtistId($artist->id);
        $xml->addAttribute('ratingKey', $id);
        $xml->addAttribute('key', self::getMetadataUri($id) . '/children');
        $xml->addAttribute('summary', '');
        $xml->addAttribute('thumb', '');
    }

    public static function addAlbum($xml, $album)
    {
        $xdir = $xml->addChild('Directory');
        self::addAlbumMeta($xdir, $album);
        $xdir->addAttribute('title', $album->f_title);
        $artistid = self::getArtistId($album->artist_id);
        $xdir->addAttribute('parentRatingKey', $artistid);
        $xdir->addAttribute('parentKey', self::getMetadataUri($artistid));
        $xdir->addAttribute('parentTitle', $album->f_artist);
        $xdir->addAttribute('leafCount', $album->song_count);
        if ($album->year != 0 && $album->year != 'N/A') {
            $xdir->addAttribute('year', $album->year);
        }

        $tags = Tag::get_top_tags('album', $album->id);
        foreach ($tags as $tag_id=>$value) {
            $tag = new Tag($tag_id);
            $xgenre = $xdir->addChild('Genre');
            $xgenre->addAttribute('tag', $tag->name);
        }
    }

    public static function addAlbumMeta($xml, $album)
    {
        $id = self::getAlbumId($album->id);
        $xml->addAttribute('allowSync', '1');
        $xml->addAttribute('ratingKey', $id);
        $xml->addAttribute('librarySectionID', $album->catalog_id);
        $xml->addAttribute('librarySectionUUID', self::uuidFromkey($album->catalog_id));
        $xml->addAttribute('key', self::getMetadataUri($id) . '/children');
        $xml->addAttribute('type', 'album');
        $xml->addAttribute('summary', '');
        $xml->addAttribute('index', '1');
        if ($album->has_art) {
            $xml->addAttribute('art', self::getMetadataUri($id) . '/thumb/' . $id);
        }
        if ($album->has_thumb) {
            $xml->addAttribute('thumb', self::getMetadataUri($id) . '/thumb/' . $id);
        }
        $xml->addAttribute('parentThumb', '');
        $xml->addAttribute('originallyAvailableAt', '');
        $xml->addAttribute('addedAt', '');
        $xml->addAttribute('updatedAt', '');
    }

    public static function setArtistRoot($xml, $artist)
    {
        self::addArtistMeta($xml, $artist);
        $xml->addAttribute('allowSync', '1');
        $xml->addAttribute('nocache', '1');
        $xml->addAttribute('parentIndex', '1'); // ??
        $xml->addAttribute('parentTitle', $artist->name);
        $xml->addAttribute('title1', ''); // Should be catalog name
        $xml->addAttribute('title2', $artist->name);
        $xml->addAttribute('viewGroup', 'album');
        $xml->addAttribute('viewMode', '65592');

        $allalbums = $artist->get_albums();
        foreach ($allalbums as $id) {
            $album = new Album($id);
            $album->format();
            self::addAlbum($xml, $album);
        }
    }

    public static function setAlbumRoot($xml, $album)
    {
        self::addAlbumMeta($xml, $album);
        $xml->addAttribute('grandparentTitle', $album->f_artist);
        $xml->addAttribute('title1', $album->f_artist);
        $xml->addAttribute('allowSync', '1');
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
        foreach ($allsongs as $id) {
            $song = new Song($id);
            self::addSong($xml, $song);
        }
    }

     public static function addSong($xml, $song)
     {
        $xdir = $xml->addChild('Track');
        self::addSongMeta($xdir, $song);
        $xdir->addAttribute('title', $song->title);
        $albumid = self::getAlbumId($song->album);
        $xdir->addAttribute('parentRatingKey', $albumid);
        $xdir->addAttribute('parentKey', self::getMetadataUri($albumid));
        $xdir->addAttribute('originalTitle', $album->f_artist_full);
        $xdir->addAttribute('summary', '');
        $xdir->addAttribute('index', '1');
        $xdir->addAttribute('duration', $song->time);
        $xdir->addAttribute('type', 'track');
        $xdir->addAttribute('addedAt', '');
        $xdir->addAttribute('updatedAt', '');

        $xmedia = $xdir->addChild('Media');
        $mediaid = self::getMediaId($song->id);
        $xmedia->addAttribute('id', $mediaid);
        $xmedia->addAttribute('duration', $song->time);
        $xmedia->addAttribute('bitrate', $song->bitrate);
        $xmedia->addAttribute('audioChannels', '');
        // Type != Codec != Container, but that's how Ampache works today...
        $xmedia->addAttribute('audioCodec', $song->type);
        $xmedia->addAttribute('container', $song->type);

        $xpart = $xmedia->addChild('Part');
        $partid = self::getPartId($song->id);
        $xpart->addAttribute('id', $partid);
        $xpart->addAttribute('key', self::getPartUri($partid, $song->type));
        $xpart->addAttribute('duration', $song->time);
        $xpart->addAttribute('file', $song->file);
        $xpart->addAttribute('size', $song->size);
        $xpart->addAttribute('container', $song->type);
     }

    public static function addSongMeta($xml, $song)
    {
        $id = self::getTrackId($song->id);
        $xml->addAttribute('ratingKey', $id);
        $xml->addAttribute('key', self::getMetadataUri($id));

        return $xsong;
    }
}
