<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
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
 * Art Class
 *
 * This class handles the images / artwork in ampache
 * This was initially in the album class, but was pulled out
 * to be more general and potentially apply to albums, artists, movies etc
 */
class Art extends database_object {

    public $type;
    public $uid; // UID of the object not ID because it's not the ART.ID
    public $raw; // Raw art data
    public $raw_mime;

    public $thumb;
    public $thumb_mime;

    private static $enabled;

    /**
     * Constructor
     * Art constructor, takes the UID of the object and the
     * object type.
     */
    public function __construct($uid, $type = 'album') {

        $this->type = Art::validate_type($type);
        $this->uid = $uid;

    } // constructor

    /**
     * build_cache
     * This attempts to reduce # of queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point
     */
    public static function build_cache($object_ids) {

        if (!is_array($object_ids) || !count($object_ids)) { return false; }
        $uidlist = '(' . implode(',', $object_ids) . ')';
        $sql = "SELECT `object_type`, `object_id`, `mime`, `size` FROM `image` WHERE `object_id` IN $uidlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('art', $row['object_type'] . 
                $row['object_id'] . $row['size'], $row);
        }

        return true;
    } // build_cache


    /**
     * _auto_init
     * Called on creation of the class
     */
    public static function _auto_init() {
        if (!isset($_SESSION['art_enabled'])) {
            $_SESSION['art_enabled'] = (Config::get('bandwidth') > 25);
        }
        self::$enabled = make_bool($_SESSION['art_enabled']);
    }

    /**
     * is_enabled
     * Checks whether the user currently wants art
     */
    public static function is_enabled() {
        if (self::$enabled) {
            return true;
        }

        return false;
    }

    /**
     * set_enabled
     * Changes the value of enabled
     */
    public static function set_enabled($value = null) {
        if (is_null($value)) {
            self::$enabled = self::$enabled ? false : true;
        }
        else {
            self::$enabled = make_bool($value);
        }

        $_SESSION['art_enabled'] = self::$enabled;
    }

    /**
     * validate_type
     * This validates the type
     */
    public static function validate_type($type) {

        switch ($type) {
            case 'album':
            case 'artist':
            case 'video':
                return $type;
            break;
            default:
                return 'album';
            break;
        }

    } // validate_type

    /**
     * extension
     * This returns the file extension for the currently loaded art
     */
    public static function extension($mime) {

        $data = explode("/", $mime);
        $extension = $data['1'];

        if ($extension == 'jpeg') { $extension = 'jpg'; }

        return $extension;

    } // extension

    /**
     * test_image
     * Runs some sanity checks on the putative image
     */
    public static function test_image($source) {
        if (strlen($source) < 10) {
            debug_event('Art', 'Invalid image passed', 1);
            return false;
        }

        // Check to make sure PHP:GD exists.  If so, we can sanity check
        // the image.
        if (function_exists('ImageCreateFromString')) {
             $image = ImageCreateFromString($source);
             if (!$image || imagesx($image) < 5 || imagesy($image) < 5) {
                debug_event('Art', 'Image failed PHP-GD test',1);
                return false;
            }
        }

        return true;
    } //test_image

    /**
     * get
     * This returns the art for our current object, this can
     * look in the database and will return the thumb if it
     * exists, if it doesn't depending on settings it will try
     * to create it.
     */
    public function get($raw=false) {

        // Get the data either way
        if (!$this->get_db()) {
            return false;
        }

        if ($raw || !$this->thumb) {
            return $this->raw;
        }
        else {
            return $this->thumb;
        }

    } // get


    /**
     * get_db
     * This pulls the information out from the database, depending
     * on if we want to resize and if there is not a thumbnail go
     * ahead and try to resize
     */
    public function get_db() {

        $type = Dba::escape($this->type);
        $id = Dba::escape($this->uid);

        $sql = "SELECT `image`, `mime`, `size` FROM `image` WHERE `object_type`='$type' AND `object_id`='$id'";
        $db_results = Dba::read($sql);

        while ($results = Dba::fetch_assoc($db_results)) {
            if ($results['size'] == 'original') {
                $this->raw = $results['image'];
                $this->raw_mime = $results['mime'];
            }
            else if (Config::get('resize_images') &&
                    $results['size'] == '275x275') {
                $this->thumb = $results['image'];
                $this->raw_mime = $results['mime'];
            }
        }
        // If we get nothing return false
        if (!$this->raw) { return false; }

        // If there is no thumb and we want thumbs
        if (!$this->thumb && Config::get('resize_images')) {
            $data = $this->generate_thumb($this->raw, array('width' => 275, 'height' => 275), $this->raw_mime);
            // If it works save it!
            if ($data) {
                $this->save_thumb($data['thumb'], $data['thumb_mime'], '275x275');
                $this->thumb = $data['thumb'];
                $this->thumb_mime = $data['thumb_mime'];
            }
            else {
                debug_event('Art','Unable to retrieve or generate thumbnail for ' . $type . '::' . $id,1);
            }
        } // if no thumb, but art and we want to resize

        return true;

    } // get_db

    /**
     * insert
     * This takes the string representation of an image and inserts it into
     * the database. You must also pass the mime type.
     */
    public function insert($source, $mime) {

        // Disabled in demo mode cause people suck and upload porn
        if (Config::get('demo_mode')) { return false; }

        // Check to make sure we like this image
        if (!self::test_image($source)) {
            debug_event('Art', 'Not inserting image, invalid data passed', 1);
            return false;
        }

        // Default to image/jpeg if they don't pass anything
        $mime = $mime ? $mime : 'image/jpeg';

        $image = Dba::escape($source);
        $mime = Dba::escape($mime);
        $uid = Dba::escape($this->uid);
        $type = Dba::escape($this->type);

        // Blow it away!
        $this->reset();

        // Insert it!
        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `object_type`, `object_id`) VALUES('$image', '$mime', 'original', '$type', '$uid')";
        $db_results = Dba::write($sql);

        return true;

    } // insert

    /**
     * reset
     * This resets the art in the database
     */
    public function reset() {

        $type = Dba::escape($this->type);
        $uid = Dba::escape($this->uid);

        $sql = "DELETE FROM `image` WHERE `object_id`='$uid' AND `object_type`='$type'";
        $db_results = Dba::write($sql);

    } // reset

    /**
     * save_thumb
     * This saves the thumbnail that we're passed
     */
    public function save_thumb($source, $mime, $size) {

        // Quick sanity check
        if (!self::test_image($source)) {
            debug_event('Art', 'Not inserting thumbnail, invalid data passed', 1);
            return false;
        }

        $source = Dba::escape($source);
        $mime = Dba::escape($mime);
        $size = Dba::escape($size);
        $uid = Dba::escape($this->uid);
        $type = Dba::escape($this->type);

        $sql = "DELETE FROM `image` WHERE `object_id`='$uid' AND `object_type`='$type' AND `size`='$size'";
        $db_results = Dba::write($sql);

        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `object_type`, `object_id`) VALUES('$source', '$mime', '$size', '$type', '$uid')";
        $db_results = Dba::write($sql);

    } // save_thumb

    /**
     * get_thumb
     * Returns the specified resized image.  If the requested size doesn't
     * already exist, create and cache it.
     */
    public function get_thumb($size) {
        $sizetext = $size['width'] . 'x' . $size['height'];
        $sizetext = Dba::escape($sizetext);
        $type = Dba::escape($this->type);
        $uid = Dba::escape($this->uid);

        $sql = "SELECT `image`, `mime` FROM `image` WHERE `size`='$sizetext' AND `object_type`='$type' AND `object_id`='$uid'";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);
        if (count($results)) {
            return array('thumb' => $results['image'],
                'thumb_mime' => $results['mime']);
        }

        // If we didn't get a result
        $results = $this->generate_thumb($this->raw, $size, $this->raw_mime);
        if ($results) {
            $this->save_thumb($results['thumb'], $results['thumb_mime'], $sizetext);
        }

        return $results;
    } // get_thumb

    /**
     * generate_thumb
     * Automatically resizes the image for thumbnail viewing.
     * Only works on gif/jpg/png/bmp. Fails if PHP-GD isn't available
     * or lacks support for the requested image type.
     */
    public function generate_thumb($image,$size,$mime) {

        $data = explode("/",$mime);
        $type = strtolower($data['1']);

        if (!self::test_image($image)) {
            debug_event('Art', 'Not trying to generate thumbnail, invalid data passed', 1);
            return false;
        }

        if (!function_exists('gd_info')) {
            debug_event('Art','PHP-GD Not found - unable to resize art',1);
            return false;
        }

        // Check and make sure we can resize what you've asked us to
        if (($type == 'jpg' OR $type == 'jpeg') AND !(imagetypes() & IMG_JPG)) {
            debug_event('Art','PHP-GD Does not support JPGs - unable to resize',1);
            return false;
        }
        if ($type == 'png' AND !imagetypes() & IMG_PNG) {
            debug_event('Art','PHP-GD Does not support PNGs - unable to resize',1);
            return false;
        }
        if ($type == 'gif' AND !imagetypes() & IMG_GIF) {
            debug_event('Art','PHP-GD Does not support GIFs - unable to resize',1);
            return false;
        }
        if ($type == 'bmp' AND !imagetypes() & IMG_WBMP) {
            debug_event('Art','PHP-GD Does not support BMPs - unable to resize',1);
            return false;
        }

        $source = imagecreatefromstring($image);

        if (!$source) {
            debug_event('Art','Failed to create Image from string - Source Image is damaged / malformed',1);
            return false;
        }

        $source_size = array('height' => imagesy($source), 'width' => imagesx($source));

        // Create a new blank image of the correct size
        $thumbnail = imagecreatetruecolor($size['width'], $size['height']);

        if (!imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $size['width'], $size['height'], $source_size['width'], $source_size['height'])) {
            debug_event('Art','Unable to create resized image',1);
            return false;
        }

        // Start output buffer
        ob_start();

        // Generate the image to our OB
        switch ($type) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumbnail, null, 75);
                $mime_type = image_type_to_mime_type(IMAGETYPE_JPEG);
            break;
            case 'gif':
                imagegif($thumbnail);
                $mime_type = image_type_to_mime_type(IMAGETYPE_GIF);
            break;
            // Turn bmps into pngs
            case 'bmp':
                $type = 'png';
            case 'png':
                imagepng($thumbnail);
                $mime_type = image_type_to_mime_type(IMAGETYPE_PNG);
            break;
        } // resized

        $data = ob_get_contents();
        ob_end_clean();

        if (!strlen($data)) {
            debug_event('Art', 'Unknown Error resizing art', 1);
            return false;
        }

        return array('thumb' => $data, 'thumb_mime' => $mime_type);

    } // generate_thumb

    /**
     * get_from_source
     * This gets an image for the album art from a source as
     * defined in the passed array. Because we don't know where
     * it's coming from we are a passed an array that can look like
     * ['url']      = URL *** OPTIONAL ***
     * ['file']     = FILENAME *** OPTIONAL ***
     * ['raw']      = Actual Image data, already captured
     */
    public static function get_from_source($data, $type = 'album') {

        // Already have the data, this often comes from id3tags
        if (isset($data['raw'])) {
            return $data['raw'];
        }

        // If it came from the database
        if (isset($data['db'])) {
            // Repull it
            $uid = Dba::escape($data['db']);
            $type = Dba::escape($type);

            $sql = "SELECT * FROM `image` WHERE `object_type`='$type' AND `object_id`='$uid' AND `size`='original'";
            $db_results = Dba::read($sql);
            $row = Dba::fetch_assoc($db_results);
            return $row['art'];
        } // came from the db

        // Check to see if it's a URL
        if (isset($data['url'])) {
            $snoopy = new Snoopy();
                    if(Config::get('proxy_host') AND Config::get('proxy_port')) {
                        $snoopy->proxy_user = Config::get('proxy_host');
                        $snoopy->proxy_port = Config::get('proxy_port');
                        $snoopy->proxy_user = Config::get('proxy_user');
                        $snoopy->proxy_pass = Config::get('proxy_pass');
                    }
            $snoopy->fetch($data['url']);
            return $snoopy->results;
        }

        // Check to see if it's a FILE
        if (isset($data['file'])) {
            $handle = fopen($data['file'],'rb');
            $image_data = fread($handle,filesize($data['file']));
            fclose($handle);
            return $image_data;
        }

        // Check to see if it is embedded in id3 of a song
        if (isset($data['song'])) {
            // If we find a good one, stop looking
            $getID3 = new getID3();
            $id3 = $getID3->analyze($data['song']);

            if ($id3['format_name'] == "WMA") {
                return $id3['asf']['extended_content_description_object']['content_descriptors']['13']['data'];
            }
            elseif (isset($id3['id3v2']['APIC'])) {
                // Foreach in case they have more then one
                foreach ($id3['id3v2']['APIC'] as $image) {
                    return $image['data'];
                }
            }
        } // if data song

        return false;

    } // get_from_source

    /**
     * url
     * This returns the constructed URL for the art in question
     */
    public static function url($uid,$type,$sid=false) {

        $sid = $sid ? scrub_out($sid) : scrub_out(session_id());
        $type = self::validate_type($type);

        $key = $type . $uid;
        if (parent::is_cached('art', $key . '275x275') && Config::get('resize_images')) {
            $row = parent::get_from_cache('art', $key . '275x275');
            $mime = $row['mime'];
        }
        if (parent::is_cached('art', $key . 'original')) {
            $row = parent::get_from_cache('art', $key . 'original');
            $thumb_mime = $row['mime'];
        }
        if (!$mime && !$thumb_mime) {

            $type = Dba::escape($type);
            $uid = Dba::escape($uid);

            $sql = "SELECT `object_type`, `object_id`, `mime`, `size` FROM `image` WHERE `object_type`='$type' AND `object_id`='$uid'";
            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                parent::add_to_cache('art', $key . $row['size'], $row);
                if ($row['size'] == 'original') {
                    $mime = $row['mime'];
                }
                else if ($row['size'] == '275x275' && Config::get('resize_images')) {
                    $thumb_mime = $row['mime'];
                }
            }
        }

        $mime = $thumb_mime ? $thumb_mime : $mime;
        $extension = self::extension($mime);

        $name = 'art.' . $extension;
        $url = Config::get('web_path') . '/image.php?id=' . scrub_out($uid) . '&object_type=' . scrub_out($type) . '&auth=' . $sid . '&name=' . $name;

        return $url;

    } // url


    /**
     * gc
     * This cleans up art that no longer has a corresponding object
     */
    public static function gc() {
        // iterate over our types and delete the images
        foreach (array('album', 'artist') as $type) {
            $sql = "DELETE FROM `image` USING `image` LEFT JOIN `" .
                $type . "` ON `" . $type . "`.`id`=" .
                "`image`.`object_id` WHERE `object_type`='" .
                $type . "' AND `" . $type . "`.`id` IS NULL";
            $db_results = Dba::write($sql);
        } // foreach
    }

    /**
     * gather
     * This tries to get the art in question
     */
    public function gather($options = array(), $limit = false) {

        // Define vars
        $results = array();

        switch ($this->type) {
            case 'album':
                $allowed_methods = array('db','lastfm','folder','amazon','google','musicbrainz','tags');
            break;
            case 'artist':
                $allowed_methods = array();
            break;
            case 'video':
                $allowed_methods = array();
            break;
        }

        $config = Config::get('art_order');
        $methods = get_class_methods('Art');

        /* If it's not set */
        if (empty($config)) {
            // They don't want art!
            debug_event('Art', 'art_order is empty, skipping art gathering', 3);
            return array();
        }
        elseif (!is_array($config)) {
            $config = array($config);
        }

        debug_event('Art','Searching using:' . json_encode($config), 3);

        foreach ($config as $method) {

            $data = array();

            if (!in_array($method, $allowed_methods)) {
                debug_event('Art', "$method not in allowed_methods, skipping", 3);
                continue;
            }

            $method_name = "gather_" . $method;

            if (in_array($method_name, $methods)) {
                debug_event('Art', "Method used: $method_name", 3);
                // Some of these take options!
                switch ($method_name) {
                    case 'gather_amazon':
                        $data = $this->{$method_name}($limit, $options['keyword']);
                    break;
                    case 'gather_lastfm':
                        $data = $this->{$method_name}($limit, $options);
                    break;
                    default:
                        $data = $this->{$method_name}($limit);
                    break;
                }

                // Add the results we got to the current set
                $results = array_merge($results, (array)$data);

                if ($limit && count($results) >= $limit) {
                    return array_slice($results, 0, $limit);
                }

            } // if the method exists
            else {
                debug_event("Art", "$method_name not defined", 1);
            }

        } // end foreach

        return $results;

    } // gather


    ///////////////////////////////////////////////////////////////////////
    // Art Methods
    ///////////////////////////////////////////////////////////////////////

    /**
     * gather_db
     * This function retrieves art that's already in the database
     */
    public function gather_db($limit = null) {
        if ($this->get_db()) {
            return array('db' => true);
        }
        return array();
    }

    /**
     * gather_musicbrainz
     * This function retrieves art based on MusicBrainz' Advanced
     * Relationships
     */
    public function gather_musicbrainz($limit = 5) {
        $images    = array();
        $num_found = 0;

        if ($this->type == 'album') {
            $album = new Album($this->uid);
        }
        else {
            return $images;
        }

        if ($album->mbid) {
            debug_event('mbz-gatherart', "Album MBID: " . $album->mbid, '5');
        }
        else {
            return $images;
        }

        $mbquery = new MusicBrainzQuery();
        $includes = new mbReleaseIncludes();
        try {
            $release = $mbquery->getReleaseByID($album->mbid, $includes->urlRelations());
        } catch (Exception $e) {
            return $images;
        }

        $asin = $release->getAsin();

        if ($asin) {
            debug_event('mbz-gatherart', "Found ASIN: " . $asin, '5');
            $base_urls = array(
                "01" => "ec1.images-amazon.com",
                "02" => "ec1.images-amazon.com",
                "03" => "ec2.images-amazon.com",
                "08" => "ec1.images-amazon.com",
                "09" => "ec1.images-amazon.com",
            );
            foreach ($base_urls as $server_num => $base_url) {
                // to avoid complicating things even further, we only look for large cover art
                $url = 'http://' . $base_url . '/images/P/' . $asin . '.' . $server_num . '.LZZZZZZZ.jpg';
                debug_event('mbz-gatherart', "Evaluating Amazon URL: " . $url, '5');
                $snoopy = new Snoopy();
                if(Config::get('proxy_host') AND Config::get('proxy_port')) {
                    $snoopy->proxy_user = Config::get('proxy_host');
                    $snoopy->proxy_port = Config::get('proxy_port');
                    $snoopy->proxy_user = Config::get('proxy_user');
                    $snoopy->proxy_pass = Config::get('proxy_pass');
                }
                if ($snoopy->fetch($url)) {
                    $num_found++;
                    debug_event('mbz-gatherart', "Amazon URL added: " . $url, '5');
                    $images[] = array(
                        'url'  => $url,
                        'mime' => 'image/jpeg',
                    );
                    if ($num_found >= $limit) {
                        return $images;
                    }
                }
            }
        }
        // The next bit is based directly on the MusicBrainz server code
        // that displays cover art.
        // I'm leaving in the releaseuri info for the moment, though
        // it's not going to be used.
        $coverartsites[] = array(
            'name' => "CD Baby",
            'domain' => "cdbaby.com",
            'regexp' => '@http://cdbaby\.com/cd/(\w)(\w)(\w*)@',
            'imguri' => 'http://cdbaby.name/$matches[1]/$matches[2]/$matches[1]$matches[2]$matches[3].jpg',
            'releaseuri' => 'http://cdbaby.com/cd/$matches[1]$matches[2]$matches[3]/from/musicbrainz',
        );
        $coverartsites[] = array(
            'name' => "CD Baby",
            'domain' => "cdbaby.name",
            'regexp' => "@http://cdbaby\.name/([a-z0-9])/([a-z0-9])/([A-Za-z0-9]*).jpg@",
            'imguri' => 'http://cdbaby.name/$matches[1]/$matches[2]/$matches[3].jpg',
            'releaseuri' => 'http://cdbaby.com/cd/$matches[3]/from/musicbrainz',
        );
        $coverartsites[] = array(
            'name' => 'archive.org',
            'domain' => 'archive.org',
            'regexp' => '/^(.*\.(jpg|jpeg|png|gif))$/',
            'imguri' => '$matches[1]',
            'releaseuri' => '',
        );
        $coverartsites[] = array(
            'name' => "Jamendo",
            'domain' => "www.jamendo.com",
            'regexp' => '/http://www\.jamendo\.com/(\w\w/)?album/(\d+)/',
            'imguri' => 'http://img.jamendo.com/albums/$matches[2]/covers/1.200.jpg',
            'releaseuri' => 'http://www.jamendo.com/album/$matches[2]',
        );
        $coverartsites[] = array(
            'name' => '8bitpeoples.com',
            'domain' => '8bitpeoples.com',
            'regexp' => '/^(.*)$/',
            'imguri' => '$matches[1]',
            'releaseuri' => '',
        );
        $coverartsites[] = array(
            'name' => 'Encyclopédisque',
            'domain' => 'encyclopedisque.fr',
            'regexp' => '/http://www.encyclopedisque.fr/images/imgdb/(thumb250|main)/(\d+).jpg/',
            'imguri' => 'http://www.encyclopedisque.fr/images/imgdb/thumb250/$matches[2].jpg',
            'releaseuri' => 'http://www.encyclopedisque.fr/',
        );
        $coverartsites[] = array(
            'name' => 'Thastrom',
            'domain' => 'www.thastrom.se',
            'regexp' => '/^(.*)$/',
            'imguri' => '$matches[1]',
            'releaseuri' => '',
        );
        $coverartsites[] = array(
            'name' => 'Universal Poplab',
            'domain' => 'www.universalpoplab.com',
            'regexp' => '/^(.*)$/',
            'imguri' => '$matches[1]',
            'releaseuri' => '',
        );
        foreach ($release->getRelations(mbRelation::TO_URL) as $ar) {
            $arurl = $ar->getTargetId();
            debug_event('mbz-gatherart', "Found URL AR: " . $arurl , '5');
            foreach ($coverartsites as $casite) {
                if (strpos($arurl, $casite['domain']) !== false) {
                    debug_event('mbz-gatherart', "Matched coverart site: " . $casite['name'], '5');
                    if (preg_match($casite['regexp'], $arurl, $matches)) {
                        $num_found++;
                        eval("\$url = \"$casite[imguri]\";");
                        debug_event('mbz-gatherart', "Generated URL added: " . $url, '5');
                        $images[] = array(
                            'url'  => $url,
                            'mime' => 'image/jpeg',
                        );
                        if ($num_found >= $limit) {
                            return $images;
                        }
                    }
                }
            } // end foreach coverart sites
        } // end foreach

        return $images;

    } // gather_musicbrainz

    /**
     * gather_amazon
     * This takes keywords and performs a search of the Amazon website
     * for the art. It returns an array of found objects with mime/url keys
     */
    public function gather_amazon($limit = 5, $keywords = '') {

        $images     = array();
        $final_results  = array();
        $possible_keys = array(
            'LargeImage',
            'MediumImage',
            'SmallImage'
        );

        // Prevent the script from timing out
        set_time_limit(0);

        if (empty($keywords)) {
            $keywords = $this->full_name;
            /* If this isn't a various album combine with artist name */
            if ($this->artist_count == '1') { $keywords .= ' ' . $this->artist_name; }
        }

        /* Attempt to retrieve the album art order */
        $amazon_base_urls = Config::get('amazon_base_urls');

        /* If it's not set */
        if (!count($amazon_base_urls)) {
            $amazon_base_urls = array('http://webservices.amazon.com');
        }

        /* Foreach through the base urls that we should check */
        foreach ($amazon_base_urls as $amazon_base) {

            // Create the Search Object
            $amazon = new AmazonSearch(Config::get('amazon_developer_public_key'), Config::get('amazon_developer_private_key'), $amazon_base);
                if(Config::get('proxy_host') AND Config::get('proxy_port')) {
                    $proxyhost = Config::get('proxy_host');
                    $proxyport = Config::get('proxy_port');
                    $proxyuser = Config::get('proxy_user');
                    $proxypass = Config::get('proxy_pass');
                    debug_event('amazon', 'setProxy', 5);
                    $amazon->setProxy($proxyhost, $proxyport, $proxyuser, $proxypass);
                }

            $search_results = array();

            /* Set up the needed variables */
            $max_pages_to_search = max(Config::get('max_amazon_results_pages'),$amazon->_default_results_pages);
            $pages_to_search = $max_pages_to_search; //init to max until we know better.
            // while we have pages to search
            do {
                $raw_results = $amazon->search(array('artist'=>$artist,'album'=>$albumname,'keywords'=>$keywords));

                $total = count($raw_results) + count($search_results);

                // If we've gotten more then we wanted
                if ($limit && $total > $limit) {
                    $raw_results = array_slice($raw_results, 0, -($total - $limit), true);

                    debug_event('amazon-xml', "Found $total, limit $limit; reducing and breaking from loop", 5);
                    // Merge the results and BREAK!
                    $search_results = array_merge($search_results,$raw_results);
                    break;
                } // if limit defined

                $search_results = array_merge($search_results,$raw_results);
                $pages_to_search = min($max_pages_to_search, $amazon->_maxPage);
                debug_event('amazon-xml', "Searched results page " . ($amazon->_currentPage+1) . "/" . $pages_to_search,'5');
                $amazon->_currentPage++;

            } while ($amazon->_currentPage < $pages_to_search);


            // Only do the second search if the first actually returns something
            if (count($search_results)) {
                $final_results = $amazon->lookup($search_results);
            }

            /* Log this if we're doin debug */
            debug_event('amazon-xml',"Searched using $keywords with " . Config::get('amazon_developer_key') . " as key, results: " . count($final_results), 5);

            // If we've hit our limit
            if (!empty($limit) && count($final_results) >= $limit) {
                break;
            }

        } // end foreach

        /* Foreach through what we've found */
        foreach ($final_results as $result) {

            /* Recurse through the images found */
            foreach ($possible_keys as $key) {
                if (strlen($result[$key])) {
                    break;
                }
            } // foreach

            // Rudimentary image type detection, only JPG and GIF allowed.
            if (substr($result[$key], -4 == '.jpg')) {
                $mime = "image/jpeg";
            }
            elseif (substr($result[$key], -4 == '.gif')) {
                $mime = "image/gif";
            }
            elseif (substr($result[$key], -4 == '.png')) {
                $mime = "image/png";
            }
            else {
                /* Just go to the next result */
                continue;
            }

            $data['url']    = $result[$key];
            $data['mime']   = $mime;

            $images[] = $data;

            if (!empty($limit)) {
                if (count($images) >= $limit) {
                    return $images;
                }
            }

        } // if we've got something

        return $images;

    } // gather_amazon

    /**
     * gather_folder
     * This returns the art from the folder of the files
     * If a limit is passed or the preferred filename is found the current
     * results set is returned
     */
    public function gather_folder($limit = 5) {

        $media = new Album($this->uid);
        $songs = $media->get_songs();
        $results = array();
        $preferred = false;
        // For storing which directories we've already done
        $processed = array();

        /* See if we are looking for a specific filename */
        $preferred_filename = Config::get('album_art_preferred_filename');

        // Array of valid extensions
        $image_extensions = array(
            'bmp',
            'gif',
            'jp2',
            'jpeg',
            'jpg',
            'png'
        );

        foreach ($songs as $song_id) {
            $data = array();
            $song = new Song($song_id);
            $dir = dirname($song->file);

            if (isset($processed[$dir])) {
                continue;
            }

            debug_event('folder_art', "Opening $dir and checking for Album Art", 3);

            /* Open up the directory */
            $handle = opendir($dir);

            if (!$handle) {
                Error::add('general', T_('Error: Unable to open') . ' ' . $dir);
                debug_event('folder_art', "Error: Unable to open $dir for album art read", 2);
                continue;
            }

            $processed[$dir] = true;

            // Recurse through this dir and create the files array
            while ($file = readdir($handle)) {
                $extension = pathinfo($file);
                $extension = $extension['extension'];

                // Make sure it looks like an image file
                if (!in_array($extension, $image_extensions)) {
                    continue;
                }

                $full_filename = $dir . '/' . $file;

                // Make sure it's got something in it
                if (!filesize($full_filename)) {
                    debug_event('folder_art', "Empty file, rejecting $file", 5);
                    continue;
                }

                // Regularise for mime type
                if ($extension == 'jpg') {
                    $extension = 'jpeg';
                }

                // Take an md5sum so we don't show duplicate
                // files.
                $index = md5($full_filename);

                if ($file == $preferred_filename) {
                    // We found the preferred filename and
                    // so we're done.
                    debug_event('folder_art', "Found preferred image file: $file", 5);
                    $preferred[$index] = array(
                        'file' => $full_filename,
                        'mime' => 'image/' . $extension
                    );
                    break;    
                }

                debug_event('folder_art', "Found image file: $file", 5);
                $results[$index] = array(
                    'file' => $full_filename,
                    'mime' => 'image/' . $extension
                );

            } // end while reading dir
            closedir($handle);

        } // end foreach songs

        if (is_array($preferred)) {
            // We found our favourite filename somewhere, so we need
            // to dump the other, less sexy ones.
            $results = $preferred;
        }

        debug_event('folder_art', 'Results: ' . json_encode($results), 5);
        if ($limit && count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return array_values($results);

    } // gather_folder

    /**
     * gather_tags
     * This looks for the art in the meta-tags of the file
     * itself
     */
    public function gather_tags($limit = 5) {

        // We need the filenames
        $album = new Album($this->uid);

        // grab the songs and define our results
        $songs = $album->get_songs();
        $data = array();

        // Foreach songs in this album
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            // If we find a good one, stop looking
            $getID3 = new getID3();
            try { $id3 = $getID3->analyze($song->file); }
            catch (Exception $error) {
                debug_event('getid3', $error->message, 1);
            }

            if (isset($id3['asf']['extended_content_description_object']['content_descriptors']['13'])) {
                $image = $id3['asf']['extended_content_description_object']['content_descriptors']['13'];
                $data[] = array(
                    'song' => $song->file,
                    'raw' => $image['data'],
                    'mime' => $image['mime']);
            }

            if (isset($id3['id3v2']['APIC'])) {
                // Foreach in case they have more then one
                foreach ($id3['id3v2']['APIC'] as $image) {
                    $data[] = array(
                        'song' => $song->file,
                        'raw' => $image['data'],
                        'mime' => $image['mime']);
                }
            }

            if ($limit && count($data) >= $limit) {
                return array_slice($data, 0, $limit);
            }

        } // end foreach

        return $data;

    } // gather_tags

    /**
     * gather_google
     * Raw google search to retrieve the art, not very reliable
     */
    public function gather_google($limit = 5) {

        $images = array();
        $media = new $this->type($this->uid);
        $media->format();

        $search = $media->full_name;

        if ($media->artist_count == '1')
            $search = $media->artist_name . ', ' . $search;

        $search = rawurlencode($search);

        $size = '&imgsz=m'; // Medium
        //$size = '&imgsz=l'; // Large

        $html = file_get_contents("http://images.google.com/images?source=hp&q=$search&oq=&um=1&ie=UTF-8&sa=N&tab=wi&start=0&tbo=1$size");

        if(preg_match_all("|\ssrc\=\"(http.+?)\"|", $html, $matches, PREG_PATTERN_ORDER))
            foreach ($matches[1] as $match) {
                $extension = "image/jpeg";

                if (strrpos($extension, '.') !== false) $extension = substr($extension, strrpos($extension, '.') + 1);

                $images[] = array('url' => $match, 'mime' => $extension);
            }

        return $images;

    } // gather_google

    /**
     * gather_lastfm
     * This returns the art from lastfm. It doesn't currently require an
     * account but may in the future.
     */
    public function gather_lastfm($limit, $options = false) {

        // Create the parser object
        $lastfm = new LastFMSearch();

        switch ($this->type) {
            case 'album':
                if (is_array($options)) {
                    $artist = $options['artist'];
                    $album  = $options['album_name'];
                }
                else {
                    $media = new Album($this->uid);
                    $media->format();
                    $artist = $media->artist_name;
                    $album = $media->full_name;
                }
            break;
        }

        if(Config::get('proxy_host') AND Config::get('proxy_port')) {
            $proxyhost = Config::get('proxy_host');
            $proxyport = Config::get('proxy_port');
            $proxyuser = Config::get('proxy_user');
            $proxypass = Config::get('proxy_pass');
            debug_event('LastFM', 'proxy set', 5);
            $lastfm->setProxy($proxyhost, $proxyport, $proxyuser, $proxypass);
        }

        $raw_data = $lastfm->album_search($artist, $album);

        if (!count($raw_data)) { return array(); }

        $coverart = $raw_data['coverart'];
        if (!is_array($coverart)) { return array(); }

        ksort($coverart);
        foreach ($coverart as $url) {
            // We need to check the URL for the /noimage/ stuff
            if (strpos($url, '/noimage/') !== false) {
                debug_event('LastFM', 'Detected as noimage, skipped ' . $url, 3);
                continue;
            }
            
            // HACK: we shouldn't rely on the extension to determine file type
                $results = pathinfo($url);
            $mime = 'image/' . $results['extension'];
            $data[] = array('url' => $url, 'mime' => $mime);
            if ($limit && count($data) >= $limit) {
                return $data;
            }
        } // end foreach

        return $data;

    } // gather_lastfm

} // Art
