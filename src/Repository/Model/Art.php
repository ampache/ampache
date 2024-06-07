<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Art\Collector\MetaTagCollectorModule;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\System\Core;
use Ampache\Repository\SongRepositoryInterface;
use Exception;
use PDOStatement;
use WpOrg\Requests;
use RuntimeException;

/**
 * This class handles the images / artwork in ampache
 * This was initially in the album class, but was pulled out
 * to be more general and potentially apply to albums, artists, movies etc
 */
class Art extends database_object
{
    protected const DB_TABLENAME = 'image';

    public const VALID_TYPES = array(
        'bmp',
        'gif',
        'jp2',
        'jpeg',
        'jpg',
        'png',
        'webp',
    );

    /**
     * @var int $id
     */
    public $id;
    /**
     * @var string $type
     */
    public $type;
    /**
     * @var int $uid
     */
    public $uid; // UID of the object not ID because it's not the ART.ID
    /**
     * @var string $raw
     */
    public $raw; // Raw art data
    /**
     * @var string $raw_mime
     */
    public $raw_mime;
    /**
     * @var string $kind
     */
    public $kind;

    /**
     * @var string $thumb
     */
    public $thumb;
    /**
     * @var string $thumb_mime
     */
    public $thumb_mime;

    /**
     * Constructor
     * Art constructor, takes the UID of the object and the
     * object type.
     * @param int|null $uid
     * @param string $type
     * @param string $kind
     */
    public function __construct($uid = 0, $type = 'album', $kind = 'default')
    {
        if (!$uid) {
            return;
        }
        if (Art::is_valid_type($type)) {
            $this->type = $type;
            $this->uid  = (int)($uid);
            $this->kind = $kind;
        }
    }

    public function getId(): int
    {
        return (int)($this->id ?: 0);
    }

    /**
     * @param string $type
     */
    public static function is_valid_type($type): bool
    {
        if (!$type) {
            return false;
        }

        return (InterfaceImplementationChecker::is_library_item($type) || $type == 'user');
    }

