<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

use MusicBrainz\MusicBrainz;
use MusicBrainz\Clients\RequestsMbClient;

/**
 * Art Class
 *
 * This class handles the images / artwork in ampache
 * This was initially in the album class, but was pulled out
 * to be more general and potentially apply to albums, artists, movies etc
 */
class Art extends database_object
{
    /**
     *  @var int $id
     */
    public $id;
    /**
     *  @var string $type
     */
    public $type;
    /**
     *  @var int $uid
     */
    public $uid; // UID of the object not ID because it's not the ART.ID
    /**
     *  @var string $raw
     */
    public $raw; // Raw art data
    /**
     *  @var string $raw_mime
     */
    public $raw_mime;
    /**
     *  @var string $kind
     */
    public $kind;

    /**
     *  @var string $thumb
     */
    public $thumb;
    /**
     *  @var string $thumb_mime
     */
    public $thumb_mime;

    /**
     *  @var bool $enabled
     */
    private static $enabled;

    /**
     * Constructor
     * Art constructor, takes the UID of the object and the
     * object type.
     * @param int $uid
     * @param string $type
     * @param string $kind
     */
    public function __construct($uid, $type = 'album', $kind = 'default')
    {
        if (!Core::is_library_item($type))
            return false;
        $this->type = $type;
        $this->uid = $uid;
        $this->kind = $kind;

    } // constructor

