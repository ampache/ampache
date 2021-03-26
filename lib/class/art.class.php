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

use MusicBrainz\MusicBrainz;
use MusicBrainz\HttpAdapters\RequestsHttpAdapter;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\Session as SpotifySession;
use SpotifyWebAPI\SpotifyWebAPIException;

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
     *  @var integer $id
     */
    public $id;
    /**
     *  @var string $type
     */
    public $type;
    /**
     *  @var integer $uid
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
     * @const ART_SEARCH_LIMIT
     */
    public const ART_SEARCH_LIMIT = 5;

    /**
     * Constructor
     * Art constructor, takes the UID of the object and the
     * object type.
     * @param integer $uid
     * @param string $type
     * @param string $kind
     */
    public function __construct($uid, $type = 'album', $kind = 'default')
    {
        if (Art::is_valid_type($type)) {
            $this->type = $type;
            $this->uid  = (int) ($uid);
            $this->kind = $kind;
        }
    } // constructor

    /**
     * @param string $type
     * @return boolean
     */
    public static function is_valid_type($type)
    {
        return (Core::is_library_item($type) || $type == 'user');
    }

    /**
     * build_cache
     * This attempts to reduce # of queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point
     * @param integer[] $object_ids
     * @param string $type
     * @return boolean
     */
    public static function build_cache($object_ids, $type = null)
    {
        if (empty($object_ids)) {
            return false;
        }
        $idlist = '(' . implode(',', $object_ids) . ')';
        $sql    = "SELECT `object_type`, `object_id`, `mime`, `size` FROM `image` WHERE `object_id` IN $idlist";
        if ($type !== null) {
            $sql .= " AND `object_type` = '$type'";
        }
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('art', $row['object_type'] . $row['object_id'] . $row['size'], $row);
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
            $_SESSION['art_enabled'] = true;
        }

        self::$enabled = $_SESSION['art_enabled'];
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
     * @param boolean|null $value
     */
    public static function set_enabled($value = null)
    {
        if ($value === null) {
            self::$enabled = self::$enabled ? false : true;
        } else {
            self::$enabled = $value;
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
        $data      = explode("/", (string) $mime);
        $extension = $data['1'];

        if ($extension == 'jpeg') {
            $extension = 'jpg';
        }

        return (string) $extension;
    } // extension

    /**
     * test_image
     * Runs some sanity checks on the putative image
     * @param string $source
     * @return boolean
     * @throws RuntimeException
     */
    public static function test_image($source)
    {
        if (strlen((string) $source) < 10) {
            debug_event(self::class, 'Invalid image passed', 1);

            return false;
        }

        // Check image size doesn't exceed the limit
        if (strlen((string) $source) > AmpConfig::get('max_upload_size')) {
            debug_event(self::class, 'Image size (' . strlen((string) $source) . ') exceed the limit (' . AmpConfig::get('max_upload_size') . ').', 1);

            return false;
        }

        $test  = false;
        $image = false;
        // Check to make sure PHP:GD exists.  If so, we can sanity check the image.
        if (function_exists('ImageCreateFromString') && is_string($source)) {
            $test  = true;
            $image = ImageCreateFromString($source);
            if ($image == false || imagesx($image) < 5 || imagesy($image) < 5) {
                debug_event(self::class, 'Image failed PHP-GD test', 1);
                $test = false;
            }
        }
        if ($test && $image != false) {
            if (imagedestroy($image) === false) {
                throw new RuntimeException('The image handle from source: ' . $source . ' could not be destroyed');
            }
        }

        return $test;
    } // test_image

    /**
     * get
     * This returns the art for our current object, this can
     * look in the database and will return the thumb if it
     * exists, if it doesn't depending on settings it will try
     * to create it.
     * @param boolean $raw
     * @return string
     */
    public function get($raw = false)
    {
        // Get the data either way
        if (!$this->has_db_info()) {
            return '';
        }

        if ($raw || !$this->thumb) {
            return $this->raw;
        } else {
            return $this->thumb;
        }
    } // get


    /**
     * has_db_info
     * This pulls the information out from the database, depending
     * on if we want to resize and if there is not a thumbnail go
     * ahead and try to resize
     * @return boolean
     */
    public function has_db_info()
    {
        $sql        = "SELECT `id`, `image`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `kind` = ?";
        $db_results = Dba::read($sql, array($this->type, $this->uid, $this->kind));

        while ($results = Dba::fetch_assoc($db_results)) {
            if ($results['size'] == 'original') {
                if (AmpConfig::get('album_art_store_disk')) {
                    $this->raw = self::read_from_dir($results['size'], $this->type, $this->uid, $this->kind);
                } else {
                    $this->raw = $results['image'];
                }
                $this->raw_mime = $results['mime'];
            } else {
                if (AmpConfig::get('resize_images') && $results['size'] == '275x275') {
                    if (AmpConfig::get('album_art_store_disk')) {
                        $this->thumb = self::read_from_dir($results['size'], $this->type, $this->uid, $this->kind);
                    } else {
                        $this->thumb = $results['image'];
                    }
                    $this->raw_mime = $results['mime'];
                }
            }
            $this->id = (int) $results['id'];
        }
        // If we get nothing return false
        if (!$this->raw) {
            return false;
        }

        // If there is no thumb and we want thumbs
        if (!$this->thumb && AmpConfig::get('resize_images')) {
            $size = array('width' => 275, 'height' => 275);
            $data = $this->generate_thumb($this->raw, $size, $this->raw_mime);
            // If it works save it!
            if (!empty($data)) {
                $this->save_thumb($data['thumb'], $data['thumb_mime'], $size);
                $this->thumb      = $data['thumb'];
                $this->thumb_mime = $data['thumb_mime'];
            } else {
                debug_event(self::class, 'Unable to retrieve or generate thumbnail for ' . $this->type . '::' . $this->id, 1);
            }
        } // if no thumb, but art and we want to resize

        return true;
    } // has_db_info

    /**
     * This check if an object has an associated image in db.
     * @param integer $object_id
     * @param string $object_type
     * @param string $kind
     * @return boolean
     */
    public static function has_db($object_id, $object_type, $kind = 'default')
    {
        $sql        = "SELECT COUNT(`id`) AS `nb_img` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `kind` = ?";
        $db_results = Dba::read($sql, array($object_type, $object_id, $kind));
        $nb_img     = 0;
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
        debug_event(self::class, 'Insert art from url ' . $url, 4);
        $image = self::get_from_source(array('url' => $url), $this->type);
        $rurl  = pathinfo($url);
        $mime  = "image/" . $rurl['extension'];
        $this->insert($image, $mime);
    }

    /**
     * This insert art from file on disk.
     * @param string $filepath
     */
    public function insert_from_file($filepath)
    {
        debug_event(self::class, 'Insert art from file on disk ' . $filepath, 4);
        $image = self::get_from_source(array('file' => $filepath), $this->type);
        $rfile = pathinfo($filepath);
        $mime  = "image/" . $rfile['extension'];
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
        if (AmpConfig::get('demo_mode')) {
            return false;
        }

        // Check to make sure we like this image
        if (!self::test_image($source)) {
            debug_event(self::class, 'Not inserting image for ' . $this->type . ' ' . $this->uid . ', invalid data passed', 1);

            return false;
        }

        $dimensions = Core::image_dimensions($source);
        $width      = (int) ($dimensions['width']);
        $height     = (int) ($dimensions['height']);
        $sizetext   = 'original';

        if (!self::check_dimensions($dimensions)) {
            return false;
        }

        // Default to image/jpeg if they don't pass anything
        $mime = $mime ? $mime : 'image/jpeg';
        // Blow it away!
        $this->reset();

        $current_picturetypeid = ($this->type == 'album') ? 3 : 8;
        if (AmpConfig::get('write_id3_art', false)) {
            $type   = ucfirst($this->type);
            $object = new $type($this->uid);
            debug_event(self::class, 'Inserting ' . $type . ' image' . $object->name . ' for song files.', 5);
            $songs = $object->get_songs();
            foreach ($songs as $song_id) {
                $song   = new Song($song_id);
                $song->format();
                $description = ($this->type == 'artist') ? $song->f_artist_full : $object->full_name;
                $id3         = new vainfo($song->file);
                $ndata       = array();
                $data        = $id3->read_id3();
                if (isset($data['id3v2']['APIC'])) {
                    $apics = $data['id3v2']['APIC'];
                    switch (count($apics)) {
                         case 1:
                            $ndata['attached_picture'][0]['data']          = $apics[0]['data'];
                            $ndata['attached_picture'][0]['mime']          = $apics[0]['mime'];
                            $ndata['attached_picture'][0]['picturetypeid'] = $apics[0]['picturetypeid'];
                            $ndata['attached_picture'][0]['description']   = $apics[0]['description'];
                            if ($apics[0]['picturetypeid'] != $current_picturetypeid) {
                                $ndata['attached_picture'][1]['data']          = $source;
                                $ndata['attached_picture'][1]['mime']          = $mime;
                                $ndata['attached_picture'][1]['picturetypeid'] =  $current_picturetypeid;
                                $ndata['attached_picture'][1]['description']   = $description;
                            } else {
                                debug_event(self::class, "only 1 image of type '" . $this->type . "' is permitted", 1);
                            }
                            break;
                          case 2:
                            $ndata['attached_picture'] = $this->replace_apic($apics, $mime,$description, $source, $current_picturetypeid);
                            break;
                       }
                } else {
                    $ndata['attached_picture'][0]['description']   = $description;
                    $ndata['attached_picture'][0]['data']          = $source;
                    $ndata['attached_picture'][0]['mime']          = $mime;
                    $ndata['attached_picture'][0]['picturetypeid'] = $current_picturetypeid;
                }
                unset($data['id3v2']['APIC']);
                $ndata   = array_merge($ndata, $id3->prepare_id3_frames($data['tags']['id3v2']));
                $id3->write_id3($ndata);
                //            Catalog::update_media_from_tags($song);
            } // foreach song
        } // write_id3

        if (AmpConfig::get('album_art_store_disk')) {
            self::write_to_dir($source, $sizetext, $this->type, $this->uid, $this->kind);
            $source = null;
        }

        // Insert it!
        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `width`, `height`, `object_type`, `object_id`, `kind`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($source, $mime, $sizetext, $width, $height, $this->type, $this->uid, $this->kind));

        return true;
    } // insert

    /**
     * replace_apic
     * @param array $apics
     * @param string $mime
     * @param string $description
     * @param string $source
     * @param integer $picturetypeid
     * @return array
     */
    private function replace_apic($apics, $mime, $description, $source, $picturetypeid)
    {
        $ndata = array();
        if ($apics[0]['picturetypeid'] == $picturetypeid) {
            $ndata[0]['description']   = $description;
            $ndata[0]['data']          = $source;
            $ndata[0]['mime']          = $mime;
            $ndata[0]['picturetypeid'] = $picturetypeid;

            $ndata[1]['description']   = $apics[1]['description'];
            $ndata[1]['data']          = $apics[1]['data'];
            $ndata[1]['mime']          = $apics[1]['mime'];
            $ndata[1]['picturetypeid'] = $apics[1]['picturetypeid'];
        } else {
            $ndata[0]['description']   = $apics[0]['description'];
            $ndata[0]['data']          = $apics[0]['data'];
            $ndata[0]['mime']          = $apics[0]['mime'];
            $ndata[0]['picturetypeid'] = $apics[0]['picturetypeid'];

            $ndata[1]['description']   = $description;
            $ndata[1]['data']          = $source;
            $ndata[1]['mime']          = $mime;
            $ndata[1]['picturetypeid'] = $picturetypeid;
        }

        return $ndata;
    }

    /**
     * Prepares images to be written to file tag.
     * @param array $pics
     * @return array
     */
    public static function prepare_pics($pics)
    {
        $ndata = array();
        $i     = 0;
        foreach ($pics as $pic) {
            $ndata['attached_picture'][$i]['description']   = $pic['description'];
            $ndata['attached_picture'][$i]['data']          = $pic['data'];
            $ndata['attached_picture'][$i]['picturetypeid'] = $pic['picturetypeid'];
            $ndata['attached_picture'][$i]['mime']          = $pic['mime'];

            $i++;
        }

        return $ndata;
    }

    /**
     * check_dimensions
     * @param array $dimensions
     * @return boolean
     */
    public static function check_dimensions($dimensions)
    {
        $width  = (int) ($dimensions['width']);
        $height = (int) ($dimensions['height']);

        if ($width > 0 && $height > 0) {
            $minw = (AmpConfig::get('album_art_min_width')) ? AmpConfig::get('album_art_min_width') : 0;
            $maxw = (AmpConfig::get('album_art_max_width')) ? AmpConfig::get('album_art_max_width') : 0;
            $minh = (AmpConfig::get('album_art_min_height')) ? AmpConfig::get('album_art_min_height') : 0;
            $maxh = (AmpConfig::get('album_art_max_height')) ? AmpConfig::get('album_art_max_height') : 0;

            // minimum width is set and current width is too low
            if ($minw > 0 && $width < $minw) {
                debug_event(self::class, "Image width not in range (min=$minw, max=$maxw, current=$width).", 1);

                return false;
            }
            // max width is set and current width is too high
            if ($maxw > 0 && $width > $maxw) {
                debug_event(self::class, "Image width not in range (min=$minw, max=$maxw, current=$width).", 1);

                return false;
            }
            if ($minh > 0 && $height < $minh) {
                debug_event(self::class, "Image height not in range (min=$minh, max=$maxh, current=$height).", 1);

                return false;
            }
            if ($maxh > 0 && $height > $maxh) {
                debug_event(self::class, "Image height not in range (min=$minh, max=$maxh, current=$height).", 1);

                return false;
            }
        }

        return true;
    }
    /**
     * clean_art_by_dimension
     *
     * look for art in the image table that doesn't fit min or max dimensions and delete it
     * @return boolean
     */
    public static function clean_art_by_dimension()
    {
        $minw = (AmpConfig::get('album_art_min_width')) ? AmpConfig::get('album_art_min_width') : null;
        $maxw = (AmpConfig::get('album_art_max_width')) ? AmpConfig::get('album_art_max_width') : null;
        $minh = (AmpConfig::get('album_art_min_height')) ? AmpConfig::get('album_art_min_height') : null;
        $maxh = (AmpConfig::get('album_art_max_height')) ? AmpConfig::get('album_art_max_height') : null;

        // minimum width is set and current width is too low
        if ($minw) {
            $sql = 'DELETE FROM `image` WHERE `width` < ? AND `width` > 0';
            Dba::write($sql, array($minw));
        }
        // max width is set and current width is too high
        if ($maxw) {
            $sql = 'DELETE FROM `image` WHERE `width` > ? AND `width` > 0';
            Dba::write($sql, array($maxw));
        }
        // min height is set and current width is too low
        if ($minh) {
            $sql = 'DELETE FROM `image` WHERE `height` < ? AND `height` > 0';
            Dba::write($sql, array($minh));
        }
        // max height is set and current height is too high
        if ($maxh) {
            $sql = 'DELETE FROM `image` WHERE `height` > ? AND `height` > 0';
            Dba::write($sql, array($maxh));
        }

        return true;
    } // clean_art_by_dimension

    /**
     * get_dir_on_disk
     * @param string $type
     * @param string $uid
     * @param string $kind
     * @param boolean $autocreate
     * @return false|string
     */
    public static function get_dir_on_disk($type, $uid, $kind = '', $autocreate = false)
    {
        $path = AmpConfig::get('local_metadata_dir');
        if (!$path) {
            debug_event(self::class, 'local_metadata_dir setting is required to store art on disk.', 1);

            return false;
        }

        // Correctly detect the slash we need to use here
        if (strpos($path, '/') !== false) {
            $slash_type = '/';
        } else {
            $slash_type = '\\';
        }

        $path .= $slash_type . $type;
        if ($autocreate && !Core::is_readable($path)) {
            mkdir($path);
        }

        $path .= $slash_type . $uid;
        if ($autocreate && !Core::is_readable($path)) {
            mkdir($path);
        }

        if (!empty($kind)) {
            $path .= $slash_type . $kind;
            if ($autocreate && !Core::is_readable($path)) {
                mkdir($path);
            }
        }
        $path .= $slash_type;

        return $path;
    }

    /**
     * write_to_dir
     * @param string $source
     * @param $sizetext
     * @param string $type
     * @param integer $uid
     * @param $kind
     * @return boolean
     */
    private static function write_to_dir($source, $sizetext, $type, $uid, $kind)
    {
        $path = self::get_dir_on_disk($type, $uid, $kind, true);
        if ($path === false) {
            return false;
        }
        $path .= "art-" . $sizetext . ".jpg";
        if (Core::is_readable($path)) {
            unlink($path);
        }
        $filepath = fopen($path, "wb");
        fwrite($filepath, $source);
        fclose($filepath);

        return true;
    }

    /**
     * read_from_dir
     * @param $sizetext
     * @param string $type
     * @param integer $uid
     * @param $kind
     * @return string|null
     */
    private static function read_from_dir($sizetext, $type, $uid, $kind)
    {
        $path = self::get_dir_on_disk($type, $uid, $kind);
        if ($path === false) {
            return null;
        }
        $path .= "art-" . $sizetext . ".jpg";
        if (!Core::is_readable($path)) {
            debug_event(self::class, 'Local image art ' . $path . ' cannot be read.', 1);

            return null;
        }

        $image    = '';
        $filepath = fopen($path, "rb");
        do {
            $image .= fread($filepath, 2048);
        } while (!feof($filepath));
        fclose($filepath);

        return $image;
    }

    /**
     * delete_from_dir
     * @param string $type
     * @param string $uid
     * @param string $kind
     */
    private static function delete_from_dir($type, $uid, $kind = '')
    {
        if ($type && $uid) {
            $path = self::get_dir_on_disk($type, $uid, $kind);
            if ($path !== false) {
                self::delete_rec_dir($path);
            }
        }
    }

    /**
     * delete_rec_dir
     * @param string $path
     */
    private static function delete_rec_dir($path)
    {
        debug_event(self::class, 'Deleting ' . (string) $path . ' directory...', 5);

        if (Core::is_readable($path)) {
            foreach (scandir($path) as $file) {
                if ('.' === $file || '..' === $file) {
                    continue;
                } elseif (is_dir($path . '/' . $file)) {
                    self::delete_rec_dir($path . '/' . $file);
                } else {
                    unlink($path . '/' . $file);
                }
            }
            rmdir($path);
        }
    }

    /**
     * reset
     * This resets the art in the database
     */
    public function reset()
    {
        if (AmpConfig::get('album_art_store_disk')) {
            self::delete_from_dir($this->type, $this->uid, $this->kind);
        }
        $sql = "DELETE FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `kind` = ?";
        Dba::write($sql, array($this->uid, $this->type, $this->kind));
    } // reset

    /**
     * save_thumb
     * This saves the thumbnail that we're passed
     * @param string $source
     * @param string $mime
     * @param array $size
     * @return boolean
     */
    public function save_thumb($source, $mime, $size)
    {
        // Quick sanity check
        if (!self::test_image($source)) {
            debug_event(self::class, 'Not inserting thumbnail, invalid data passed', 1);

            return false;
        }

        $width    = $size['width'];
        $height   = $size['height'];
        $sizetext = $width . 'x' . $height;

        $sql = "DELETE FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `size` = ? AND `kind` = ?";
        Dba::write($sql, array($this->uid, $this->type, $sizetext, $this->kind));

        if (AmpConfig::get('album_art_store_disk')) {
            self::write_to_dir($source, $sizetext, $this->type, $this->uid, $this->kind);
            $source = null;
        }
        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `width`, `height`, `object_type`, `object_id`, `kind`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($source, $mime, $sizetext, $width, $height, $this->type, $this->uid, $this->kind));

        return true;
    } // save_thumb

    /**
     * get_thumb
     * Returns the specified resized image.  If the requested size doesn't
     * already exist, create and cache it.
     * @param array $size
     * @return array
     */
    public function get_thumb($size)
    {
        $sizetext   = $size['width'] . 'x' . $size['height'];
        $sql        = "SELECT `image`, `mime` FROM `image` WHERE `size` = ? AND `object_type` = ? AND `object_id` = ? AND `kind` = ?";
        $db_results = Dba::read($sql, array($sizetext, $this->type, $this->uid, $this->kind));

        $results = Dba::fetch_assoc($db_results);
        if (count($results)) {
            if (AmpConfig::get('album_art_store_disk')) {
                $image = self::read_from_dir($sizetext, $this->type, $this->uid, $this->kind);
            } else {
                $image = $results['image'];
            }

            if ($image != null) {
                return array(
                    'thumb' => (AmpConfig::get('album_art_store_disk')) ? self::read_from_dir($sizetext, $this->type, $this->uid, $this->kind) : $results['image'],
                    'thumb_mime' => $results['mime']);
            } else {
                debug_event(self::class, 'Thumb entry found in database but associated data cannot be found.', 3);
            }
        }

        // If we didn't get a result
        $results = $this->generate_thumb($this->raw, $size, $this->raw_mime);
        if (!empty($results)) {
            $this->save_thumb($results['thumb'], $results['thumb_mime'], $size);
        }

        return $results;
    } // get_thumb

    /**
     * generate_thumb
     * Automatically resizes the image for thumbnail viewing.
     * Only works on gif/jpg/png/bmp. Fails if PHP-GD isn't available
     * or lacks support for the requested image type.
     * @param string $image
     * @param array $size
     * @param string $mime
     * @return array
     */
    public function generate_thumb($image, $size, $mime)
    {
        $data = explode("/", (string) $mime);
        $type = ((string) $data['1'] !== '') ? strtolower((string) $data['1']) : 'jpg';

        if (!self::test_image($image)) {
            debug_event(self::class, 'Not trying to generate thumbnail, invalid data passed', 1);

            return array();
        }

        if (!function_exists('gd_info')) {
            debug_event(self::class, 'PHP-GD Not found - unable to resize art', 1);

            return array();
        }

        // Check and make sure we can resize what you've asked us to
        if (($type == 'jpg' || $type == 'jpeg' || $type == 'jpg?v=2') && !(imagetypes() & IMG_JPG)) {
            debug_event(self::class, 'PHP-GD Does not support JPGs - unable to resize', 1);

            return array();
        }
        if ($type == 'png' && !imagetypes() & IMG_PNG) {
            debug_event(self::class, 'PHP-GD Does not support PNGs - unable to resize', 1);

            return array();
        }
        if ($type == 'gif' && !imagetypes() & IMG_GIF) {
            debug_event(self::class, 'PHP-GD Does not support GIFs - unable to resize', 1);

            return array();
        }
        if ($type == 'bmp' && !imagetypes() & IMG_WBMP) {
            debug_event(self::class, 'PHP-GD Does not support BMPs - unable to resize', 1);

            return array();
        }

        $source = imagecreatefromstring($image);

        if (!$source) {
            debug_event(self::class, 'Failed to create Image from string - Source Image is damaged / malformed', 2);

            return array();
        }

        $source_size = array('height' => imagesy($source), 'width' => imagesx($source));

        // Create a new blank image of the correct size
        $thumbnail = imagecreatetruecolor((int) $size['width'], (int) $size['height']);

        if (!imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $size['width'], $size['height'], $source_size['width'], $source_size['height'])) {
            debug_event(self::class, 'Unable to create resized image', 1);
            imagedestroy($source);
            imagedestroy($thumbnail);

            return array();
        }
        imagedestroy($source);

        // Start output buffer
        ob_start();

        // Generate the image to our OB
        switch ($type) {
            case 'jpg':
            case 'jpeg':
            case 'jpg?v=2':
            case '(null)':
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
            default:
                $mime_type = null;
        } // resized

        if ($mime_type === null) {
            debug_event(self::class, 'Error: No mime type found using: ' . $mime, 2);

            return array();
        }

        $data = ob_get_contents();
        ob_end_clean();

        imagedestroy($thumbnail);
        if (!strlen((string) $data)) {
            debug_event(self::class, 'Unknown Error resizing art', 1);

            return array();
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
    public static function get_from_source($data, $type)
    {
        if (!isset($type)) {
            $type = (AmpConfig::get('show_song_art')) ? 'song' : 'album';
        }

        // Already have the data, this often comes from id3tags
        if (isset($data['raw'])) {
            return $data['raw'];
        }

        // If it came from the database
        if (isset($data['db'])) {
            $sql        = "SELECT * FROM `image` WHERE `object_type` = ? AND `object_id` =? AND `size`='original'";
            $db_results = Dba::read($sql, array($type, $data['db']));
            $row        = Dba::fetch_assoc($db_results);

            return $row['art'];
        } // came from the db

        // Check to see if it's a URL
        if (filter_var($data['url'], FILTER_VALIDATE_URL)) {
            debug_event(self::class, 'CHECKING URL ' . $data['url'], 2);
            $options = array();
            try {
                $options['timeout'] = 10;
                Requests::register_autoloader();
                $request            = Requests::get($data['url'], array(), Core::requests_options($options));
                $raw                = $request->body;
            } catch (Exception $error) {
                debug_event(self::class, 'Error getting art: ' . $error->getMessage(), 2);
                $raw = '';
            }

            return $raw;
        }

        // Check to see if it's a FILE
        if (isset($data['file'])) {
            $handle     = fopen($data['file'], 'rb');
            $image_data = (string) fread($handle, Core::get_filesize($data['file']));
            fclose($handle);

            return $image_data;
        }

        // Check to see if it is embedded in id3 of a song
        if (isset($data['song'])) {
            // If we find a good one, stop looking
            $getID3 = new getID3();
            try {
                $id3 = $getID3->analyze($data['song']);
            } catch (Exception $error) {
                debug_event(self::class, 'getid3' . $error->getMessage(), 1);

                return '';
            }

            if ($id3['format_name'] == "WMA") {
                return $id3['asf']['extended_content_description_object']['content_descriptors']['13']['data'];
            } elseif (isset($id3['id3v2']['APIC'])) {
                // Foreach in case they have more then one
                foreach ($id3['id3v2']['APIC'] as $image) {
                    return $image['data'];
                }
            }
        } // if data song

        return '';
    } // get_from_source

    /**
     * url
     * This returns the constructed URL for the art in question
     * @param integer $uid
     * @param string $type
     * @param string $sid
     * @param integer|null $thumb
     * @return string
     */
    public static function url($uid, $type, $sid = null, $thumb = null)
    {
        if (!self::is_valid_type($type)) {
            return null;
        }

        if (AmpConfig::get('use_auth') && AmpConfig::get('require_session')) {
            $sid = $sid ? scrub_out($sid) : scrub_out(session_id());
            if ($sid == null) {
                $sid = Session::create(array(
                    'type' => 'api'
                ));
            }
        }

        $key = $type . $uid;

        if (parent::is_cached('art', $key . '275x275') && AmpConfig::get('resize_images')) {
            $row  = parent::get_from_cache('art', $key . '275x275');
            $mime = $row['mime'];
        }
        if (parent::is_cached('art', $key . 'original')) {
            $row        = parent::get_from_cache('art', $key . 'original');
            $thumb_mime = $row['mime'];
        }
        if (!isset($mime) && !isset($thumb_mime)) {
            $sql        = "SELECT `object_type`, `object_id`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ?";
            $db_results = Dba::read($sql, array($type, $uid));

            while ($row = Dba::fetch_assoc($db_results)) {
                parent::add_to_cache('art', $key . $row['size'], $row);
                if ($row['size'] == 'original') {
                    $mime = $row['mime'];
                } else {
                    if ($row['size'] == '275x275' && AmpConfig::get('resize_images')) {
                        $thumb_mime = $row['mime'];
                    }
                }
            }
        }

        $mime      = isset($thumb_mime) ? $thumb_mime : (isset($mime) ? $mime : null);
        $extension = self::extension($mime);

        if (AmpConfig::get('stream_beautiful_url')) {
            if (empty($extension)) {
                $extension = 'jpg';
            }
            $url = AmpConfig::get('web_path') . '/play/art/' . $sid . '/' . scrub_out($type) . '/' . scrub_out($uid) . '/thumb';
            if ($thumb !== null) {
                $url .= $thumb;
            }
            $url .= '.' . $extension;
        } else {
            $url = AmpConfig::get('web_path') . '/image.php?object_id=' . scrub_out($uid) . '&object_type=' . scrub_out($type) . '&auth=' . $sid;
            if ($thumb !== null) {
                $url .= '&thumb=' . $thumb;
            }
            if (!empty($extension)) {
                $name = 'art.' . $extension;
                $url .= '&name=' . $name;
            }
        }

        return $url;
    } // url

    /**
     * garbage_collection
     * This cleans up art that no longer has a corresponding object
     * @param string $object_type
     * @param integer $object_id
     */
    public static function garbage_collection($object_type = null, $object_id = null)
    {
        $types = array('album', 'artist', 'tvshow', 'tvshow_season', 'video', 'user', 'live_stream');

        if ($object_type !== null) {
            if (in_array($object_type, $types)) {
                if (AmpConfig::get('album_art_store_disk')) {
                    self::delete_from_dir($object_type, $object_id);
                }
                $sql = "DELETE FROM `image` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event(self::class, 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            // iterate over our types and delete the images
            foreach ($types as $type) {
                if (AmpConfig::get('album_art_store_disk')) {
                    $sql = "SELECT `image`.`object_id`, `image`.`object_type` FROM `image` LEFT JOIN `" .
                        $type . "` ON `" . $type . "`.`id`=" .
                        "`image`.`object_id` WHERE `object_type`='" .
                        $type . "' AND `" . $type . "`.`id` IS NULL";
                    $db_results = Dba::read($sql);
                    while ($row = Dba::fetch_row($db_results)) {
                        self::delete_from_dir($row[1], $row[0]);
                    }
                }
                $sql = "DELETE FROM `image` USING `image` LEFT JOIN `" .
                    $type . "` ON `" . $type . "`.`id`=" .
                    "`image`.`object_id` WHERE `object_type`='" .
                    $type . "' AND `" . $type . "`.`id` IS NULL";
                Dba::write($sql);
            } // foreach
        }
    }

    /**
     * Migrate an object associate images to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE `image` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }

    /**
     * Duplicate an object associate images to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function duplicate($object_type, $old_object_id, $new_object_id)
    {
        debug_event(self::class, 'duplicate... type:' . $object_type . ' old_id:' . $old_object_id . ' new_id:' . $new_object_id, 5);
        if (AmpConfig::get('album_art_store_disk')) {
            $sql        = "SELECT `size`, `kind` FROM `image` WHERE `object_type` = ? AND `object_id` = ?";
            $db_results = Dba::read($sql, array($object_type, $old_object_id));
            while ($row = Dba::fetch_assoc($db_results)) {
                $image = self::read_from_dir($row['size'], $object_type, $old_object_id, $row['kind']);
                if ($image !== null) {
                    self::write_to_dir($image, $row['size'], $object_type, $new_object_id, $row['kind']);
                }
            }
        }

        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `object_type`, `object_id`, `kind`) SELECT `image`, `mime`, `size`, `object_type`, ? as `object_id`, `kind` FROM `image` WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }

    /**
     * gather
     * This tries to get the art in question
     * @param array $options
     * @param integer $limit
     * @return array
     */
    public function gather($options = array(), $limit = 0)
    {
        // Define vars
        $results = array();
        $type    = $this->type;
        if (isset($options['type'])) {
            $type = $options['type'];
        }

        if (count($options) == 0) {
            debug_event(self::class, 'No options for art search, skipped.', 3);

            return array();
        }
        if ($limit == 0) {
            $limit   = (is_null(AmpConfig::get('art_search_limit'))) ? static::ART_SEARCH_LIMIT : AmpConfig::get('art_search_limit');
        }
        $config    = AmpConfig::get('art_order');
        $methods   = get_class_methods('Art');

        /* If it's not set */
        if (empty($config)) {
            // They don't want art!
            debug_event(self::class, 'art_order is empty, skipping art gathering', 3);

            return array();
        } elseif (!is_array($config)) {
            $config = array($config);
        }

        debug_event(self::class, 'Searching using:' . json_encode($config), 3);

        $plugin_names = Plugin::get_plugins('gather_arts');
        foreach ($config as $method) {
            $method_name = "gather_" . $method;

            $data = array();
            if (in_array($method, $plugin_names)) {
                $plugin            = new Plugin($method);
                $installed_version = Plugin::get_plugin_version($plugin->_plugin->name);
                if ($installed_version) {
                    if ($plugin->load(Core::get_global('user'))) {
                        $data = $plugin->_plugin->gather_arts($type, $options, $limit);
                    }
                }
            } else {
                if (in_array($method_name, $methods)) {
                    debug_event(self::class, "Method used: $method_name", 4);
                    // Some of these take options!
                    switch ($method_name) {
                        case 'gather_google':
                        case 'gather_musicbrainz':
                        case 'gather_lastfm':
                        case 'gather_spotify':
                            $data = $this->{$method_name}($limit, $options);
                            break;
                        default:
                            $data = $this->{$method_name}($limit);
                            break;
                    }
                } else {
                    debug_event(self::class, $method_name . " not defined", 1);
                }
            }

            // Add the results we got to the current set
            $results = array_merge($results, (array) $data);

            if ($limit && count($results) >= $limit) {
                debug_event(self::class, 'results:' . json_encode($results), 3);

                return array_slice($results, 0, $limit + 1);
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
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function gather_db()
    {
        if ($this->has_db_info()) {
            return array('db' => true);
        }

        return array();
    }

    /**
     * gather_spotify
     * This function gathers art from the spotify catalog
     * @param integer $limit
     * @param array $data
     * @return array
     */
    public function gather_spotify($limit = 5, $data = array())
    {
        static $accessToken = null;
        $images             = array();
        if (!AmpConfig::get('spotify_client_id') || !AmpConfig::get('spotify_client_secret')) {
            debug_event(self::class, 'gather_spotify: Missing Spotify credentials, check your config',5);

            return $images;
        }
        $clientId     = AmpConfig::get('spotify_client_id');
        $clientSecret = AmpConfig::get('spotify_client_secret');
        $session      = null;

        if (!isset($accessToken)) {
            try {
                $session = new SpotifySession($clientId, $clientSecret);
                $session->requestCredentialsToken();
                $accessToken = $session->getAccessToken();
            } catch (SpotifyWebAPIException $error) {
                debug_event(self::class, "gather_spotify: A problem exists with the client credentials", 5);
            }
        }
        $filter  = array();
        $query   = '';
        $query1  = '';
        $getType = '';
        $api     = new SpotifyWebAPI();
        $types   = $this->type . 's';
        $api->setAccessToken($accessToken);
        if ($this->type == 'artist') {
            debug_event(self::class, "gather_spotify artist: " . $data['artist'], 5);
            $query   = $data['keyword'];
            $getType = 'getArtist';
        } elseif ($this->type == 'album') {
            $getType = 'getAlbum';
            debug_event(self::class, "gather_spotify album: " . $data['album'], 5);
            // Check for manual search
            if (key_exists('search_limit', $data)) {
                $limit = $data['search_limit'];
                if (key_exists('artist_filter', $data)) {
                    $filter[]= 'artist';
                }
                if (key_exists('year_filter', $data)) {
                    $filter[] = $data['year_filter'];
                }
            } elseif (!is_null(AmpConfig::get('spotify_art_filter')) || !empty(AmpConfig::get('spotify_art_filter'))) {
                $filter = explode(',', AmpConfig::get('spotify_art_filter'));
            }
            if (!empty($filter)) {
                foreach ($filter as $item) {
                    switch (trim($item)) {
                        case 'artist':
                          $query1 .= " artist:\"{$data['artist']}\"";
                        break;
                        case preg_match('/year:.*/', $item):
                           $query1 .= ' ' . $item;
                        break;
                        default:
                    }
                }
                $query = "album:" . "\"{$data['album']}\"" . $query1;
            } else {
                $query = "\"{$data['album']}\"";
            }
        } else {
            return $images;
        }

        try {
            $response = $api->search($query, $this->type, ['limit' => $limit]);
        } catch (SpotifyWebAPIException $error) {
            if ($error->hasExpiredToken()) {
                $session = new SpotifySession($clientId, $clientSecret);
                $session->requestCredentialsToken();
                $accessToken = $session->getAccessToken();
            } elseif ($error->getCode() == 429) {
                $lastResponse = $api->getRequest()->getLastResponse();
                $retryAfter   = $lastResponse['headers']['Retry-After'];
                // Number of seconds to wait before sending another request
                sleep($retryAfter);
            }
            $response = $api->search($query, $this->type,['limit' => $limit]);
        } // end of catch

        if (count($response->{$types}->items)) {
            foreach ($response->{$types}->items as $item) {
                $item_id = $item->id;
                $result  = $api->{$getType}($item_id);
                foreach ($result->images as $image) {
                    $images[] = array(
                    'url' => $image->url,
                    'mime' => 'image/jpeg',
                    'title' => 'Spotify'
                );
                }
            }
        }

        return $images;
    } // gather_spotify

    /**
     * gather_musicbrainz
     * This function retrieves art based on MusicBrainz' Advanced
     * Relationships
     * @param integer $limit
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

        if ($data['mb_albumid']) {
            debug_event(self::class, "gather_musicbrainz Album MBID: " . $data['mb_albumid'], 5);
        } else {
            return $images;
        }

        $mbrainz  = new MusicBrainz(new RequestsHttpAdapter());
        $includes = array(
            'url-rels'
        );
        try {
            $release = $mbrainz->lookup('release', $data['mb_albumid'], $includes);
        } catch (Exception $error) {
            debug_event(self::class, "gather_musicbrainz exception: " . $error, 3);

            return $images;
        }

        $asin = $release->asin;

        if ($asin) {
            debug_event(self::class, "gather_musicbrainz Found ASIN: " . $asin, 5);
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
                debug_event(self::class, "gather_musicbrainz Evaluating Amazon URL: " . $url, 5);
                $request = Requests::get($url, array(), Core::requests_options());
                if ($request->status_code == 200) {
                    $num_found++;
                    debug_event(self::class, "gather_musicbrainz Amazon URL added: " . $url, 5);
                    $images[] = array(
                        'url' => $url,
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
        $coverartsites   = array();
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
            debug_event(self::class, "gather_musicbrainz Found URL AR: " . $arurl, 5);
            foreach ($coverartsites as $casite) {
                if (strpos($arurl, $casite['domain']) !== false) {
                    debug_event(self::class, "gather_musicbrainz Matched coverart site: " . $casite['name'], 5);
                    if (preg_match($casite['regexp'], $arurl, $matches)) {
                        $num_found++;
                        $url = $casite['imguri'];
                        debug_event(self::class, "gather_musicbrainz Generated URL added: " . $url, 5);
                        $images[] = array(
                            'url' => $url,
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
     * @param integer $limit
     * @return array
     */
    public function gather_folder($limit = 5)
    {
        if (!$limit) {
            $limit = 5;
        }

        $results   = array();
        $preferred = array();
        // For storing which directories we've already done
        $processed = array();

        /* See if we are looking for a specific filename */
        $preferred_filename = (AmpConfig::get('album_art_preferred_filename')) ?: 'folder.jpg';
        $artist_filename    = AmpConfig::get('artist_art_preferred_filename');
        $artist_art_folder  = AmpConfig::get('artist_art_folder');

        // Array of valid extensions
        $image_extensions = array(
            'bmp',
            'gif',
            'jp2',
            'jpeg',
            'jpg',
            'png'
        );

        $dirs = array();
        if ($this->type == 'album') {
            $media = new Album($this->uid);
            $songs = $media->get_songs();
            foreach ($songs as $song_id) {
                $song   = new Song($song_id);
                $dirs[] = Core::conv_lc_file(dirname($song->file));
            }
        } elseif ($this->type == 'video') {
            $media  = new Video($this->uid);
            $dirs[] = Core::conv_lc_file(dirname($media->file));
        } elseif ($this->type == 'artist') {
            $media = new Artist($this->uid);
            $media->format();
            $preferred_filename = str_replace(array('<', '>', '\\', '/'), '_', $media->f_full_name);
            if ($artist_art_folder) {
                $dirs[] = Core::conv_lc_file($artist_art_folder);
            }
            // get the folders from songs as well
            $songs = $media->get_songs();
            foreach ($songs as $song_id) {
                $song = new Song($song_id);
                // look in the directory name of the files (e.g. /mnt/Music/%artistName%/%album%)
                $dirs[] = Core::conv_lc_file(dirname($song->file));
                // look one level up (e.g. /mnt/Music/%artistName%)
                $dirs[] = Core::conv_lc_file(dirname($song->file, 2));
            }
        }

        foreach ($dirs as $dir) {
            if (isset($processed[$dir])) {
                continue;
            }

            debug_event(self::class, "gather_folder: Opening $dir and checking for " . $this->type . " Art", 3);

            /* Open up the directory */
            $handle = opendir($dir);

            if (!$handle) {
                AmpError::add('general', T_('Unable to open') . ' ' . $dir);
                debug_event(self::class, "gather_folder: Error: Unable to open $dir for album art read", 2);
                continue;
            }

            $processed[$dir] = true;

            // Recurse through this dir and create the files array
            while (false !== ($file = readdir($handle))) {
                $extension = pathinfo($file);
                $extension = $extension['extension'];

                // Make sure it looks like an image file
                if (!in_array($extension, $image_extensions)) {
                    continue;
                }

                $full_filename = $dir . '/' . $file;

                // Make sure it's got something in it
                if (!Core::get_filesize($full_filename)) {
                    debug_event(self::class, "gather_folder: Empty file, rejecting" . $file, 5);
                    continue;
                }

                // Regularize for mime type
                if ($extension == 'jpg') {
                    $extension = 'jpeg';
                }

                // Take an md5sum so we don't show duplicate files.
                $index = md5($full_filename);

                if (($file == $preferred_filename || pathinfo($file, PATHINFO_FILENAME) == $preferred_filename) ||
                    ($file == $artist_filename || pathinfo($file, PATHINFO_FILENAME) == $artist_filename)) {
                    // We found the preferred filename and so we're done.
                    debug_event(self::class, "gather_folder: Found preferred image file: $file", 5);
                    $preferred[$index] = array(
                        'file' => $full_filename,
                        'mime' => 'image/' . $extension,
                        'title' => 'Folder'
                    );
                    break;
                }
                if ($this->type !== 'artist') {
                    debug_event(self::class, "gather_folder: Found image file: $file", 5);
                    $results[$index] = array(
                        'file' => $full_filename,
                        'mime' => 'image/' . $extension,
                        'title' => 'Folder'
                    );
                }
            } // end while reading dir
            closedir($handle);
        } // end foreach dirs

        if (!empty($preferred)) {
            // We found our favorite filename somewhere, so we need
            // to dump the other, less sexy ones.
            $results = $preferred;
        }

        //debug_event(self::class, "gather_folder: Results: " . json_encode($results), 5);
        if ($limit && count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return array_values($results);
    } // gather_folder

    /**
     * gather_tags
     * This looks for the art in the meta-tags of the file
     * itself
     * @param integer $limit
     * @return array
     */
    public function gather_tags($limit = 5)
    {
        if (!$limit) {
            $limit = 5;
        }

        if ($this->type == "video") {
            $data = $this->gather_video_tags();
        } elseif ($this->type == 'album' || $this->type == 'artist') {
            $data = $this->gather_song_tags($limit);
        } elseif (($this->type == 'song') && (AmpConfig::get('gather_song_art', false))) {
            $data = $this->gather_song_tags_single($limit);
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
     * @param integer $limit
     * @return array
     */
    public function gather_song_tags($limit = 5)
    {
        // We need the filenames
        if ($this->type == 'album') {
            $album = new Album($this->uid);
            $songs = $album->get_songs();
        } else {
            $artist = new Artist($this->uid);
            $songs  = $artist->get_songs();
        }

        // grab the songs and define our results
        $data  = array();

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
     * Gather tags from single song instead of full album
     * (taken from function gather_song_tags with some changes)
     * @param int $limit
     * @return array
     */
    public function gather_song_tags_single($limit = 5)
    {
        // get song object directly from id, not by loop through album
        $song = new Song($this->uid);
        $data = array();
        $data = array_merge($data, $this->gather_media_tags($song));

        if ($limit && count($data) >= $limit) {
            return array_slice($data, 0, $limit);
        }

        return $data;
    }

    /**
     * Gather tags from files.
     * @param Song|Video $media
     * @return array
     */
    protected function gather_media_tags($media)
    {
        $mtype  = strtolower(get_class($media));
        $data   = array();
        $getID3 = new getID3();
        try {
            $id3 = $getID3->analyze($media->file);
        } catch (Exception $error) {
            debug_event(self::class, 'getid3' . $error->getMessage(), 1);
        }

        if (isset($id3['asf']['extended_content_description_object']['content_descriptors']['13'])) {
            $image  = $id3['asf']['extended_content_description_object']['content_descriptors']['13'];
            $data[] = array(
                $mtype => $media->file,
                'raw' => $image['data'],
                'mime' => $image['mime'],
                'title' => 'ID3');
        }

        if (isset($id3['id3v2']['APIC'])) {
            // Foreach in case they have more than one
            foreach ($id3['id3v2']['APIC'] as $image) {
                $data[] = array(
                    $mtype => $media->file,
                    'raw' => $image['data'],
                    'mime' => $image['mime'],
                    'title' => 'ID3');
            }
        }

        return $data;
    }

    /**
     * gather_google
     * Raw google search to retrieve the art, not very reliable
     *
     * @param integer $limit
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
        $size   = '&imgsz=m'; // Medium

        $url = "http://www.google.com/search?source=hp&tbm=isch&q=" . $search . "&oq=&um=1&ie=UTF-8&sa=N&tab=wi&start=0&tbo=1" . $size;
        debug_event(self::class, 'Search url: ' . $url, 5);

        try {
            // Need this to not be considered as a bot (are we? ^^)
            $headers = array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:67.0) Gecko/20100101 Firefox/67.0',
            );

            $query = Requests::get($url, $headers, Core::requests_options());
            $html  = $query->body;

            if (preg_match_all('/"ou":"(http.+?)"/', $html, $matches)) {
                foreach ($matches[1] as $match) {
                    if (preg_match('/lookaside\.fbsbx\.com/', $match)) {
                        break;
                    }
                    $match = rawurldecode($match);
                    debug_event(self::class, 'Found image at: ' . $match, 5);
                    $results = pathinfo($match);
                    $test    = $results['extension'];
                    $pos     = strpos($test, '?');
                    if ($pos > 0) {
                        $results['extension'] = substr($test, 0, $pos);
                    }
                    if (preg_match('~[^png|^jpg|^jpeg|^jif|^bmp]~', $test)) {
                        $results['extension']  = 'jpg';
                    }

                    $mime = 'image/';
                    $mime .= isset($results['extension']) ? $results['extension'] : 'jpeg';

                    $images[] = array('url' => $match, 'mime' => $mime, 'title' => 'Google');
                    if ($limit > 0 && count($images) >= $limit) {
                        break;
                    }
                }
            }
        } catch (Exception $error) {
            debug_event(self::class, 'Error getting google images: ' . $error->getMessage(), 2);
        }

        return $images;
    } // gather_google

    /**
     * gather_lastfm
     * This returns the art from lastfm. It doesn't currently require an
     * account but may in the future.
     * @param integer $limit
     * @param array $data
     * @return array
     */
    public function gather_lastfm($limit = 5, $data = array())
    {
        $images = array();

        try {
            $coverart = array();
            // search for album objects
            if ((!empty($data['artist']) && !empty($data['album']))) {
                $xmldata = Recommendation::album_search($data['artist'], $data['album']);
                if (!$xmldata) {
                    return array();
                }
                if (!$xmldata->album->image) {
                    return array();
                }
                foreach ($xmldata->album->image as $albumart) {
                    $coverart[] = (string) $albumart;
                }
            }
            // Albums only for last FM
            if (empty($coverart)) {
                return array();
            }
            ksort($coverart);
            foreach ($coverart as $url) {
                // We need to check the URL for the /noimage/ stuff
                if (is_array($url) || strpos($url, '/noimage/') !== false) {
                    debug_event(self::class, 'LastFM: Detected as noimage, skipped', 3);
                    continue;
                }
                debug_event(self::class, 'LastFM: found image ' . $url, 3);

                // HACK: we shouldn't rely on the extension to determine file type
                $results  = pathinfo($url[0]);
                $mime     = 'image/' . $results['extension'];
                $images[] = array('url' => $url, 'mime' => $mime, 'title' => 'LastFM');
                if ($limit && count($images) >= $limit) {
                    return $images;
                }
            } // end foreach
        } catch (Exception $error) {
            debug_event(self::class, 'LastFM error: ' . $error->getMessage(), 3);
        }

        return $images;
    } // gather_lastfm

    /**
     * Gather metadata from plugin.
     * @param $plugin
     * @param string $type
     * @param array $options
     * @return array
     */
    public static function gather_metadata_plugin($plugin, $type, $options)
    {
        $gtypes     = array();
        $media_info = array();
        switch ($type) {
            case 'tvshow':
            case 'tvshow_season':
            case 'tvshow_episode':
                $gtypes[]                     = 'tvshow';
                $media_info['tvshow']         = $options['tvshow'];
                $media_info['tvshow_season']  = $options['tvshow_season'];
                $media_info['tvshow_episode'] = $options['tvshow_episode'];
                break;
            case 'song':
                $media_info['mb_trackid'] = $options['mb_trackid'];
                $media_info['title']      = $options['title'];
                $media_info['artist']     = $options['artist'];
                $media_info['album']      = $options['album'];
                $gtypes[]                 = 'song';
                break;
            case 'album':
                $media_info['mb_albumid']       = $options['mb_albumid'];
                $media_info['mb_albumid_group'] = $options['mb_albumid_group'];
                $media_info['artist']           = $options['artist'];
                $media_info['title']            = $options['album'];
                $gtypes[]                       = 'music';
                $gtypes[]                       = 'album';
                break;
            case 'artist':
                $media_info['mb_artistid'] = $options['mb_artistid'];
                $media_info['title']       = $options['artist'];
                $gtypes[]                  = 'music';
                $gtypes[]                  = 'artist';
                break;
            case 'movie':
                $gtypes[]            = 'movie';
                $media_info['title'] = $options['keyword'];
                break;
        }

        $meta   = $plugin->get_metadata($gtypes, $media_info);
        $images = array();

        if ($meta['art']) {
            $url      = $meta['art'];
            $ures     = pathinfo($url);
            $images[] = array('url' => $url, 'mime' => 'image/' . $ures['extension'], 'title' => $plugin->name);
        }
        if ($meta['tvshow_season_art']) {
            $url      = $meta['tvshow_season_art'];
            $ures     = pathinfo($url);
            $images[] = array('url' => $url, 'mime' => 'image/' . $ures['extension'], 'title' => $plugin->name);
        }
        if ($meta['tvshow_art']) {
            $url      = $meta['tvshow_art'];
            $ures     = pathinfo($url);
            $images[] = array('url' => $url, 'mime' => 'image/' . $ures['extension'], 'title' => $plugin->name);
        }

        return $images;
    }

    /**
     * Get thumb size from thumb type.
     * @param integer $thumb
     * @return array
     */
    public static function get_thumb_size($thumb)
    {
        $size = array();

        switch ($thumb) {
            case 1:
                /* This is used by the now_playing / browse stuff */
                $size['height']   = 100;
                $size['width']    = 100;
                break;
            case 2:
                $size['height']    = 128;
                $size['width']     = 128;
                break;
            case 3:
                /* This is used by the embedded web player */
                $size['height']    = 80;
                $size['width']     = 80;
                break;
            case 5:
                /* Web Player size */
                $size['height'] = 32;
                $size['width']  = 32;
                break;
            case 6:
                /* Video browsing size */
                $size['height'] = 150;
                $size['width']  = 100;
                break;
            case 7:
                /* Video page size */
                $size['height'] = 300;
                $size['width']  = 200;
                break;
            case 8:
                /* Video preview size */
                 $size['height'] = 200;
                 $size['width']  = 470;
                break;
            case 9:
                /* Video preview size */
                 $size['height'] = 100;
                 $size['width']  = 235;
                break;
            case 10:
                /* Search preview size */
                 $size['height'] = 24;
                 $size['width']  = 24;
                break;
            case 4:
                /* Popup Web Player size */
            case 11:
                /* Large view browse size */
            case 12:
                /* Search preview size */
                 $size['height'] = 150;
                 $size['width']  = 150;
                break;
            default:
                $size['height']   = 200;
                $size['width']    = 200;
                break;
        }

        return $size;
    }

    /**
     * Display an item art.
     * @param library_item $item
     * @param integer $thumb
     * @param string $link
     * @return boolean
     */
    public static function display_item($item, $thumb, $link = null)
    {
        return self::display($item->type ?: strtolower(get_class($item)), $item->id, $item->get_fullname(), $thumb, $link);
    }

    /**
     * Display an item art.
     * @param string $object_type
     * @param integer $object_id
     * @param string $name
     * @param integer $thumb
     * @param string $link
     * @param boolean $show_default
     * @param string $kind
     * @return boolean
     */
    public static function display($object_type, $object_id, $name, $thumb, $link = null, $show_default = true, $kind = 'default')
    {
        if (!self::is_valid_type($object_type)) {
            return false;
        }

        if (!$show_default) {
            // Don't show any image if not available
            if (!self::has_db($object_id, $object_type, $kind)) {
                return false;
            }
        }
        $size        = self::get_thumb_size($thumb);
        $prettyPhoto = ($link === null);
        if ($link === null) {
            $link = AmpConfig::get('web_path') . "/image.php?object_id=" . $object_id . "&object_type=" . $object_type;
            if (AmpConfig::get('use_auth') && AmpConfig::get('require_session')) {
                $link .= "&auth=" . session_id();
            }
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
        // This to keep browser cache feature but force a refresh in case image just changed
        if (Art::has_db($object_id, $object_type)) {
            $art = new Art($object_id, $object_type);
            if ($art->has_db_info()) {
                $imgurl .= '&fooid=' . $art->id;
            }
        }
        echo "<img src=\"" . $imgurl . "\" alt=\"" . $name . "\" height=\"" . $size['height'] . "\" width=\"" . $size['width'] . "\" />";

        if ($size['height'] >= 150 && $size['height'] <= 300) {
            echo "<div class=\"item_art_play\">";
            echo Ajax::text('?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $object_id . '\' + getPagePlaySettings() + \'', '<span class="item_art_play_icon" title="' . T_('Play') . '" />', 'directplay_art_' . $object_type . '_' . $object_id);
            echo "</div>";
        }

        if ($prettyPhoto) {
            $libitem = new $object_type($object_id);
            echo "<div class=\"item_art_actions\">";
            if (Core::get_global('user')->has_access(50) || (Core::get_global('user')->has_access(25) && Core::get_global('user')->id == $libitem->get_user_owner())) {
                echo "<a href=\"javascript:NavigateTo('" . AmpConfig::get('web_path') . "/arts.php?action=show_art_dlg&object_type=" . $object_type . "&object_id=" . $object_id . "&burl=' + getCurrentPage());\">";
                echo UI::get_icon('edit', T_('Edit/Find Art'));
                echo "</a>";

                echo "<a href=\"javascript:NavigateTo('" . AmpConfig::get('web_path') . "/arts.php?action=clear_art&object_type=" . $object_type . "&object_id=" . $object_id . "&burl=' + getCurrentPage());\" onclick=\"return confirm('" . T_('Do you really want to reset art?') . "');\">";
                echo UI::get_icon('delete', T_('Reset Art'));
                echo "</a>";
            }
            echo"</div>";
        }

        echo "</a>\n";
        echo "</div>";

        return true;
    }
} // end art.class