    /**
     * build_cache
     * This attempts to reduce # of queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point
     * @param list<int> $object_ids
     * @param null|string $type
     */
    public static function build_cache(array $object_ids, $type = null): bool
    {
        if ($object_ids === []) {
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
    }

    /**
     * extension
     * This returns the file extension for the currently loaded art
     * @param string|null $mime
     */
    public static function extension($mime): string
    {
        if (empty($mime)) {
            return '';
        }
        $data      = explode('/', $mime);
        $extension = $data['1'] ?? '';

        if ($extension == 'jpeg') {
            $extension = 'jpg';
        }

        return (string)$extension;
    }

    /**
     * test_image
     * Runs some sanity checks on the putative image
     * @param string $source
     * @return bool
     * @throws RuntimeException
     */
    private static function test_image($source): bool
    {
        if (strlen((string) $source) < 10) {
            debug_event(self::class, 'Invalid image passed', 1);

            return false;
        }
        $max_upload_size = (int)AmpConfig::get('max_upload_size', 0);

        // Check image size doesn't exceed the limit
        if ($max_upload_size > 0 && strlen((string) $source) > $max_upload_size) {
            debug_event(self::class, 'Image size (' . strlen((string) $source) . ') exceed the limit (' . $max_upload_size . ').', 1);

            return false;
        }

        // Check to make sure PHP:GD exists. Don't test things you can't change
        if (!function_exists('imagecreatefromstring')) {
            return true;
        }

        $test  = false;
        $image = false;
        if (is_string($source)) {
            $test  = true;
            $image = imagecreatefromstring($source);
            if (!$image || imagesx($image) < 5 || imagesy($image) < 5) {
                debug_event(self::class, 'Image failed PHP-GD test', 1);
                $test = false;
            }
        }
        if ($test && $image) {
            if (imagedestroy($image) === false) {
                throw new RuntimeException('The image handle from source: ' . $source . ' could not be destroyed');
            }
        }

        return $test;
    }

    /**
     * get
     * This returns the art for our current object, this can
     * look in the database and will return the thumb if it
     * exists, if it doesn't depending on settings it will try
     * to create it.
     * @param bool $raw
     * @param bool $fallback
     */
    public function get($raw = false, $fallback = false): string
    {
        // Get the data either way (allow forcing to fallback image)
        if (!$this->has_db_info($fallback)) {
            return '';
        }

        if ($raw || !$this->thumb) {
            return $this->raw;
        } else {
            return $this->thumb;
        }
    }

    /**
     * has_db_info
     * This pulls the information out from the database, depending
     * on if we want to resize and if there is not a thumbnail go
     * ahead and try to resize
     *
     * @param bool $fallback
     */
    public function has_db_info($fallback = false): bool
    {
        $sql         = "SELECT `id`, `image`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `kind` = ?";
        $db_results  = Dba::read($sql, array($this->type, $this->uid, $this->kind));
        $default_art = false;

        while ($results = Dba::fetch_assoc($db_results)) {
            if ($results['size'] == 'original') {
                if (AmpConfig::get('album_art_store_disk')) {
                    $this->raw = (string)self::read_from_dir($results['size'], $this->type, $this->uid, $this->kind, $results['mime']);
                } else {
                    $this->raw = $results['image'];
                }
                $this->raw_mime = $results['mime'];
            } elseif (AmpConfig::get('resize_images')) {
                if (!empty($this->thumb)) { // See https://github.com/ampache/ampache/issues/3386
                    continue;
                }
                if (AmpConfig::get('album_art_store_disk')) {
                    $this->thumb = (string)self::read_from_dir($results['size'], $this->type, $this->uid, $this->kind, $results['mime']);
                } elseif ($results['size'] == '275x275') {
                    $this->thumb = $results['image'];
                }
                $this->raw_mime = $results['mime'];
            }
            $this->id = (int)$results['id'];
        }
        // return a default image if fallback is requested
        if (!$this->raw && $fallback) {
            $this->raw      = self::read_from_images();
            $this->raw_mime = 'image/png';
            $default_art    = true;
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
                if (!$default_art) {
                    $this->save_thumb($data['thumb'], $data['thumb_mime'], $size);
                }
                $this->thumb      = $data['thumb'];
                $this->thumb_mime = $data['thumb_mime'];
            } else {
                debug_event(self::class, 'Art id {' . $this->id . '} Unable to generate thumbnail for ' . $this->type . ': ' . $this->uid, 1);
            }
        } // if no thumb, but art and we want to resize

        return true;
    }

    /**
     * This check if an object has an associated image in db.
     * @param int $object_id
     * @param string $object_type
     * @param string $kind
     */
    public static function has_db($object_id, $object_type, $kind = 'default'): bool
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
    public function insert_url($url): void
    {
        debug_event(self::class, 'Insert art from url ' . $url, 4);
        $image = self::get_from_source(array('url' => $url), $this->type);
        $rurl  = pathinfo($url);
        $mime  = "image/" . ($rurl['extension'] ?? 'jpg');
        $this->insert($image, $mime);
    }

    /**
     * insert
     * This takes the string representation of an image and inserts it into
     * the database. You must also pass the mime type.
     * @param string $source
     * @param string|null $mime
     */
    public function insert($source, $mime = ''): bool
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
        $width      = (int)($dimensions['width']);
        $height     = (int)($dimensions['height']);
        $sizetext   = 'original';

        if (!self::check_dimensions($dimensions)) {
            return false;
        }

        // Default to image/jpeg if they don't pass anything
        $mime = empty($mime) ? $mime : 'image/jpeg';
        // Blow it away!
        $this->reset();
        $picturetypeid = ($this->type == 'album') ? 3 : 8;