    /**
     * build_cache
     * This attempts to reduce # of queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point
     * @param int[] $object_ids
     * @return bool
     */
    public static function build_cache($object_ids)
    {
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
    public static function _auto_init()
    {
        if (!isset($_SESSION['art_enabled'])) {
            /*if (isset($_COOKIE['art_enabled'])) {
                $_SESSION['art_enabled'] = $_COOKIE['art_enabled'];
            } else {*/
                $_SESSION['art_enabled'] = true;
            //}
        }

        self::$enabled = make_bool($_SESSION['art_enabled']);
        //setcookie('art_enabled', self::$enabled, time() + 31536000, "/");
    }

    /**
     * is_enabled
     * Checks whether the user currently wants art
     * @return boolean
     */
    public static function is_enabled()
    {
        if (self::$enabled) {
            return true;
        }

        return false;
    }

    /**
     * set_enabled
     * Changes the value of enabled
     * @param bool|null $value
     */
    public static function set_enabled($value = null)
    {
        if (is_null($value)) {
            self::$enabled = self::$enabled ? false : true;
        } else {
            self::$enabled = make_bool($value);
        }

        $_SESSION['art_enabled'] = self::$enabled;
        //setcookie('art_enabled', self::$enabled, time() + 31536000, "/");
    }

    /**
     * extension
     * This returns the file extension for the currently loaded art
     * @param string $mime
     * @return string
     */
    public static function extension($mime)
    {
        $data = explode("/", $mime);
        $extension = $data['1'];

        if ($extension == 'jpeg') { $extension = 'jpg'; }

        return $extension;

    } // extension

    /**
     * test_image
     * Runs some sanity checks on the putative image
     * @param string $source
     * @return boolean
     */
    public static function test_image($source)
    {
        if (strlen($source) < 10) {
            debug_event('Art', 'Invalid image passed', 1);
            return false;
        }

        $test = true;
        // Check to make sure PHP:GD exists.  If so, we can sanity check
        // the image.
        if (function_exists('ImageCreateFromString')) {
             $image = ImageCreateFromString($source);
             if (!$image || imagesx($image) < 5 || imagesy($image) < 5) {
                debug_event('Art', 'Image failed PHP-GD test',1);
                $test = false;
            }
            @imagedestroy($image);
        }

        return $test;
    } //test_image

    /**
     * get
     * This returns the art for our current object, this can
     * look in the database and will return the thumb if it
     * exists, if it doesn't depending on settings it will try
     * to create it.
     * @param boolean $raw
     * @return string
     */
    public function get($raw=false)
    {
        // Get the data either way
        if (!$this->get_db()) {
            return false;
        }

        if ($raw || !$this->thumb) {
            return $this->raw;
        } else {
            return $this->thumb;
        }

    } // get


    /**
     * get_db
     * This pulls the information out from the database, depending
     * on if we want to resize and if there is not a thumbnail go
     * ahead and try to resize
     * @return boolean
     */
    public function get_db()
    {
        $sql = "SELECT `id`, `image`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `kind` = ?";
        $db_results = Dba::read($sql, array($this->type, $this->uid, $this->kind));

        while ($results = Dba::fetch_assoc($db_results)) {
            if ($results['size'] == 'original') {
                $this->raw = $results['image'];
                $this->raw_mime = $results['mime'];
            } else if (AmpConfig::get('resize_images') &&
                    $results['size'] == '275x275') {
                $this->thumb = $results['image'];
                $this->raw_mime = $results['mime'];
            }
            $this->id = $results['id'];
        }
        // If we get nothing return false
        if (!$this->raw) { return false; }

        // If there is no thumb and we want thumbs
        if (!$this->thumb && AmpConfig::get('resize_images')) {
            $data = $this->generate_thumb($this->raw, array('width' => 275, 'height' => 275), $this->raw_mime);
            // If it works save it!
            if ($data) {
                $this->save_thumb($data['thumb'], $data['thumb_mime'], '275x275');
                $this->thumb = $data['thumb'];
                $this->thumb_mime = $data['thumb_mime'];
            } else {
                debug_event('Art','Unable to retrieve or generate thumbnail for ' . $this->type . '::' . $this->id,1);
            }
        } // if no thumb, but art and we want to resize

        return true;

    } // get_db

    /**
     * This check if an object has an associated image in db.
     * @param int $object_id
     * @param string $object_type
     * @param string $kind
     * @return boolean
     */
    public static function has_db($object_id, $object_type, $kind = 'default')
    {
        $sql = "SELECT COUNT(`id`) AS `nb_img` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `kind` = ?";
        $db_results = Dba::read($sql, array($object_type, $object_id, $kind));
        $nb_img = 0;
        if ($results = Dba::fetch_assoc($db_results)) {
            $nb_img = $results['nb_img'];
        }

        return ($nb_img > 0);
    }

    /**
     * This insert art from url.
     * @param string $url
     */
    public function insert_url($url)
    {
        debug_event('art', 'Insert art from url ' . $url, '5');
        $image = Art::get_from_source(array('url' => $url), $this->type);
        $rurl = pathinfo($url);
        $mime = "image/" . $rurl['extension'];
        $this->insert($image, $mime);
    }

    /**
     * insert
     * This takes the string representation of an image and inserts it into
     * the database. You must also pass the mime type.
     * @param string $source
     * @param string $mime
     * @return boolean
     */
    public function insert($source, $mime = '')
    {
        // Disabled in demo mode cause people suck and upload porn
        if (AmpConfig::get('demo_mode')) { return false; }

        // Check to make sure we like this image
        if (!self::test_image($source)) {
            debug_event('Art', 'Not inserting image, invalid data passed', 1);
            return false;
        }

        // Default to image/jpeg if they don't pass anything
        $mime = $mime ? $mime : 'image/jpeg';
        // Blow it away!
        $this->reset();

        if (AmpConfig::get('write_id3_art')) {
            if ($this->type == 'album') {
                $album = new Album($this->uid );
                debug_event('Art', 'Inserting image Album ' . $album->name . ' on songs.', 5);
                $songs = $album->get_songs();
                foreach ($songs as $song_id) {
                    $song = new Song($song_id);
                    $song->format();
                    $id3 = new vainfo($song->file);
                    $data = $id3->read_id3();
                    if (isset($data['tags']['id3v2'])) {
                        $image_from_tag = '';
                        if (isset($data['id3v2']['APIC'][0]['data'])) {
                            $image_from_tag = $data['id3v2']['APIC'][0]['data'];
                        }
                        if ($image_from_tag != $source) {
                            $ndata = array();
                            $ndata['APIC']['data'] = $source;
                            $ndata['APIC']['mime'] = $mime;
                            $ndata = array_merge($ndata, $song->get_metadata());
                            $id3->write_id3($ndata);
                        }
                    }
                }
            }
        }

        // Insert it!
        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `object_type`, `object_id`, `kind`) VALUES(?, ?, 'original', ?, ?, ?)";
        Dba::write($sql, array($source, $mime, $this->type, $this->uid, $this->kind));

        return true;

    } // insert

    /**
     * reset
     * This resets the art in the database
     */
    public function reset()
    {
        $sql = "DELETE FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `kind` = ?";
        Dba::write($sql, array($this->uid, $this->type, $this->kind));
    } // reset

    /**
     * save_thumb
     * This saves the thumbnail that we're passed
     * @param string $source
     * @param string $mime
     * @param string $size
     */
    public function save_thumb($source, $mime, $size)
    {
        // Quick sanity check
        if (!self::test_image($source)) {
            debug_event('Art', 'Not inserting thumbnail, invalid data passed', 1);
            return false;
        }

        $sql = "DELETE FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `size` = ? AND `kind` = ?";
        Dba::write($sql, array($this->uid, $this->type, $size, $this->kind));

        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `object_type`, `object_id`, `kind`) VALUES(?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($source, $mime, $size, $this->type, $this->uid, $this->kind));
    } // save_thumb

    /**
     * get_thumb
     * Returns the specified resized image.  If the requested size doesn't
     * already exist, create and cache it.
     * @param string $size
     * @return string
     */
    public function get_thumb($size)
    {
        $sizetext = $size['width'] . 'x' . $size['height'];
        $sql = "SELECT `image`, `mime` FROM `image` WHERE `size` = ? AND `object_type` = ? AND `object_id` = ? AND `kind` = ?";
        $db_results = Dba::read($sql, array($sizetext, $this->type, $this->uid, $this->kind));

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
     * @param string $image
     * @param string $size
     * @param string $mime
     * @return string
     */
    public function generate_thumb($image,$size,$mime)
    {
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
            imagedestroy($source);
            imagedestroy($thumbnail);
            return false;
        }
        imagedestroy($source);

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
            case 'png':
                imagepng($thumbnail);
                $mime_type = image_type_to_mime_type(IMAGETYPE_PNG);
            break;
        } // resized

        if (!isset($mime_type)) {
            debug_event('Art', 'Eror: No mime type found.', 1);
            return false;
        }

        $data = ob_get_contents();
        ob_end_clean();

        imagedestroy($thumbnail);
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
     * @param array $data
     * @param string $type
     * @return string
     */
    public static function get_from_source($data, $type = 'album')
    {
        // Already have the data, this often comes from id3tags
        if (isset($data['raw'])) {
            return $data['raw'];
        }

        // If it came from the database
        if (isset($data['db'])) {
            $sql = "SELECT * FROM `image` WHERE `object_type` = ? AND `object_id` =? AND `size`='original'";
            $db_results = Dba::read($sql, array($type, $data['db']));
            $row = Dba::fetch_assoc($db_results);
            return $row['art'];
        } // came from the db

        // Check to see if it's a URL
        if (isset($data['url'])) {
            $options = array();
            if (AmpConfig::get('proxy_host') AND AmpConfig::get('proxy_port')) {
                $proxy = array();
                $proxy[] = AmpConfig::get('proxy_host') . ':' . AmpConfig::get('proxy_port');
                if (AmpConfig::get('proxy_user')) {
                    $proxy[] = AmpConfig::get('proxy_user');
                    $proxy[] = AmpConfig::get('proxy_pass');
                }
                $options['proxy'] = $proxy;
            }
            try {
                $options['timeout'] = 3;
                $request = Requests::get($data['url'], array(), $options);
                $raw = $request->body;
            } catch (Exception $e) {
                debug_event('Art', 'Error getting art: ' . $e->getMessage(), '1');
                $raw = null;
            }

            return $raw;
        }

        // Check to see if it's a FILE
        if (isset($data['file'])) {
            $handle = fopen($data['file'],'rb');
            $image_data = fread($handle,Core::get_filesize($data['file']));
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
            } elseif (isset($id3['id3v2']['APIC'])) {
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
     * @param int $uid
     * @param string $type
     * @param string $sid
     * @return string
     */
    public static function url($uid,$type,$sid=null)
    {
        $sid = $sid ? scrub_out($sid) : scrub_out(session_id());
        if (!Core::is_library_item($type))
            return null;

        $key = $type . $uid;

        if (parent::is_cached('art', $key . '275x275') && AmpConfig::get('resize_images')) {
            $row = parent::get_from_cache('art', $key . '275x275');
            $mime = $row['mime'];
        }
        if (parent::is_cached('art', $key . 'original')) {
            $row = parent::get_from_cache('art', $key . 'original');
            $thumb_mime = $row['mime'];
        }
        if (!isset($mime) && !isset($thumb_mime)) {
            $sql = "SELECT `object_type`, `object_id`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ?";
            $db_results = Dba::read($sql, array($type, $uid));

            while ($row = Dba::fetch_assoc($db_results)) {
                parent::add_to_cache('art', $key . $row['size'], $row);
                if ($row['size'] == 'original') {
                    $mime = $row['mime'];
                } else if ($row['size'] == '275x275' && AmpConfig::get('resize_images')) {
                    $thumb_mime = $row['mime'];
                }
            }
        }

        $mime = isset($thumb_mime) ? $thumb_mime : (isset($mime) ? $mime : null);
        $extension = self::extension($mime);

        if (AmpConfig::get('stream_beautiful_url')) {
            if (empty($extension)) {
                $extension = 'jpg';
            }
            $url = AmpConfig::get('web_path') . '/play/art/' . $sid . '/' . scrub_out($type) . '/' . scrub_out($uid) . '/thumb.' . $extension;
        } else {
            $url = AmpConfig::get('web_path') . '/image.php?object_id=' . scrub_out($uid) . '&object_type=' . scrub_out($type) . '&auth=' . $sid;
            if (!empty($extension)) {
                $name = 'art.' . $extension;
                $url .= '&name=' . $name;
            }
        }

        return $url;

    } // url

    /**
     * gc
     * This cleans up art that no longer has a corresponding object
     */
    public static function gc()
    {
        // iterate over our types and delete the images
        foreach (array('album', 'artist','tvshow','tvshow_season','video','user') as $type) {
            $sql = "DELETE FROM `image` USING `image` LEFT JOIN `" .
                $type . "` ON `" . $type . "`.`id`=" .
                "`image`.`object_id` WHERE `object_type`='" .
                $type . "' AND `" . $type . "`.`id` IS NULL";
            Dba::write($sql);
        } // foreach
    }

    /**
     * gather
     * This tries to get the art in question
     * @param array $options
     * @param int $limit
     * @return array
     */
    public function gather($options = array(), $limit = 0)
    {
        // Define vars
        $results = array();
        $type = $this->type;
        if (isset($options['type'])) {
            $type = $options['type'];
        }

        if (count($options) == 0) {
            debug_event('Art', 'No options for art search, skipped.', 3);
            return array();
        }
        $config = AmpConfig::get('art_order');
        $methods = get_class_methods('Art');

        /* If it's not set */
        if (empty($config)) {
            // They don't want art!
            debug_event('Art', 'art_order is empty, skipping art gathering', 3);
            return array();
        } elseif (!is_array($config)) {
            $config = array($config);
        }

        debug_event('Art','Searching using:' . json_encode($config), 3);

        $plugin_names = Plugin::get_plugins('gather_arts');
        foreach ($config as $method) {
            $method_name = "gather_" . $method;

            $data = array();
            if (in_array($method, $plugin_names)) {
                $plugin = new Plugin($method);
                $installed_version = Plugin::get_plugin_version($plugin->_plugin->name);
                if ($installed_version) {
                    if ($plugin->load($GLOBALS['user'])) {
                        $data = $plugin->_plugin->gather_arts($type, $options, $limit);
                    }
                }
            } else if (in_array($method_name, $methods)) {
                debug_event('Art', "Method used: $method_name", 3);
                // Some of these take options!
                switch ($method_name) {
                    case 'gather_amazon':
                        $data = $this->{$method_name}($limit, $options);
                    break;
                    case 'gather_lastfm':
                        $data = $this->{$method_name}($limit, $options);
                    break;
                    case 'gather_google':
                        $data = $this->{$method_name}($limit, $options);
                    break;
                    default:
                        $data = $this->{$method_name}($limit);
                    break;
                }
            } else {
                debug_event("Art", $method_name . " not defined", 1);
            }

            // Add the results we got to the current set
            $results = array_merge($results, (array) $data);

            if ($limit && count($results) >= $limit) {
                return array_slice($results, 0, $limit);
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
     *
     * @param int|null $limit
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function gather_db($limit = null)
    {
        if ($this->get_db()) {
            return array('db' => true);
        }
        return array();
    }

    /**
     * gather_musicbrainz
     * This function retrieves art based on MusicBrainz' Advanced
     * Relationships
     * @param int $limit
     * @param array $data
     * @return array
     */
    public function gather_musicbrainz($limit = 5, $data = array())
    {
        $images    = array();
        $num_found = 0;

        if ($this->type != 'album') {
            return $images;
        }

        if ($data['mbid']) {
            debug_event('mbz-gatherart', "Album MBID: " . $data['mbid'], '5');
        } else {
            return $images;
        }

        $mb = new MusicBrainz(new RequestsMbClient());
        $includes = array(
            'url-rels'
        );
        try {
            $release = $mb->lookup('release', $data['mbid'], $includes);
        } catch (Exception $e) {
            return $images;
        }

        $asin = $release->asin;

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
                $options = array();
                if (AmpConfig::get('proxy_host') AND AmpConfig::get('proxy_port')) {
                    $proxy = array();
                    $proxy[] = AmpConfig::get('proxy_host') . ':' . AmpConfig::get('proxy_port');
                    if (AmpConfig::get('proxy_user')) {
                        $proxy[] = AmpConfig::get('proxy_user');
                        $proxy[] = AmpConfig::get('proxy_pass');
                    }
                    $options['proxy'] = $proxy;
                }
                $request = Requests::get($url, array(), $options);
                if ($request->status_code == 200) {
                    $num_found++;
                    debug_event('mbz-gatherart', "Amazon URL added: " . $url, '5');
                    $images[] = array(
                        'url'  => $url,
                        'mime' => 'image/jpeg',
                        'title' => 'MusicBrainz'
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
        $coverartsites = array();
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
        foreach ($release->relations as $ar) {
            $arurl = $ar->url->resource;
            debug_event('mbz-gatherart', "Found URL AR: " . $arurl , '5');
            foreach ($coverartsites as $casite) {
                if (strpos($arurl, $casite['domain']) !== false) {
                    debug_event('mbz-gatherart', "Matched coverart site: " . $casite['name'], '5');
                    if (preg_match($casite['regexp'], $arurl, $matches)) {
                        $num_found++;
                        $url = '';
                        eval("\$url = \"$casite[imguri]\";");
                        debug_event('mbz-gatherart', "Generated URL added: " . $url, '5');
                        $images[] = array(
                            'url'  => $url,
                            'mime' => 'image/jpeg',
                            'title' => 'MusicBrainz'
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
     * gather_folder
     * This returns the art from the folder of the files
     * If a limit is passed or the preferred filename is found the current
     * results set is returned
     * @param int $limit
     * @return array
     */
    public function gather_folder($limit = 5)
    {
        if (!$limit) {
            $limit = 5;
        }

        if ($this->type != 'album')
            return array();

        $media = new Album($this->uid);
        $songs = $media->get_songs();
        $results = array();
        $preferred = false;
        // For storing which directories we've already done
        $processed = array();

        /* See if we are looking for a specific filename */
        $preferred_filename = AmpConfig::get('album_art_preferred_filename');

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
                if (!Core::get_filesize($full_filename)) {
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
                        'mime' => 'image/' . $extension,
                        'title' => 'Folder'
                    );
                    break;
                }

                debug_event('folder_art', "Found image file: $file", 5);
                $results[$index] = array(
                    'file' => $full_filename,
                    'mime' => 'image/' . $extension,
                    'title' => 'Folder'
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
     * @param int $limit
     * @return array
     */
    public function gather_tags($limit = 5)
    {
        if (!$limit) {
            $limit = 5;
        }

        if ($this->type == "video") {
            $data = $this->gather_video_tags();
        } elseif ($this->type == 'album') {
            $data = $this->gather_song_tags($limit);
        } else {
            $data = array();
        }

        return $data;
    }

    /**
     * Gather tags from video files.
     * @return array
     */
    public function gather_video_tags()
    {
        $video = new Video($this->uid);
        return $this->gather_media_tags($video);
    }

    /**
     * Gather tags from audio files.
     * @param int $limit
     * @return array
     */
    public function gather_song_tags($limit = 5)
    {
        // We need the filenames
        $album = new Album($this->uid);

        // grab the songs and define our results
        $songs = $album->get_songs();
        $data = array();

        // Foreach songs in this album
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            $data = array_merge($data, $this->gather_media_tags($song));

            if ($limit && count($data) >= $limit) {
                return array_slice($data, 0, $limit);
            }
        }

        return $data;
    }

    /**
     * Gather tags from files.
     * @param media $media
     * @return array
     */
    protected function gather_media_tags($media)
    {
        $mtype = strtolower(get_class($media));
        $data = array();
        $getID3 = new getID3();
        try { $id3 = $getID3->analyze($media->file); } catch (Exception $error) {
            debug_event('getid3', $error->getMessage(), 1);
        }

        if (isset($id3['asf']['extended_content_description_object']['content_descriptors']['13'])) {
            $image = $id3['asf']['extended_content_description_object']['content_descriptors']['13'];
            $data[] = array(
                $mtype => $media->file,
                'raw' => $image['data'],
                'mime' => $image['mime'],
                'title' => 'ID3');
        }

        if (isset($id3['id3v2']['APIC'])) {
            // Foreach in case they have more then one
            foreach ($id3['id3v2']['APIC'] as $image) {
                $data[] = array(
                    $mtype => $media->file,
                    'raw' => $image['data'],
                    'mime' => $image['mime'],
                    'title' => 'ID3');
            }
        }

        if (isset($id3['comments']['picture']['0'])) {
            $image = $id3['comments']['picture']['0'];
            $data[] = array(
            $mtype => $media->file,
            'raw' => $image['data'],
            'mime' => $image['image_mime'],
            'title' => 'ID3');
            return $data;
        }

        return $data;

    }

    /**
     * gather_google
     * Raw google search to retrieve the art, not very reliable
     *
     * @param int $limit
     * @param array $data
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function gather_google($limit = 5, $data = array())
    {
        if (!$limit) {
            $limit = 5;
        }

        $images = array();
        $search = rawurlencode($data['keyword']);
        $size = '&imgsz=m'; // Medium

        $url = "http://images.google.com/images?source=hp&q=" . $search . "&oq=&um=1&ie=UTF-8&sa=N&tab=wi&start=0&tbo=1" . $size;
        debug_event('Art', 'Search url: ' . $url, '5');

        try {
            // Need this to not be considered as a bot (are we? ^^)
            $headers = array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',
            );
            $query = Requests::get($url, $headers);
            $html = $query->body;

            if (preg_match_all("|imgres\?imgurl\=(http.+?)&|", $html, $matches, PREG_PATTERN_ORDER)) {
                foreach ($matches[1] as $match) {
                    $match = rawurldecode($match);
                    debug_event('Art', 'Found image at: ' . $match, '5');
                    $results = pathinfo($match);
                    $mime = 'image/' . $results['extension'];

                    $images[] = array('url' => $match, 'mime' => $mime, 'title' => 'Google');
                    if ($limit > 0 && count($images) >= $limit)
                        break;
                }
            }
        } catch (Exception $e) {
            debug_event('Art', 'Error getting google images: ' . $e->getMessage(), '1');
        }

        return $images;

    } // gather_google

    /**
     * gather_lastfm
     * This returns the art from lastfm. It doesn't currently require an
     * account but may in the future.
     * @param int $limit
     * @param array $data
     * @return array
     */
    public function gather_lastfm($limit = 5, $data = array())
    {
        if (!$limit) {
            $limit = 5;
        }

        $images = array();

        if ($this->type != 'album' || empty($data['artist']) || empty($data['album'])) {
            return $images;
        }

        try {
            $xmldata = Recommendation::album_search($data['artist'], $data['album']);

            if (!count($xmldata)) { return array(); }

            $coverart = (array) $xmldata->coverart;
            if (!$coverart) { return array(); }

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
                $images[] = array('url' => $url, 'mime' => $mime, 'title' => 'LastFM');
                if ($limit && count($images) >= $limit) {
                    return $images;
                }
            } // end foreach
        } catch (Exception $e) {
            debug_event('art', 'LastFM error: ' . $e->getMessage(), 5);
        }

        return $images;

    } // gather_lastfm

    /**
     * Gather metadata from plugin.
     * @param string $type
     * @param array $options
     * @return array
     */
    public static function gather_metadata_plugin($plugin, $type, $options)
    {
        $gtypes = array();
        $media_info = array();
        switch ($type) {
            case 'tvshow':
            case 'tvshow_season':
            case 'tvshow_episode':
                $gtypes[] = 'tvshow';
                $media_info['tvshow'] = $options['tvshow'];
                $media_info['tvshow_season'] = $options['tvshow_season'];
                $media_info['tvshow_episode'] = $options['tvshow_episode'];
            break;
            case 'song':
                $media_info['mb_trackid'] = $options['mb_trackid'];
                $media_info['title'] = $options['title'];
                $media_info['artist'] = $options['artist'];
                $media_info['album'] = $options['album'];
                $gtypes[] = 'song';
                break;
            case 'album':
                $media_info['mb_albumid'] = $options['mb_albumid'];
                $media_info['mb_albumid_group'] = $options['mb_albumid_group'];
                $media_info['artist'] = $options['artist'];
                $media_info['title'] = $options['album'];
                $gtypes[] = 'music';
                $gtypes[] = 'album';
                break;
            case 'artist':
                $media_info['mb_artistid'] = $options['mb_artistid'];
                $media_info['title'] = $options['artist'];
                $gtypes[] = 'music';
                $gtypes[] = 'artist';
                break;
            case 'movie':
                $gtypes[] = 'movie';
                $media_info['title'] = $options['keyword'];
            break;
        }

        $meta = $plugin->get_metadata($gtypes, $media_info);
        $images = array();

        if ($meta['art']) {
            $url = $meta['art'];
            $ures = pathinfo($url);
            $images[] = array('url' => $url, 'mime' => 'image/' . $ures['extension'], 'title' => $plugin->name);
        }
        if ($meta['tvshow_season_art']) {
            $url = $meta['tvshow_season_art'];
            $ures = pathinfo($url);
            $images[] = array('url' => $url, 'mime' => 'image/' . $ures['extension'], 'title' => $plugin->name);
        }
        if ($meta['tvshow_art']) {
            $url = $meta['tvshow_art'];
            $ures = pathinfo($url);
            $images[] = array('url' => $url, 'mime' => 'image/' . $ures['extension'], 'title' => $plugin->name);
        }

        return $images;
    }

    /**
     * Get thumb size from thumb type.
     * @param int $thumb
     * @return array
     */
    public static function get_thumb_size($thumb)
    {
        $size = array();

        switch ($thumb) {
            case 1:
                /* This is used by the now_playing / browse stuff */
                $size['height'] = '100';
                $size['width']    = '100';
            break;
            case 2:
                $size['height']    = '128';
                $size['width']    = '128';
            break;
            case 3:
                /* This is used by the flash player */
                $size['height']    = '80';
                $size['width']    = '80';
            break;
            case 4:
                /* Web Player size */
                $size['height'] = 200;
                $size['width'] = 200; // 200px width, set via CSS
            break;
            case 5:
                /* Web Player size */
                $size['height'] = 32;
                $size['width'] = 32;
            break;
            case 6:
                /* Video browsing size */
                $size['height'] = 150;
                $size['width'] = 100;
            break;
            case 7:
                /* Video page size */
                $size['height'] = 300;
                $size['width'] = 200;
            break;
            case 8:
                /* Video preview size */
                 $size['height'] = 200;
                 $size['width'] = 470;
            break;
            case 9:
                /* Video preview size */
                 $size['height'] = 100;
                 $size['width'] = 235;
            break;
            default:
                $size['height'] = '275';
                $size['width']    = '275';
            break;
        }

        return $size;
    }

    /**
     * Display an item art.
     * @param library_item $item
     * @param int $thumb
     * @param string $link
     * @return boolean
     */
    public static function display_item($item, $thumb, $link = null)
    {
        return self::display($item->type, $item->id, $item->get_fullname(), $thumb, $link);
    }

    /**
     * Display an item art.
     * @param string $object_type
     * @param int $object_id
     * @param string $name
     * @param int $thumb
     * @param string $link
     * @param boolean $show_default
     * @param string $kind
     * @return boolean
     */
    public static function display($object_type, $object_id, $name, $thumb, $link = null, $show_default = true, $kind = 'default')
    {
        if (!$show_default) {
            // Don't show any image if not available
            if (!self::has_db($object_id, $object_type, $kind)) {
                return false;
            }
        }
        $size = self::get_thumb_size($thumb);
        $prettyPhoto = ($link == null);
        if ($link == null) {
            $link = AmpConfig::get('web_path') . "/image.php?object_id=" . $object_id . "&object_type=" . $object_type . "&auth=" . session_id();
            if ($kind != 'default') {
                $link .= '&kind=' . $kind;
            }
        }
        echo "<div class=\"item_art\">";
        echo "<a href=\"" . $link . "\" title=\"" . $name . "\"";
        if ($prettyPhoto) {
            echo " rel=\"prettyPhoto\"";
        }
        echo ">";
        $imgurl = AmpConfig::get('web_path') . "/image.php?object_id=" . $object_id . "&object_type=" . $object_type . "&thumb=" . $thumb;
        if ($kind != 'default') {
            $imgurl .= '&kind=' . $kind;
        }
        echo "<img src=\"" . $imgurl . "\" alt=\"" . $name . "\" height=\"" . $size['height'] . "\" width=\"" . $size['width'] . "\" />";
        if ($prettyPhoto) {
            if ($size['width'] >= 150) {
                echo "<div class=\"item_art_play\">";
                echo Ajax::text('?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $object_id . '\' + getPagePlaySettings() + \'', '<span class="item_art_play_icon" title="' . T_('Play') . '" />', 'directplay_art_' . $object_type . '_' .$object_id);
                echo "</div>";
            }
            echo "<div class=\"item_art_actions\">";
            $burl = substr($_SERVER['REQUEST_URI'], strlen(AmpConfig::get('raw_web_path')));
            $burl = rawurlencode(ltrim($burl, '/'));
            if ($GLOBALS['user']->has_access('25')) {
                echo "<a href=\"" . AmpConfig::get('web_path') . "/arts.php?action=find_art&object_type=" . $object_type . "&object_id=" . $object_id . "&burl=" . $burl . "\">";
                echo UI::get_icon('edit', T_('Edit/Find Art'));
                echo "</a>";
            }
            if ($GLOBALS['user']->has_access('75')) {
                echo "<a href=\"" . AmpConfig::get('web_path') . "/arts.php?action=clear_art&object_type=" . $object_type . "&object_id=" . $object_id . "&burl=" . $burl . "\" onclick=\"return confirm('" . T_('Do you really want to reset art?') . "');\">";
                echo UI::get_icon('delete', T_('Reset Art'));
                echo "</a>";
            }
            echo"</div>";
        }
        echo "</a>\n";
        echo "</div>";

        return true;
    }

} // Art