        if (AmpConfig::get('write_tags', false)) {
            $className = ObjectTypeToClassNameMapper::map($this->type);
            $object    = new $className($this->uid);
            $songs     = array();
            debug_event(__CLASS__, 'Inserting ' . $this->type . ' image' . $object->name . ' for song files.', 5);
            if ($this->type === 'album') {
                /** Use special treatment for albums */
                $songs = $this->getSongRepository()->getByAlbum($object->id);
            } elseif ($this->type === 'artist') {
                /** Use special treatment for artists */
                $songs = $this->getSongRepository()->getByArtist($object->id);
            }
            global $dic;
            $utilityFactory = $dic->get(UtilityFactoryInterface::class);

            foreach ($songs as $song_id) {
                $song        = new Song($song_id);
                $description = ($this->type == 'artist') ? $song->get_artist_fullname() : $object->full_name;
                $vainfo      = $utilityFactory->createVaInfo(
                    $song->file
                );

                $ndata      = array();
                $data       = $vainfo->read_id3();
                $fileformat = $data['fileformat'];
                if ($fileformat == 'flac' || $fileformat == 'ogg') {
                    $apics = $data['flac']['PICTURE'];
                } else {
                    $apics = $data['id3v2']['APIC'];
                }
                /* is the file flac or mp3? */
                $apic_typeid   = ($fileformat == 'flac' || $fileformat == 'ogg') ? 'typeid' : 'picturetypeid';
                $apic_mimetype = ($fileformat == 'flac' || $fileformat == 'ogg') ? 'image_mime' : 'mime';
                $new_pic       = array(
                    'data' => $source,
                    'mime' => $mime,
                    'picturetypeid' => $picturetypeid,
                    'description' => $description
                );

                if (is_null($apics)) {
                    $ndata['attached_picture'][] = $new_pic;
                } else {
                    switch (count($apics)) {
                        case 1:
                            $idx = $this->check_for_duplicate($apics, $ndata, $new_pic, $apic_typeid);
                            if (is_null($idx)) {
                                $ndata['attached_picture'][] = $new_pic;
                                $ndata['attached_picture'][] = array(
                                    'data' => $apics[0]['data'],
                                    'description' => $apics[0]['description'],
                                    'mime' => $apics[0]['mime'],
                                    'picturetypeid' => $apics[0]['picturetypeid']
                                );
                            }
                            break;
                        case 2:
                            $idx = $this->check_for_duplicate($apics, $ndata, $new_pic, $apic_typeid);
                            /* If $idx is null, it means both images are of opposite types
                             * of the new image. Either image could be replaced to have
                             * one cover and one artist image.
                             */
                            if (is_null($idx)) {
                                $ndata['attached_picture'][0] = $new_pic;
                            } else {
                                $apicsId                             = ($idx == 0) ? 1 : 0;
                                $ndata['attached_picture'][$apicsId] = array(
                                    'data' => $apics[$apicsId]['data'],
                                    'mime' => $apics[$apicsId][$apic_mimetype],
                                    'picturetypeid' => $apics[$apicsId][$apic_typeid],
                                    'description' => $apics[$apicsId]['description']
                                );
                            }

                            break;
                    }
                }
                unset($apics);
                $tags  = ($fileformat == 'flac' || $fileformat == 'ogg') ? 'vorbiscomment' : 'id3v2';
                $ndata = array_merge($ndata, $vainfo->prepare_metadata_for_writing($data['tags'][$tags]));
                $vainfo->write_id3($ndata);
            } // foreach song
        } // write_id3

        if (AmpConfig::get('album_art_store_disk') && self::write_to_dir($source, $sizetext, $this->type, $this->uid, $this->kind, $mime)) {
            $source = null;
        }
        // Insert it!
        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `width`, `height`, `object_type`, `object_id`, `kind`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($source, $mime, $sizetext, $width, $height, $this->type, $this->uid, $this->kind));

        return true;
    }

    /**
     * check_for_duplicate
     * @param array $apics
     * @param array $ndata
     * @param array $new_pic
     * @param string $apic_typeid
     * @return int|null
     */
    private function check_for_duplicate($apics, &$ndata, $new_pic, $apic_typeid): ?int
    {
        $idx = null;
        $cnt = count($apics);
        for ($i = 0; $i < $cnt; $i++) {
            if ($new_pic['picturetypeid'] == $apics[$i][$apic_typeid]) {
                $ndata['attached_picture'][$i]['description']   = $new_pic['description'];
                $ndata['attached_picture'][$i]['data']          = $new_pic['data'];
                $ndata['attached_picture'][$i]['mime']          = $new_pic['mime'];
                $ndata['attached_picture'][$i]['picturetypeid'] = $new_pic['picturetypeid'];
                $idx                                            = $i;
                break;
            }
        }

        return $idx;
    }

    /**
     * check_dimensions
     * @param array{width: int, height: int} $dimensions
     */
    public static function check_dimensions($dimensions): bool
    {
        $width  = (int)($dimensions['width']);
        $height = (int)($dimensions['height']);

        if ($width > 0 && $height > 0) {
            $minw = AmpConfig::get('album_art_min_width', 0);
            $maxw = AmpConfig::get('album_art_max_width', 0);
            $minh = AmpConfig::get('album_art_min_height', 0);
            $maxh = AmpConfig::get('album_art_max_height', 0);

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
     * get_dir_on_disk
     * @param string $type
     * @param int $uid
     * @param string $kind
     * @param bool $autocreate
     * @return string|false
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
     * @param string $sizetext
     * @param string $type
     * @param int $uid
     * @param string $kind
     * @param string|null $mime
     */
    private static function write_to_dir($source, $sizetext, $type, $uid, $kind, $mime): bool
    {
        $path = self::get_dir_on_disk($type, $uid, $kind, true);
        if ($path === false) {
            return false;
        }
        if (!Core::is_readable($path)) {
            debug_event(self::class, 'Local image art directory ' . $path . ' does not exist.', 1);

            return false;
        }
        $path .= "art-" . $sizetext . "." . self::extension($mime);
        if (Core::is_readable($path)) {
            unlink($path);
        }
        $filepath = fopen($path, "wb");
        if ($filepath) {
            fwrite($filepath, $source);
            fclose($filepath);
        }

        return true;
    }

    /**
     * read_from_images
     */
    private static function read_from_images(): ?string
    {
        $path = __DIR__ . '/../../../images/blankalbum.png';
        if (!Core::is_readable($path)) {
            debug_event(self::class, 'read_from_images ' . $path . ' cannot be read.', 1);

            return null;
        }

        $image    = '';
        $filepath = fopen($path, "rb");
        if ($filepath) {
            do {
                $image .= fread($filepath, 2048);
            } while (!feof($filepath));
            fclose($filepath);
        }

        return $image;
    }

    /**
     * read_from_dir
     * @param string $sizetext
     * @param string $type
     * @param int $uid
     * @param string $kind
     * @param string $mime
     */
    private static function read_from_dir($sizetext, $type, $uid, $kind, $mime): ?string
    {
        $path = self::get_dir_on_disk($type, $uid, $kind);
        if ($path === false) {
            return null;
        }
        $path .= "art-" . $sizetext . '.' . self::extension($mime);
        if (!Core::is_readable($path)) {
            debug_event(self::class, 'Local image art ' . $path . ' cannot be read.', 1);

            return null;
        }

        $image    = '';
        $filepath = fopen($path, "rb");
        if ($filepath) {
            do {
                $image .= fread($filepath, 2048);
            } while (!feof($filepath));
            fclose($filepath);
        }

        return $image;
    }

    /**
     * delete_from_dir
     * @param string $type
     * @param int $uid
     * @param string $kind
     */
    public static function delete_from_dir($type, $uid, $kind = ''): void
    {
        if ($type && $uid) {
            $path = self::get_dir_on_disk($type, $uid, $kind);
            if ($path !== false) {
                self::delete_rec_dir(rtrim($path, '/'));
            }
        }
    }

    /**
     * delete_rec_dir
     * @param string $path
     */
    private static function delete_rec_dir($path): void
    {
        debug_event(self::class, 'Deleting ' . (string) $path . ' directory...', 5);

        if (Core::is_readable($path)) {
            foreach (scandir($path) as $file) {
                if ('.' === $file || '..' === $file) {
                    continue;
                } elseif (is_dir($path . '/' . $file)) {
                    self::delete_rec_dir(rtrim($path, '/') . '/' . $file);
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
    public function reset(): void
    {
        $this->getArtCleanup()->deleteForArt($this);
    }

    /**
     * save_thumb
     * This saves the thumbnail that we're passed
     * @param string $source
     * @param string $mime
     * @param array $size
     */
    public function save_thumb($source, $mime, $size): bool
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

        if (AmpConfig::get('album_art_store_disk') && self::write_to_dir($source, $sizetext, $this->type, $this->uid, $this->kind, $mime)) {
            $source = null;
        }
        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `width`, `height`, `object_type`, `object_id`, `kind`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($source, $mime, $sizetext, $width, $height, $this->type, $this->uid, $this->kind));

        return true;
    }

    /**
     * get_thumb
     * Returns the specified resized image.  If the requested size doesn't
     * already exist, create and cache it.
     * @param array{width: int, height: int} $size
     * @return array{thumb?: string, thumb_mime?: string}
     */
    public function get_thumb($size): array
    {
        $sizetext   = $size['width'] . 'x' . $size['height'];
        $sql        = "SELECT `image`, `mime` FROM `image` WHERE `size` = ? AND `object_type` = ? AND `object_id` = ? AND `kind` = ?";
        $db_results = Dba::read($sql, array($sizetext, $this->type, $this->uid, $this->kind));

        $results = Dba::fetch_assoc($db_results);
        if (count($results)) {
            if (AmpConfig::get('album_art_store_disk')) {
                $image = self::read_from_dir($sizetext, $this->type, $this->uid, $this->kind, $results['mime']);
            } else {
                $image = $results['image'];
            }

            if ($image != null) {
                return array(
                    'thumb' => (AmpConfig::get('album_art_store_disk'))
                        ? self::read_from_dir($sizetext, $this->type, $this->uid, $this->kind, $results['mime'])
                        : $results['image'],
                    'thumb_mime' => $results['mime']
                );
            } else {
                debug_event(self::class, 'Thumb entry found in database but associated data cannot be found.', 3);
            }
        }

        // If we didn't get a result try again
        $results = array();
        if (!$this->raw && $this->thumb) {
            $results = $this->generate_thumb($this->thumb, $size, $this->raw_mime);
        }
        if ($this->raw) {
            $results = $this->generate_thumb($this->raw, $size, $this->raw_mime);
        }
        if (!empty($results)) {
            $this->save_thumb($results['thumb'], $results['thumb_mime'], $size);
        }

        return $results;
    }

    /**
     * generate_thumb
     * Automatically resizes the image for thumbnail viewing.
     * Only works on gif/jpg/png/bmp. Fails if PHP-GD isn't available
     * or lacks support for the requested image type.
     * @param string $image
     * @param array $size
     * @param string $mime
     * @return array{thumb?: string, thumb_mime?: string}
     */
    public function generate_thumb($image, $size, $mime): array
    {
        $data = explode('/', (string) $mime);
        $type = ((string)($data[1] ?? '') !== '') ? strtolower((string) $data[1]) : 'jpg';

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

        if ($source_size['width'] > $source_size['height']) {
            // landscape
            $new_height = $size['height'];
            $new_width  = floor($source_size['width'] * ($new_height / $source_size['height']));
            $crop_x     = ceil(($source_size['width'] - $source_size['height']) / 2);
            $crop_y     = 0;
        } elseif ($source_size['height'] > $source_size['width']) {
            // portrait
            $new_width  = $size['width'];
            $new_height = floor($source_size['height'] * ($new_width / $source_size['width']));
            $crop_x     = 0;
            $crop_y     = ceil(($source_size['height'] - $source_size['width']) / 3); // assuming most portrait images would have faces closer to the top
        } else {
            // square
            $new_width  = $size['width'];
            $new_height = $size['height'];
            $crop_x     = 0;
            $crop_y     = 0;
        }

        if (!imagecopyresampled($thumbnail, $source, 0, 0, $crop_x, $crop_y, $new_width, $new_height, $source_size['width'], $source_size['height'])) {
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
            case 'bmp':
            case 'png':
                // Turn bmps into pngs
                imagepng($thumbnail);
                $mime_type = image_type_to_mime_type(IMAGETYPE_PNG);
                break;
            case 'webp':
                imagewebp($thumbnail);
                $mime_type = image_type_to_mime_type(IMAGETYPE_WEBP);
                break;
            default:
                $mime_type = null;
        } // resized

        if ($mime_type === null) {
            debug_event(self::class, 'Error: No mime type found using: ' . $mime, 2);

            return array();
        }

        $data = (string) ob_get_contents();
        ob_end_clean();

        imagedestroy($thumbnail);

        if ($data === '') {
            debug_event(self::class, 'Unknown Error resizing art', 1);

            return array();
        }

        return array(
            'thumb' => $data,
            'thumb_mime' => $mime_type
        );
    }

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
     */
    public static function get_from_source($data, $type): string
    {
        if (empty($data) || !is_array($data)) {
            return '';
        }

        // Already have the data, this often comes from id3tags
        if (isset($data['raw'])) {
            return $data['raw'];
        }

        // If it came from the database
        if (isset($data['db'])) {
            if (empty($type)) {
                $type = (AmpConfig::get('show_song_art')) ? 'song' : 'album';
            }
            $sql        = "SELECT * FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `size`='original'";
            $db_results = Dba::read($sql, array($type, $data['db']));
            $row        = Dba::fetch_assoc($db_results);

            return $row['art'];
        } // came from the db

        // Check to see if it's a URL
        if (array_key_exists('url', $data) && filter_var($data['url'], FILTER_VALIDATE_URL)) {
            debug_event(self::class, 'CHECKING URL ' . $data['url'], 2);
            $options = array();
            try {
                $options['timeout'] = 10;
                Requests\Autoload::register();
                $request = Requests\Requests::get($data['url'], array(), Core::requests_options($options));
                $raw     = $request->body;
            } catch (Exception $error) {
                debug_event(self::class, 'Error getting art: ' . $error->getMessage(), 2);
                $raw = '';
            }

            return $raw;
        }

        // Check to see if it's a FILE
        if (isset($data['file'])) {
            $handle     = fopen($data['file'], 'rb');
            if ($handle) {
                $image_data = (string)fread($handle, Core::get_filesize($data['file']));
                fclose($handle);

                return $image_data;
            }
        }

        // Check to see if it is embedded in id3 of a song
        if (isset($data['song'])) {
            $images = MetaTagCollectorModule::gatherFileArt($data['song']);
            foreach ($images as $image) {
                if ($data['title'] == $image['title']) {
                    return $image['raw'];
                }
            }
        } // if data song

        return '';
    }

    /**
     * url
     * This returns the constructed URL for the art in question
     * @param int $uid
     * @param string $type
     * @param string $sid
     * @param int|null $thumb
     * @return string
     */
    public static function url($uid, $type, $sid = null, $thumb = null): ?string
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
        } else {
            $sid = 'none';
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

        $mime      = $thumb_mime ?? ($mime ?? null);
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
            $url = AmpConfig::get('web_path') . '/image.php?object_id=' . scrub_out($uid) . '&object_type=' . scrub_out($type);
            if ($thumb !== null) {
                $url .= '&thumb=' . $thumb;
            }
            if (!empty($extension)) {
                $name = 'art.' . $extension;
                $url .= '&name=' . $name;
            }
        }

        return $url;
    }

    /**
     * Duplicate an object associate images to a new object
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     * @param string $new_object_type
     * @return PDOStatement|bool
     */
    public static function duplicate($object_type, $old_object_id, $new_object_id, $new_object_type = null)
    {
        $write_type = (self::is_valid_type($new_object_type))
            ? $new_object_type
            : $object_type;
        if (Art::has_db($new_object_id, $write_type) || $old_object_id == $new_object_id) {
            return false;
        }

        debug_event(self::class, 'duplicate... type:' . $object_type . ' old_id:' . $old_object_id . ' new_type:' . $write_type . ' new_id:' . $new_object_id, 5);
        if (AmpConfig::get('album_art_store_disk')) {
            $sql        = "SELECT `size`, `kind`, `mime` FROM `image` WHERE `object_type` = ? AND `object_id` = ?";
            $db_results = Dba::read($sql, array($object_type, $old_object_id));
            while ($row = Dba::fetch_assoc($db_results)) {
                $image = self::read_from_dir($row['size'], $object_type, $old_object_id, $row['kind'], $row['mime']);
                if ($image !== null) {
                    self::write_to_dir($image, $row['size'], $write_type, $new_object_id, $row['kind'], $row['mime']);
                }
            }
        }

        $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `object_type`, `object_id`, `kind`) SELECT `image`, `mime`, `size`, ? AS `object_type`, ? AS `object_id`, `kind` FROM `image` WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($write_type, $new_object_id, $object_type, $old_object_id));
    }

    /**
     * Gather metadata from plugin.
     * @param $plugin
     * @param string $type
     * @param array $options
     * @return list<array{
     *  url: string,
     *  mime: string,
     *  title: string
     * }>
     */
    public static function gather_metadata_plugin($plugin, $type, $options): array
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

        if (array_key_exists('art', $meta)) {
            $url      = $meta['art'];
            $ures     = pathinfo($url);
            $images[] = array('url' => $url, 'mime' => 'image/' . ($ures['extension'] ?? 'jpg'), 'title' => $plugin->name);
        }
        if (array_key_exists('tvshow_season_art', $meta)) {
            $url      = $meta['tvshow_season_art'];
            $ures     = pathinfo($url);
            $images[] = array('url' => $url, 'mime' => 'image/' . ($ures['extension'] ?? 'jpg'), 'title' => $plugin->name);
        }
        if (array_key_exists('tvshow_art', $meta)) {
            $url      = $meta['tvshow_art'];
            $ures     = pathinfo($url);
            $images[] = array('url' => $url, 'mime' => 'image/' . ($ures['extension'] ?? 'jpg'), 'title' => $plugin->name);
        }

        return $images;
    }

    /**
     * Get thumb size from thumb type.
     * @return array{width: int, height: int}
     */
    public static function get_thumb_size(int $thumb): array
    {
        $size = array();

        switch ($thumb) {
            case 1:
                // This is used by the now_playing / browse stuff
                $size['height'] = 100;
                $size['width']  = 100;
                break;
            case 2:
                // live stream, artist pages
                $size['height'] = 128;
                $size['width']  = 128;
                break;
            case 22:
                $size['height'] = 256;
                $size['width']  = 256;
                break;
            case 32:
                // Single Album & Podcast pages
                $size['height'] = 384;
                $size['width']  = 384;
                break;
            case 3:
                // This is used by the embedded web player
                $size['height'] = 80;
                $size['width']  = 80;
                break;
            case 5:
                // Web Player size
                $size['height'] = 32;
                $size['width']  = 32;
                break;
            case 6:
                // Video browsing size
                $size['height'] = 150;
                $size['width']  = 100;
                break;
            case 34:
                // small 34x34
                $size['height'] = 34;
                $size['width']  = 34;
                break;
            case 64:
                // medium 64x64
                $size['height'] = 64;
                $size['width']  = 64;
                break;
            case 174:
                // large 174x174
                $size['height'] = 174;
                $size['width']  = 174;
                break;
            case 300:
                // extralarge, mega 300x300
            case 7:
                // Video page size
                $size['height'] = 300;
                $size['width']  = 200;
                break;
            case 8:
                // Video preview size
                $size['height'] = 200;
                $size['width']  = 470;
                break;
            case 9:
                // Video preview size
                $size['height'] = 84;
                $size['width']  = 150; // cel_cover max-width is 150px
                break;
            case 10:
                // Search preview size
                $size['height'] = 24;
                $size['width']  = 24;
                break;
            case 4:
                // Popup Web Player size
            case 11:
                // Large view browse size
            case 12:
                // Search preview size
                $size['height'] = 150;
                $size['width']  = 150;
                break;
            default:
                $size['height'] = 200;
                $size['width']  = 200;
                break;
        }

        // For @2x output
        $size['height'] *= 2;
        $size['width'] *= 2;

        return $size;
    }

    /**
     * Display an item art.
     * @param string $object_type
     * @param int $object_id
     * @param string $name
     * @param int $thumb
     * @param string $link
     * @param bool $show_default
     * @param string $kind
     * @return bool
     */
    public static function display(
        $object_type,
        $object_id,
        $name,
        $thumb,
        $link = null,
        $show_default = true,
        $kind = 'default'
    ): bool {
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
            $link = AmpConfig::get('web_path') . "/image.php?object_id=" . $object_id . "&object_type=" . $object_type . "&thumb=" . $thumb;
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

        // For @2x output
        $size['height'] /= 2;
        $size['width'] /= 2;

        echo "<img src=\"" . $imgurl . "\" alt=\"" . $name . "\" height=\"" . $size['height'] . "\" width=\"" . $size['width'] . "\" />";

        // don't put the play icon on really large images.
        if ($size['height'] >= 150 && $size['height'] <= 300) {
            echo "<div class=\"item_art_play\">";
            echo Ajax::text(
                '?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $object_id . '\' + getPagePlaySettings() + \'',
                '<span class="item_art_play_icon" title="' . T_('Play') . '" />',
                'directplay_art_' . $object_type . '_' . $object_id
            );
            echo "</div>";
        }

        if ($prettyPhoto) {
            $className = ObjectTypeToClassNameMapper::map($object_type);
            /** @var class-string<library_item> $className */
            $libitem = new $className($object_id);
            echo "<div class=\"item_art_actions\">";
            if ((!empty(Core::get_global('user')) && Core::get_global('user')->has_access(50)) || (Core::get_global('user')->has_access(25) && Core::get_global('user')->id == $libitem->get_user_owner())) {
                echo "<a href=\"javascript:NavigateTo('" . AmpConfig::get('web_path') . "/arts.php?action=show_art_dlg&object_type=" . $object_type . "&object_id=" . $object_id . "&burl=' + getCurrentPage());\">";
                echo Ui::get_icon('edit', T_('Edit/Find Art'));
                echo "</a>";
                echo "<a href=\"javascript:NavigateTo('" . AmpConfig::get('web_path') . "/arts.php?action=clear_art&object_type=" . $object_type . "&object_id=" . $object_id . "&burl=' + getCurrentPage());\" onclick=\"return confirm('" . T_('Do you really want to reset art?') . "');\">";
                echo Ui::get_icon('delete', T_('Reset Art'));
                echo "</a>";
            }
            echo "</div>";
        }

        echo "</a>\n";
        echo "</div>";

        return true;
    }

    /**
     * @deprecated Inject dependency
     */
    private function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private function getArtCleanup(): ArtCleanupInterface
    {
        global $dic;

        return $dic->get(ArtCleanupInterface::class);
    }
}
